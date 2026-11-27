# Plan

## Phase 0 - CI baseline (this branch)

- [ ] CI files of the bootstrap commit: lint.yml (php -l, pre-commit),
      analyse.yml (phpstan, phan), test.yml (instance tests, page
      smoke), .ci/scripts, phpstan.neon.dist, .phan/config.php, the
      placeholder phpstan-baseline.neon, docs/SPECIFICATION.md, TODO.md
      and the project skill.
      Acceptance: every job starts; the analysers generate their
      baselines and fail exactly once on purpose.
      Done (bootstrap commit): the files landed; the whitespace and
      end-of-file cleanup of the existing files made the pre-commit
      fixers pass from the first run.
- [ ] Commit the generated baselines from the artifacts
      (phpstan-baseline.neon, .phan/baseline.txt) and re-run.
      Acceptance: analyse.yml green, failing only on new findings.
- [ ] Run the whole pipeline on the branch, fix what it reveals, then
      open the pull request to main.
      Acceptance: lint and test green; analyse green from the
      baselines.
- [ ] Cover the about tab (admin/about.php) with a stable marker once
      one is chosen on the rendered page.
      Acceptance: the smoke asserts it.

## Phase 1 - First instance tests

- [ ] test/phpunit with the platform bootstrap (TARGET_HTDOCS resolved
      to the htdocs of the CI instance, master.inc.php, user 1 with the
      module rights): an activation replay test first (module off,
      module on, pages still open), then one test file per behaviour.
      Acceptance: one process per file, everything touched restored in
      tearDown, no assertion on hardcoded rowids of the reference
      dataset.

## Phase 2 - Port backlog

- [ ] Lift the baselined findings by area (descriptor, class, pages,
      templates), regenerating the baselines as they shrink: the
      baseline size is the progress bar of the port.

## Later / on demand

- A matrix leg on another Dolibarr series, if the module must support
  one.
- A test of the quarter-backtrack rule (FR-4) against seeded invoice
  dates, as a PHPUnit file with its own fixtures.
