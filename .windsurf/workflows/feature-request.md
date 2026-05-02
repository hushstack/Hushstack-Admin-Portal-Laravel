---
description: Capture and document new feature requests into .ai/
---

# Feature Request Workflow

Use this workflow when the user requests a new feature (e.g., "Hello I want new feature about...").

## Steps

1. **Read existing `.ai/` context first**
   // turbo
   - Read `.ai/README.md` to understand the structure
   - Read `.ai/ROADMAP.md` for existing roadmap items
   - Read `.ai/TASKS.md` for current tasks

2. **Capture the feature request**
   - Extract the feature description from the user's message
   - Identify which phase/category it belongs to (Foundation, Core Features, etc.)

3. **Update documentation**
   - Add the feature to `.ai/ROADMAP.md` under the appropriate phase
   - Add the feature to `.ai/TASKS.md` under "Todo" section with format:
     ```
     - [DATE] Feature: [brief description] - Requested by user
     ```
   - Update `.ai/CURRENT_STATE.md` if the feature changes current work context

4. **Acknowledge to user**
   - Confirm the feature has been captured
   - Mention which files were updated
   - Optionally ask clarifying questions about the feature

## Example Output Format

When updating files, follow this pattern:

**ROADMAP.md addition:**
```markdown
## Phase X: [Phase Name]

- [NEW] [Feature description] - Added [DATE]
```

**TASKS.md addition:**
```markdown
## Todo

- [YYYY-MM-DD] Feature: [Feature name] - [brief description]
```

## Rules

- Always update ROADMAP.md and TASKS.md when a new feature is requested
- Be concise in documentation - capture the essence, not full conversation
- Do NOT implement the feature yet - only document it unless explicitly asked
- Keep feature descriptions clear and actionable
