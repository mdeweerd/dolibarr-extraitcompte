<?php
/* Copyright (C) 2026 MDW <mdeweerd@users.noreply.github.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */
/**
 * \file    test/phpunit/BootstrapIncludeTest.php
 * \brief   Detects the "include_once('../../../main.inc.php'): Failed to
 *          open stream" class of bug: a module page that resolves
 *          main.inc.php itself, through paths relative to its own
 *          directory, breaks when the page is reached from another
 *          depth. Every page must include the shared core/bootstrap.php
 *          through a __DIR__-relative path instead, and the bootstrap
 *          must anchor its own lookups to its __DIR__.
 */

use PHPUnit\Framework\TestCase;

class BootstrapIncludeTest extends TestCase
{
	/** Module root, two levels up from this file. */
	private function moduleRoot(): string
	{
		return dirname(__DIR__, 2);
	}

	/** Web entry points of the module: root pages and admin pages. */
	private function pageFiles(): array
	{
		$root = $this->moduleRoot();
		$files = glob($root.'/*.php') ?: [];
		foreach (glob($root.'/admin/*.php') ?: [] as $admin) {
			$files[] = $admin;
		}
		return $files;
	}

	public function testBootstrapFileExists(): void
	{
		$this->assertFileExists($this->moduleRoot().'/core/bootstrap.php');
	}

	public function testPagesIncludeSharedBootstrapViaDirRelativePath(): void
	{
		$pages = $this->pageFiles();
		$this->assertNotEmpty($pages, 'no module page found to check');
		foreach ($pages as $page) {
			$code = file_get_contents($page);
			$this->assertMatchesRegularExpression(
				'/require_once\s+__DIR__\s*\.\s*[\'"][^\'"]*bootstrap\.php[\'"]/',
				$code,
				basename($page).' must include the shared bootstrap via a __DIR__-relative path'
			);
		}
	}

	public function testPagesDoNotResolveMainIncThemselves(): void
	{
		foreach ($this->pageFiles() as $page) {
			$code = file_get_contents($page);
			$this->assertDoesNotMatchRegularExpression(
				'/main\.inc\.php/',
				$code,
				basename($page).' resolves main.inc.php itself; it must go through core/bootstrap.php'
			);
		}
	}

	public function testBootstrapAnchorsRelativeLookupsToItsOwnDir(): void
	{
		$code = file_get_contents($this->moduleRoot().'/core/bootstrap.php');
		$this->assertMatchesRegularExpression(
			'/__DIR__\s*\.\s*[\'"]\/\.\.\/\.\.\/?[\'"]/',
			$code,
			'core/bootstrap.php must anchor its relative candidates to its own __DIR__, not the including page'
		);
	}
}
