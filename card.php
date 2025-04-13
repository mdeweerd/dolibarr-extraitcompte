<?php
// Load Dolibarr environment

if (false === (@include_once '../../main.inc.php')) {  // From htdocs directory
    include_once '../../../main.inc.php'; // From "custom" directory
}

global $db, $langs, $user;

// Load translation files required by the page
$langs->loadLangs(array("companies", "bills", "payments"));

$client_id = GETPOST('id', 'int');

// Load the ExtraitCompte class
require_once DOL_DOCUMENT_ROOT.'/custom/extraitcompte/class/ExtraitCompte.class.php';
$extraitCompte = new ExtraitCompte($db);

// Display the extract
llxHeader('', 'Extrait de Compte');
$extraitCompte->displayExtraitCompte($client_id);
llxFooter();

$db->close();

