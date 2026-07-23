---
name: openspec-propose
description: Propose a new change in the OpenSpec workflow. Use when the user wants to start a new feature, improvement, or bugfix and wants to define its requirements and design first.
license: MIT
compatibility: Requires openspec CLI.
metadata:
  author: openspec
  version: "1.0"
  generatedBy: "1.5.0"
---

Propose a new change in the OpenSpec workflow.

This is an **agent-driven** operation. You will create a change proposal directory using the OpenSpec CLI and guide the user through defining its artifacts (proposal, specifications, design, and tasks) before starting code implementation.

**Store selection:** If the user names a store (a store is a standalone OpenSpec repo registered on this machine) or the work lives in one, run `openspec store list --json` to discover registered store ids, then pass `--store <id>` on the commands that read or write specs and changes (`new change`, `status`, `instructions`, `list`, `show`, `validate`, `archive`, `doctor`, `context`). Other commands do not take the flag. Hints printed by commands already carry the flag; keep it on follow-ups. Without a store, commands act on the nearest local `openspec/` root.

**Steps**

1. **Resolve change name and details**
   
   Ask the user for the name and goals of the proposed change if they haven't provided them already.
   Ensure the name is simple, lowercase, and uses kebab-case (e.g., `add-approval-fields`).

2. **Initialize the change**

   Run the CLI command to initialize a new change under the default schema (`spec-driven`):
   ```bash
   openspec new change "<change-name>" --schema spec-driven
   ```
   *Optionally*, pass `--description "<desc>"` or `--goal "<goal>"` if available.

3. **Iterate to complete artifacts**

   A `spec-driven` workflow requires completing the following artifacts in order:
   `proposal` ➔ `specs` ➔ `design` ➔ `tasks`.

   For each artifact:
   
   a. **Get instructions:**
      Run:
      ```bash
      openspec instructions <artifact> --change "<change-name>" --json
      ```
      Use the output to understand what is required for this phase.

   b. **Read/Analyze code context:**
      Explore the codebase to gather context, check existing implementations, or verify the design points requested.

   c. **Create/Update the artifact:**
      Edit the respective file in the change directory:
      - `openspec/changes/<change-name>/proposal.md`
      - `openspec/changes/<change-name>/specs/<capability>/spec.md` (delta specs)
      - `openspec/changes/<change-name>/design.md`
      - `openspec/changes/<change-name>/tasks.md`

   d. **Validate the change status:**
      Run:
      ```bash
      openspec validate "<change-name>"
      ```
      And check the completion state:
      ```bash
      openspec status --change "<change-name>" --json
      ```
      Verify if the artifact is now in state `done`. If yes, proceed to the next artifact in the graph.

4. **Show summary to the user**

   Once all artifacts (`proposal`, `specs`, `design`, `tasks`) are completed (`done`), present a summary to the user including:
   - The scope of the proposal.
   - The specifications introduced.
   - The generated tasks list.
   
   Suggest moving to the implementation phase using `/opsx-apply <change-name>` or the `openspec-apply-change` skill.

**Output On Success**

```
## Change Proposed: <change-name>

All artifacts are now ready for implementation:
- **Proposal**: Done (Scope defined)
- **Specifications**: Done (Deltas specified)
- **Design**: Done (Technical solution drafted)
- **Tasks**: Done (<N> tasks generated in tasks.md)

You can start implementing this change by running the command:
`/opsx-apply <change-name>`
```

**Guardrails**
- Never write application code during the proposal phase.
- Always run `openspec validate` before considering an artifact complete.
- Follow the sequence of artifacts indicated by the status graph.
