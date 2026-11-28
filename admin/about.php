<?php
/* Copyright (C) 2025		MDW	<mdeweerd@users.noreply.github.com>
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

// Load Dolibarr environment
require_once __DIR__.'/../core/bootstrap.php';

// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";

// Load module class
require_once '../core/modules/modExtraitCompte.class.php';

// Translations
$langs->loadLangs(array("admin", "extraitcompte@extraitcompte"));

// Access control
if (!$user->admin) accessforbidden();


/*
 * View
 */

$modulename = 'extraitcompte';
$title = $langs->trans("Module104201Name");
$picto = 'generic';

llxHeader('', $title);

// Prepare head array
$h = 0;
$head = array();

$head[$h][0] = 'extraitcompte.php';
$head[$h][1] = $langs->trans("ModuleSetup");
$head[$h][2] = 'settings';
$h++;

$head[$h][0] = $_SERVER["PHP_SELF"];
$head[$h][1] = $langs->trans("About");
$head[$h][2] = 'about';
$h++;

print dol_get_fiche_head($head, 'about', '', -1, $picto);

// Module title
$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("About"), $linkback, $picto);

print '<table class="noborder" width="100%">';
print '<tr class="oddeven">';
print '<td width="20%">'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Module104201Name").'</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("Description").'</td>';
print '<td>'.$langs->trans("Module104201Desc").'</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("Version").'</td>';
print '<td>';
if (isModEnabled($modulename)) {
    $module = new modExtraitCompte($db);
    print $module->version;
}
print '</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("License").'</td>';
print '<td>GPL v3 or later</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("Author").'</td>';
print '<td>MDW</td>';
print '</tr>';

print '</table>';

print dol_get_fiche_end();

llxFooter();

$db->close();
