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

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root caluclated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && @file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include substr($tmp, 0, ($i+1))."/main.inc.php";
if (! $res && $i > 0 && @file_exists(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php")) $res=@include dirname(substr($tmp, 0, ($i+1)))."/main.inc.php";
// Try main.inc.php using relative path
if (! $res && file_exists("../../main.inc.php")) $res=@include "../../main.inc.php";
if (! $res && file_exists("../../../main.inc.php")) $res=@include "../../../main.inc.php";
if (! $res && file_exists("../../../../main.inc.php")) $res=@include "../../../../main.inc.php";
if (! $res && file_exists("../../../../../main.inc.php")) $res=@include "../../../../../main.inc.php";
if (! $res) die("Include of main fails");

// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";

// Load module class
require_once '../core/modules/modExtraitCompte.class.php';

// Translations
$langs->loadLangs(array("admin", "extraitcompte@extraitcompte"));

// Access control
if (!$user->admin) accessforbidden();

// Parameters
$action = GETPOST('action', 'aZ09');
$modulename = 'extraitcompte';


/*
 * Actions
 */

if ($action == 'enable') {
    dolibarr_set_const($db, 'MAIN_MODULE_'.strtoupper($modulename), 1, 'chaine', 0, '', $conf->entity);
    $conf->modules[$modulename] = 1;
    setEventMessages($langs->trans('ModuleEnabled'), null, 'mesgs');
} elseif ($action == 'disable') {
    dolibarr_set_const($db, 'MAIN_MODULE_'.strtoupper($modulename), 0, 'chaine', 0, '', $conf->entity);
    $conf->modules[$modulename] = 0;
    setEventMessages($langs->trans('ModuleDisabled'), null, 'mesgs');
}


/*
 * View
 */
$title = $langs->trans("Module104201Name");
$picto = 'generic';

llxHeader('', $title);

// Prepare head array
$h = 0;
$head = array();

$head[$h][0] = $_SERVER["PHP_SELF"];
$head[$h][1] = $langs->trans("ModuleSetup");
$head[$h][2] = 'settings';
$h++;

$head[$h][0] = 'about.php';
$head[$h][1] = $langs->trans("About");
$head[$h][2] = 'about';
$h++;

dol_fiche_head($head, 'settings', '', (((float) DOL_VERSION <= 8)?0:-1), $picto);

// Module title
$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("Module104201Name"), $linkback, $picto);

// Configuration message
print '<div class="info">';
print $langs->trans("Module104201Desc");
print '</div>';
print '<br>';

// Module status
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("ModuleStatus").'</td>';
print '<td>';
if (isModEnabled($modulename)) {
    print '<a href="'.$_SERVER["PHP_SELF"].'?action=disable&token='.newToken().'">';
    print img_picto($langs->trans("Enabled"), 'switch_on');
    print '</a>';
} else {
    print '<a href="'.$_SERVER["PHP_SELF"].'?action=enable&token='.newToken().'">';
    print img_picto($langs->trans("Disabled"), 'switch_off');
    print '</a>';
}
print '</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("ModuleVersion").'</td>';
print '<td>';
if (isModEnabled($modulename)) {
    $module = new modExtraitCompte($db);
    print $module->version;
}
print '</td>';
print '</tr>';

print '</table>';

dol_fiche_end();

llxFooter();

$db->close();
