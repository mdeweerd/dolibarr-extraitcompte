<?php
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
$title=$langs->trans("ThirdParty");
$picto='company';

// Display the header with the tab list
llxHeader('', 'Extrait de Compte');
print dol_fiche_head($head, 'extraitcompte', $langs->trans("ExtraitCompte"), 0, 'company');

// Display the extract
$extraitCompte->displayExtraitCompte($client_id);

// Display the footer with the tab list
print dol_get_fiche_end();
llxFooter();

$db->close();
