# PayWay Payment Flow Notes (2026-03-11)

Source reviewed: `d:\DOCUMENTATION\PayWay V2.0.021 APIs - Sandbox+.pdf`  
Document version: `V2.0.021`  
Released: `2023-05-16`

Purpose: short implementation memory for a future PayWay integration in this Laravel backend.

## Core Flow

1. Buyer places an order in our app/site.
2. Our backend creates a PayWay transaction by `POST`ing to the Purchase API.
3. We send PayWay the order data plus a `hash` signed from the request fields.
4. PayWay returns one of these depending on use case:
   - hosted checkout page / iframe content
   - QR payload / QR image
   - ABA deeplink for mobile
5. Customer completes authentication in PayWay / ABA app.
6. PayWay sends a server-to-server pushback notification to our callback URL.
7. Our backend verifies the final status with `check-transaction`.
8. Only after verification should we mark the order as paid.

## Main Endpoints

- Sandbox purchase: `https://checkout-sandbox.payway.com.kh/api/payment-gateway/v1/payments/purchase`
- Production purchase: `https://checkout.payway.com.kh/api/payment-gateway/v1/payments/purchase`
- Sandbox check transaction: `https://checkout-sandbox.payway.com.kh/api/payment-gateway/v1/payments/check-transaction`
- Production check transaction: `https://checkout.payway.com.kh/api/payment-gateway/v1/payments/check-transaction`

Related optional endpoints:
- Refund
- Pre-auth completion / cancellation
- Account-on-File (AoF) link / renew / remove
- Card-on-File (CoF) add / remove

## Purchase Request Basics

Method and payload:
- `POST`
- `multipart/form-data`
- Call must come from a PayWay-whitelisted domain or IP

Important request fields:
- `req_time`: UTC timestamp in `YYYYmmddHis`
- `merchant_id`: provided by PayWay
- `tran_id`: our unique order/payment id
- `amount`: KHR must not include decimals; USD can
- `payment_option`: commonly `cards`, `abapay_khqr`, or `abapay_khqr_deeplink`
- `type`: blank or `purchase` for normal payment, `pre-auth` for authorization-only
- `return_url`: base64 callback URL for dynamic pushback
- `continue_success_url`: user-facing redirect after success
- `cancel_url`: user-facing redirect after cancel
- `return_params`: our note/reference echoed back in pushback
- `custom_fields`: extra metadata echoed back in pushback / portal
- `ctid` and `pwt`: token pair for saved-account or saved-card payments

Response shape depends on the use case:
- Web checkout: render the returned checkout page in an iframe or popup
- Desktop QR: show QR for ABA/KHQR scan
- Native mobile: use returned deeplink to open ABA Mobile

## Signing Rules

PayWay is strict about hashes.

- Hash algorithm: `base64_encode(hash_hmac('sha512', signing_string, public_key, true))`
- Purchase hash must include all fields actually sent that are part of the signing list.
- Field order matters.
- The document explicitly says `view_type` is not included in the hash.
- The document also says `skip_cof3ds` is not included in the hash.

Purchase signing string from the document is effectively this ordered concatenation:

`req_time + merchant_id + tran_id + amount + items + lifetime + ctid + pwt + firstname + lastname + email + phone + type + payment_option + return_url + cancel_url + continue_success_url + return_deeplink + custom_fields + return_params`

The PDF also shows an expanded example including fields such as `shipping`, `currency`, and `topup_channel`. Practical rule: if a field belongs to PayWay's signed payload for your chosen flow, keep the exact PayWay order and include it consistently in both payload and hash generation.

## Recommended Integration Pattern

Backend responsibilities:
- Create internal payment record before calling PayWay
- Generate `tran_id`
- Generate hash on the server only
- Store raw PayWay request/response for debugging
- Expose a webhook endpoint for pushback
- After pushback, call `check-transaction` to reconcile
- Update order/payment state only from verified server-side results

Frontend responsibilities:
- Ask backend to create payment session
- Render iframe / QR / deeplink returned by backend
- Treat `continue_success_url` as UX only, not as payment proof

## Pushback and Verification

Payment success pushback example is small:
- `tran_id`
- `apv`
- `status`
- optional `return_params`

Observed meaning from the PDF:
- `status = 0` means success/approved in pushback examples

Do not trust redirect pages alone. Preferred flow:
1. Receive pushback on backend.
2. Locate internal payment by `tran_id`.
3. Call `check-transaction`.
4. Persist verified status and references.
5. Trigger business effects only once.

`check-transaction` statuses in the document:
- `0`: approved / pre-auth approved
- `1`: created
- `2`: pending
- `3`: declined
- `4`: refunded
- `5`: wrong hash
- `11`: other server-side error

## Common Failure Cases

Purchase error codes called out in the PDF:
- `1`: invalid hash
- `2`: invalid transaction id
- `4`: duplicate transaction id
- `5`: invalid success URL
- `6`: invalid domain / not whitelisted
- `7`: invalid `return_params`
- `12`: invalid currency type
- `16-19`: invalid customer fields

Operational reminders:
- Sandbox and production use different base URLs
- Production keys can expire
- Callback and success URLs need to match what PayWay allows
- Merchant profile may be single-currency only

## Optional Flows Worth Remembering

### Refund
- Refund is allowed only for successful payments
- Only within 30 days
- Supports full and partial refunds
- Uses a separate RSA-based `merchant_auth` flow

### Pre-auth
- Use purchase API with `type=pre-auth`
- Later either:
  - complete pre-auth to capture funds
  - cancel pre-auth to release funds
- Uncaptured pre-auth is auto-cancelled after 30 days by default

### Account-on-File (ABA account token)
- Link account first through QR or deeplink
- PayWay pushback returns `ctid` and `pwt`
- Future purchases can use `ctid + pwt` and will auto-charge without PIN
- Linked account token validity is 60 days and can be renewed

### Card-on-File
- Customer can save a card token separately, or during purchase
- Tokenized card payments also use `ctid + pwt`
- Purchase-and-save-card flow returns two pushbacks:
  - payment result
  - saved card token data

## Suggested Laravel Design Later

- `config/services.php` or dedicated `config/payway.php` for merchant credentials and URLs
- `PayWayService` for hash generation and API calls
- `payments` table for internal transaction state
- webhook controller for pushback
- job or service method for `check-transaction` reconciliation
- idempotent payment finalization logic keyed by `tran_id`

## Minimum Implementation Checklist

- obtain sandbox merchant id and public key
- whitelist our server IP or domain with PayWay
- define sandbox callback URL
- implement purchase request builder
- implement purchase hash generator
- store PayWay transaction responses
- implement pushback endpoint
- implement `check-transaction`
- add idempotent status mapping in database
- only then connect frontend checkout/QR flow
