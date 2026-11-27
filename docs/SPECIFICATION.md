# Specification - extraitcompte

Baseline specification of the existing behaviour of the ExtraitCompte
module (1.0), written when the CI was set up: the document states what
exists today and is the reference for every commit and test. Anything
not stated here is out of scope.

## Purpose and context

ExtraitCompte adds an account extract tab to third parties: the
invoices and payments of a client from a start date, with the balance
and a copy/print presentation. It is a Dolibarr module (descriptor:
core/modules/modExtraitCompte.class.php, module id 104201) deployed as
htdocs/custom/extraitcompte.

## Hard constraints

- Target platform: current Dolibarr (the CI exercises it against
  24.x with PHP 8.3).
- No SQL schema of its own (sql/extraitcompte.sql is a placeholder); no
  core file modification; the tab and the pages only.
- Activation must stay replayable (idempotent statements and guards).

## Roles

- Dolibarr administrator: activates the module, reads the admin pages.
- User holding the extraitcompte read right: opens the tab of a third
  party.

## Functional requirements

- FR-1 Third-party tab "ExtraitCompte" on every societe card
  (descriptor tabs), gated by the module right, pointing to
  /extraitcompte/card.php?id=__ID__.
- FR-2 Account extract page (card.php): invoices and payments of the
  client from a start date, with the running balance
  (class/ExtraitCompte.class.php, displayExtraitCompte).
- FR-3 Start date filter: a date field on the page (start_date), an
  "all dates" mode (all_dates); the default start is the first day of
  the previous quarter (getFirstDayOfPreviousTrimester).
- FR-4 Backtrack rule: when an unpaid invoice predates the start date,
  the extract starts at that invoice date instead.
- FR-5 Copy button (clipboard) and print stylesheet on the extract
  table.
- FR-6 Admin page (admin/extraitcompte.php) with the module status;
  about tab (admin/about.php).
- FR-7 Permission: "Lire les extraits de compte" (extraitcompte read).

## Non-functional requirements

- NFR-1 Activation replay: activating an already-activated module is a
  no-op without error.
- NFR-2 CSRF: every POST form carries the token.
- NFR-3 Translations: every UI string in the lang files (fr_FR first,
  en_US second); no hardcoded string in the pages.
- NFR-4 CI: lint (php -l, pre-commit), static analysis (phpstan, phan,
  from generated baselines), instance tests and page smoke against a
  real Dolibarr - see .github/workflows.

## Acceptance criteria summary

Activation on a Dolibarr 24.0.4 demo instance succeeds; the extract tab
page of a demo third party and the admin pages answer 200 with their
marker and no PHP error; the pre-commit hooks pass over the whole tree;
the analysers pass from their committed baselines and fail on new
findings.

## Out of scope

- PDF or export formats of the extract.
- Objects other than client invoices and payments (credits, standing
  orders, supplier side).
- Multi-entity specifics.
- Verifying the print rendering pixel-perfect in CI (the page is
  smoked, the printed layout is not).
