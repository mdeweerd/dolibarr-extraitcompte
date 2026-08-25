<?php
/* Copyright (C) 2025-2026	MDW	<mdeweerd@users.noreply.github.com>
 */
// Load Dolibarr environment

if (false === (@include_once '../../main.inc.php')) {  // From htdocs directory
	include_once '../../../main.inc.php'; // From "custom" directory
}

global $db, $langs, $user, $conf;

// Load translation files required by the page
$langs->loadLangs(array("companies", "bills", "payments"));

$client_id = GETPOST('id', 'int');
$start_date = GETPOST('start_date', 'alpha');
$all_dates = GETPOST('all_dates', 'alpha');

$result = restrictedArea($user, 'societe', '', '');

// Load the ExtraitCompte class
require_once __DIR__.'/class/ExtraitCompte.class.php';
$extraitCompte = new ExtraitCompte($db);

// Include the library for dol_get_fiche_head
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';


$thirdparty = new Societe($db);
$thirdparty->id = $client_id;
$thirdparty->fetch($client_id);

$head = societe_prepare_head($thirdparty);
$title = $langs->trans("ThirdParty");
$picto = 'company';

// Display the header with the tab list
llxHeader('', $langs->trans('ExtraitCompte'));

  // $linkback = '<a href="'.DOL_URL_ROOT.'/comm/card.php?soc_id='.$client_id.'">'.$langs->trans("BackToList").'</a>';
  $linkback = '';

 print dol_get_fiche_head($head, 'extraitcompte', $langs->trans("ExtraitCompte"), -1, 'company');
    dol_banner_tab($thirdparty, 'socid', $linkback, ($user->socid ? 0 : 1), 'rowid', 'nom');

// Display start date filter form
print '<div class="fichecenter">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?id='.$client_id.'" class="form-inline">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("StartDate").'</td>';
print '<td>';
print '<input type="date" name="start_date" class="flat" value="'.($start_date ? $start_date : '').'">';
print '</td>';
print '<td>';
print '<input type="submit" class="button" value="'.$langs->trans("Refresh").'">';
print '<input type="submit" name="all_dates" class="button" value="'.$langs->trans("All").'">';
print '<button type="button" class="button" onclick="copyTableToClipboard()">'.$langs->trans("Copy").'</button>';
print '</td>';
print '</tr>';
print '</table>';
print '</div>';
print '</form>';
print '</div>';

// Print styles - only show the extract content when printing
print '<style type="text/css" media="print">';
print '  html, body { background: white !important; color: black !important; }';
print '  #id-container > .side-nav, header, nav, footer, .tabs, .fichecenter, .fichehead, .ficheend, .footpart, .menubar, .topmenu, .leftmenu, #tmenu_tooltip, #dialogforpopup, #page_y, .arearef, .underrefbanner, br { display: none !important; }';
print '  #extraitcompte-tab-content { margin: 0 !important; padding: 10px !important; width: 167% !important; transform: scale(0.6); transform-origin: top left; }';
print '  #extraitcompte-tab-content h1 { margin: 0 0 10px 0 !important; }';
print '  #extraitcompte-tab-content table { border-collapse: collapse !important; }';
print '  #extraitcompte-tab-content tr { page-break-inside: avoid; }';
print '</style>';

print '<br>';



//    print dol_get_fiche_head($head, 'extraitcompte', $langs->trans("VATPayment"), -1, 'company', 0, '', '', 0, '', 1);


// Display the extract
$extraitCompte->displayExtraitCompte($client_id, $start_date, $all_dates);

// Display the footer with the tab list
print dol_get_fiche_end();

// Copy to clipboard JavaScript with CSV escaping for line breaks in cells
print '<script>';
print 'function escapeCsvField(text) {';
print '  var dq = String.fromCharCode(34);';
print '  text = text.replace(new RegExp(dq, "g"), dq + dq);';
print '  if (text.indexOf("\n") >= 0 || text.indexOf("\t") >= 0 || text.indexOf(dq) >= 0) {';
print '    return dq + text + dq;';
print '  }';
print '  return text;';
print '}';
print '';
print 'function copyTableToClipboard() {';
print '  var contentDiv = document.getElementById("extraitcompte-tab-content");';
print '  if (contentDiv) {';
print '    var table = contentDiv.querySelector("table");';
print '    if (table) {';
print '      var text = "";';
print '      var title = contentDiv.querySelector("h1");';
print '      if (title) {';
print '        text = escapeCsvField(title.textContent.trim());';
print '      }';
print '      var rows = table.querySelectorAll("tr");';
print '      rows.forEach(function(row) {';
print '        var cells = row.querySelectorAll("td, th");';
print '        if (cells.length) {';
print '          var rowText = Array.from(cells).map(function(cell) {';
print '            return escapeCsvField(cell.textContent.trim());';
print '          }).join("\t");';
print '          if (text) text += "\n";';
print '          text += rowText;';
print '        }';
print '      });';
print '      if (text) {';
print '        navigator.clipboard.writeText(text).then(function() {';
print '          alert("'.$langs->trans("CopiedToClipboard").'");';
print '        });';
print '      }';
print '    }';
print '  }';
print '}';
print '</script>';

llxFooter();

$db->close();
