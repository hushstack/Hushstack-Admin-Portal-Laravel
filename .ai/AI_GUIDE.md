# Universal AI Guide - How To Use This `.ai/` Folder

This guide works for **any AI tool**: Cascade, Codex CLI, Gemini CLI, Claude, etc.

## When You Start

**ALWAYS read these files in order:**

1. `.ai/README.md` - What's in this folder
2. `.ai/PROJECT_CONTEXT.md` - Project overview
3. `.ai/ARCHITECTURE.md` - Technical architecture
4. `.ai/CURRENT_STATE.md` - What we're working on now
5. `.ai/TASKS.md` - Todo list

## When User Says "I want new feature about..."

**Automatically do this:**

1. Read `.ai/ROADMAP.md` and `.ai/TASKS.md`
2. Add the feature to `ROADMAP.md` under the right phase
3. Add to `TASKS.md` Todo section with format:
   ```
   - [YYYY-MM-DD] Feature: [name] - [description]
   ```
4. Tell the user: "Feature captured in `.ai/ROADMAP.md` and `.ai/TASKS.md`"

## When You Finish Coding

**Update these files:**

- `.ai/CURRENT_STATE.md` - Update current work status
- `.ai/TASKS.md` - Move items from Todo to Done
- `.ai/DECISIONS.md` - If you made important technical decisions
- `.ai/AGENT_HANDOFF.md` - If switching to another AI

## File Purposes

| File | Purpose | Read First? | Update After? |
|------|---------|-------------|---------------|
| README.md | How to use `.ai/` folder | ✓ | |
| PROJECT_CONTEXT.md | Project overview | ✓ | |
| ARCHITECTURE.md | Tech stack, structure | ✓ | |
| CODING_RULES.md | Code style rules | ✓ | |
| SECURITY_RULES.md | Security requirements | ✓ | |
| CURRENT_STATE.md | Current work status | ✓ | ✓ |
| ROADMAP.md | Future plans | ✓ (capture features) | ✓ |
| TASKS.md | Todo/Done lists | ✓ | ✓ |
| DECISIONS.md | Key decisions | | ✓ |
| AGENT_HANDOFF.md | Notes for next AI | | ✓ |

## Quick Reference

```bash
# Read order (do this first)
cat .ai/README.md .ai/PROJECT_CONTEXT.md .ai/CURRENT_STATE.md .ai/TASKS.md

# When feature requested
echo "- [$(date +%Y-%m-%d)] Feature: [name] - [desc]" >> .ai/TASKS.md
```

## Rules

- **Read before coding** - Always load context first
- **Capture features** - Auto-note any "I want..." requests
- **Update after coding** - Keep docs current with your work
- **Be minimal** - Don't dump entire conversations, just capture the essence
