<?php
/* Copyright (C) 2025-2026	MDW	<mdeweerd@users.noreply.github.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
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

// Longest common leading path of $pathA and $pathB, always ending on a
// '/' segment boundary, or null when they share no path segment.
$commonPathPrefix = function (string $pathA, string $pathB): ?string {
	$div = strspn(($pathA."\0") ^ ($pathB."\1"), "\0"); // first difference, XOR with sentinels
	$cut = strrpos(substr($pathA, 0, $div), '/');      // last '/' before the difference
	return $cut ? substr($pathA, 0, $cut + 1) : null;  // common path incl. '/', null if none
};

// Potential locations of "main.inc.php", in order; each ends with '/', null entries are skipped
$scriptRoot = isset($_SERVER['SCRIPT_FILENAME']) ? $commonPathPrefix($_SERVER['SCRIPT_FILENAME'], (string) realpath(__FILE__)) : null;
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
