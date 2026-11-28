# LLM prompt — centralize main.inc.php loading in a Dolibarr module

Use this prompt in any Dolibarr module repository where pages fail with
`include_once('../../../main.inc.php'): Failed to open stream` (or the
follow-up `Call to a member function loadLangs() on null`), because each
page resolves `main.inc.php` through paths relative to its own directory.

```
In this Dolibarr module, every web entry page (root-level *.php pages and
admin/*.php pages) currently locates htdocs/main.inc.php itself, through
relative paths such as '../../main.inc.php' or a copied multi-path
fallback block. That breaks when a page is reached from a different
depth: PHP emits "include_once(...main.inc.php): Failed to open stream"
and then "Call to a member function loadLangs() on null".

Do exactly this:

1. Create ONE new file `core/bootstrap.php` with EXACTLY this content
   (do not modify, do not "improve"):

---8<--- core/bootstrap.php ---8<---
<?php
/**
 * \file    core/bootstrap.php
 * \brief   Single entry point that loads the Dolibarr environment
 *          (main.inc.php) for every page of the module. Pages include
 *          this file through a path relative to their own directory:
 *
 *              require_once __DIR__.'/core/bootstrap.php';        (module root)
 *              require_once __DIR__.'/../core/bootstrap.php';     (one level down)
 *
 *          All lookups below are anchored to this file's __DIR__, never
 *          to the including page, so the depth of the page that
 *          includes this file cannot break the resolution.
 */

/**
 * Longest common leading path of $pathA and $pathB, always ending on a
 * '/' segment boundary, or null when they share no path segment.
 */
function common_path_prefix(string $pathA, string $pathB): ?string
{
	$div = strspn(($pathA."\0") ^ ($pathB."\1"), "\0"); // first difference, XOR with sentinels
	$cut = strrpos(substr($pathA, 0, $div), '/');      // last '/' before the difference
	return $cut ? substr($pathA, 0, $cut + 1) : null;  // common path incl. '/', null if none
}

// Potential locations of "main.inc.php", in order; each ends with '/', null entries are skipped
$scriptRoot = isset($_SERVER['SCRIPT_FILENAME']) ? common_path_prefix($_SERVER['SCRIPT_FILENAME'], (string) realpath(__FILE__)) : null;
$candidates = [
	!empty($_SERVER["CONTEXT_DOCUMENT_ROOT"]) ? $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/" : null, // web root known to the server
	$scriptRoot,                                  // web root from SCRIPT_FILENAME vs this file, '/' terminated
	$scriptRoot ? dirname($scriptRoot)."/" : null, // its parent
	__DIR__."/../../",
	__DIR__."/../../../",
];

$res = 0;
foreach ($candidates as $dir) {
	if ($dir && ($res = @include $dir."main.inc.php")) break; // @include returns false on a missing file
}
if (!$res) die("Include of main fails");
---8<--------------------------------------

2. In every web entry page, delete its own main.inc.php resolution block
   and replace it with a single __DIR__-relative include of the shared
   file: `require_once __DIR__.'/core/bootstrap.php';` at module root,
   `require_once __DIR__.'/../core/bootstrap.php';` one level down.

3. In the module descriptor class (`core/modules/mod*.class.php`), replace
   any hardcoded module-name URL (e.g. a tab entry pointing at
   `/<modulename>/card.php`) with the module's directory name derived
   locally — do NOT use a shared constant or DOL_DOCUMENT_ROOT:

       $moduledir = basename(dirname(__DIR__, 2)); // module dir name, two parents up
       $taburl = '/'.$moduledir.'/card.php?id=__ID__';

4. Add `test/phpunit/BootstrapIncludeTest.php` (PHPUnit 9, class extending
   PHPUnit\Framework\TestCase) that fails when: a page resolves
   'main.inc.php' itself, a page does not include the bootstrap via a
   __DIR__-relative path, or core/bootstrap.php lacks a __DIR__-anchored
   candidate. The CI runs each test/phpunit/*Test.php file.

5. Run `php -l` on every changed file and the new PHPUnit test, and fix
   anything they report.

6. Add any files downloaded or generated for testing/CI to `.gitignore`
   (e.g. `dolibarr/`, `dolibarr.tar.gz`, `phan.phar`, `phpstan.phar`,
   `*.swp`) so `git status` stays clean after a local test run.

Do not touch other class files, CI scripts, or anything unrelated. Keep
the diff minimal: one new bootstrap file, one include line per page, the
moduledir variable in the descriptor, one test file, .gitignore entries.
```
