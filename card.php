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

// Display the header with the tab list
llxHeader('', 'Extrait de Compte');
print dol_get_fiche_head(prepareHead($extraitCompte, $client_id), 'extraitcompte', $langs->trans("ExtraitCompte"), 0, 'bill');

// Display the extract
$extraitCompte->displayExtraitCompte($client_id);

// Display the footer with the tab list
print dol_get_fiche_end();
llxFooter();

$db->close();

function prepareHead($extraitCompte, $client_id) {
    global $langs;

    $head = array();
    $head[] = array(
        'text' => $langs->trans("ExtraitCompte"),
        'url' => dol_buildpath('/custom/extraitcompte/card.php', 1) . '?id=' . $client_id,
        'active' => 1
    );

    // Add other tabs if needed
    // $head[] = array(...);

    return $head;
}
