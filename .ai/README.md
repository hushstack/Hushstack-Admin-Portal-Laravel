# AI Agent Folder

This folder gives AI agents a shared, simple context for working on the Hushstack Admin Portal API.

**New AI? Start here:** `.ai/AI_GUIDE.md` - Universal guide for ANY AI tool (Cascade, Codex, Gemini, etc.)

## Read Before Coding

Read these files first:

- `.ai/PROJECT_CONTEXT.md`
- `.ai/ARCHITECTURE.md`
- `.ai/CODING_RULES.md`
- `.ai/SECURITY_RULES.md`
- `.ai/CURRENT_STATE.md`
- `.ai/TASKS.md`

Also scan the real project before making changes. Important project files include:

- `README.md`
- `composer.json`
- `package.json`
- `.env.example`
- `routes/api.php`
- `routes/web.php`
- `app/`
- `config/`
- `database/`
- `resources/`
- `tests/`

## Update After Coding

After coding, update these files when relevant:

- `.ai/CURRENT_STATE.md`
- `.ai/TASKS.md`
- `.ai/DECISIONS.md`
- `.ai/AGENT_HANDOFF.md`

## Available Workflows

- `/feature-request` - When you want a new feature, use this. It auto-updates ROADMAP.md and TASKS.md.
- `/load-context` - Re-load all `.ai/` context files (runs automatically at start).

## Rules For Agents

- Scan the project before making changes.
- Do not edit unrelated files.
- Do not change existing architecture unless the task clearly requires it.
- Keep changes small and focused.
- Follow the existing Laravel style and folder patterns.
- Explain what files changed after work is complete.
- Never hardcode secrets.
