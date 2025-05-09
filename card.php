<?php
/* Copyright (C) 2025		MDW	<mdeweerd@users.noreply.github.com>
 */
// Load Dolibarr environment

if (false === (@include_once '../../main.inc.php')) {  // From htdocs directory
	include_once '../../../main.inc.php'; // From "custom" directory
}

global $db, $langs, $user;

// Load translation files required by the page
$langs->loadLangs(array("companies", "bills", "payments"));

$client_id = GETPOST('id', 'int');

$result = restrictedArea($user, 'societe', '', '');

// Load the ExtraitCompte class
require_once DOL_DOCUMENT_ROOT.'/custom/extraitcompte/class/ExtraitCompte.class.php';
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
llxHeader('', 'Extrait de Compte');

  // $linkback = '<a href="'.DOL_URL_ROOT.'/comm/card.php?soc_id='.$client_id.'">'.$langs->trans("BackToList").'</a>';
  $linkback = '';

 print dol_get_fiche_head($head, 'extraitcompte', $langs->trans("ExtraitCompte"), -1, 'company');
    dol_banner_tab($thirdparty, 'socid', $linkback, 0/*($user->socid ? 0 : 1)*/, 'rowid', 'nom');



//    print dol_get_fiche_head($head, 'extraitcompte', $langs->trans("VATPayment"), -1, 'company', 0, '', '', 0, '', 1);


// Display the extract
$extraitCompte->displayExtraitCompte($client_id);

// Display the footer with the tab list
print dol_get_fiche_end();
llxFooter();

$db->close();
