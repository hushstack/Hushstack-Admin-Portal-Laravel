---
description: Auto-load .ai/ context at the start of every conversation
---

# Load AI Context Workflow

This workflow runs automatically at the start of every conversation to load project context.

## When to Run

- **Trigger**: Automatically at the start of every new conversation
- **Condition**: Always run unless the user explicitly says "skip context"

## Steps

1. **Check if `.ai/` directory exists**
   - If yes, proceed to read context files
   - If no, skip and note that no AI context exists

2. **Read required context files** (in order)
   // turbo
   - `.ai/README.md` - Always read first for folder structure
   - `.ai/PROJECT_CONTEXT.md` - Project overview
   - `.ai/ARCHITECTURE.md` - Technical architecture
   - `.ai/CODING_RULES.md` - Coding standards
   - `.ai/SECURITY_RULES.md` - Security requirements
   - `.ai/CURRENT_STATE.md` - Current work state
   - `.ai/TASKS.md` - Active tasks and todo items
   - `.ai/ROADMAP.md` - Project roadmap
   - `.ai/DECISIONS.md` - Key decisions
   - `.ai/AGENT_HANDOFF.md` - Handoff notes

3. **Summarize loaded context**
   - Briefly acknowledge what context was loaded
   - Mention current task state if relevant
   - Do NOT dump file contents unless asked

4. **Wait for user request**
   - After loading context, ask what the user wants to work on

## Rules

- Keep the context load lightweight - don't show file contents unless relevant
- If files are large, read only the relevant sections
- Always respect the priority order in README.md's "Read Before Coding" section
- Do not run file system commands that modify state during context loading
