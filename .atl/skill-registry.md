# Skill Registry

**Delegator use only.** Any agent that launches sub-agents reads this registry to resolve compact rules, then injects them directly into sub-agent prompts. Sub-agents do NOT read this registry or individual SKILL.md files.

See `_shared/skill-resolver.md` for the full resolution protocol.

## User Skills

| Trigger | Skill | Path |
|---------|-------|------|
| When creating a pull request, opening a PR, or preparing changes for review. | branch-pr | C:/Users/FER/.config/opencode/skills/branch-pr/SKILL.md |
| When creating a GitHub issue, reporting a bug, or requesting a feature. | issue-creation | C:/Users/FER/.config/opencode/skills/issue-creation/SKILL.md |
| When writing Go tests, using teatest, or adding test coverage. | go-testing | C:/Users/FER/.config/opencode/skills/go-testing/SKILL.md |
| When user asks to create a new skill, add agent instructions, or document patterns for AI. | skill-creator | C:/Users/FER/.config/opencode/skills/skill-creator/SKILL.md |
| When user says "judgment day", "judgment-day", "review adversarial", "dual review", "doble review", "juzgar", "que lo juzguen". | judgment-day | C:/Users/FER/.config/opencode/skills/judgment-day/SKILL.md |

## Compact Rules

Pre-digested rules per skill. Delegators copy matching blocks into sub-agent prompts as `## Project Standards (auto-resolved)`.

### branch-pr
- Every PR MUST link an approved issue and have exactly one `type:*` label.
- Branch names MUST follow `type/description` with allowed types like feat, fix, chore.
- Commit messages MUST follow Conventional Commits regex and map to PR labels.
- Use the PR template, including Linked Issue, PR Type, Summary, Changes Table, and Test Plan.
- Run shellcheck on modified scripts before pushing.

### issue-creation
- All issues MUST use a template; blank issues are not allowed.
- New issues start with `status:needs-review` and require `status:approved` before PRs.
- Use Bug Report for bugs and Feature Request for enhancements; questions go to Discussions.
- Fill in ALL required fields, including pre-flight checks and detailed descriptions.

### go-testing
- Prefer table-driven tests for Go functions with multiple cases.
- Test Bubbletea TUIs by exercising Model.Update and state transitions.
- Use teatest for integration-style TUI flows when needed.
- Use golden files for view output; update with a flag when expectations change.

### skill-creator
- Create skills only for reusable patterns or project-specific conventions.
- Follow the SKILL.md template with complete frontmatter and clear triggers.
- Keep examples minimal and focused; link to local docs via references/ when needed.
- Name skills using `{technology}` or `{project}-{component}` patterns.

### judgment-day
- Always run two independent judge agents in parallel for adversarial review.
- Judges only review and classify findings; a separate Fix Agent applies changes.
- Classify warnings as real vs theoretical and only re-judge for confirmed critical issues.
- Stop after two fix iterations unless the user explicitly wants more.

## Project Conventions

| File | Path | Notes |
|------|------|-------|
| AGENTS.md | C:/laragon/www/ConsultApp/consultapp/AGENTS.md | Index — project setup, commands, and test defaults |
