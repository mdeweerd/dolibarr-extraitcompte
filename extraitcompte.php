<?php
// Load Dolibarr environment

if (false === (@require_once '../../main.inc.php')) {  // From htdocs directory
    require_once '../../../main.inc.php'; // From "custom" directory
}
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/client.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';

global $db, $langs, $user;

// Load translation files required by the page
$langs->loadLangs(array("companies", "bills", "payments"));

$client_id = GETPOST('id', 'int');

// Fetch client information
$client = new Societe($db);
if ($client->fetch($client_id) <= 0) {
    dol_print_error($db, $client->error);
    exit;
}

$facture_static = new Facture($db);
$invoices = $facture_static->liste_array(0, 0, null, $client_id);

// Sort invoices by date
usort($invoices, function($a, $b) {
    return strtotime($a['date']) - strtotime($b['date']);
});

// Display the extract
llxHeader('', 'Extrait de Compte');

print '<h1>Extrait de Compte pour ' . $client->nom . '</h1>';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>Date</td>';
print '<td>Référence</td>';
//print '<td>Libellé</td>';
print '<td>Montant HT</td>';
print '<td>Montant TVA</td>';
print '<td>Montant TTC</td>';
print '<td>Montant Payé</td>';
print '<td>Montant Restant Dû</td>';
print '<td>Statut</td>';
print '<td>Paiements</td>';
print '</tr>';

$totalDue = 0;
foreach (array_reverse($invoices) as $invoice) {
    $invoiceObj = new Facture($db);
    $invoiceObj->fetch($invoice['id']);

    // Fetch payments for the invoice
    $payments = $invoiceObj->getListOfPayments();

	$paid_total = $invoiceObj->getSommePaiement();
    // Calculate paid amounts
    $paid = 0;
	$infoPaiement = '';
	// array(7) { ["amount"]=> string(13) "3000.00000000" ["type"]=> string(3) "VIR" ["date"]=> string(19) "2022-09-13 12:00:00" ["num"]=> string(0) "" ["ref"]=> string(12) "PAY2209-0076" ["ref_ext"]=> string(0) "" ["fk_bank_line"]=> string(3) "182" }
    foreach ($payments as $payment) {
        $paid += $payment['amount'];
		$infoPaiement .= '<div>'. $payment['ref'] ." - <b>".dol_print_date($payment['date'], 'day') . '</b> - ' /*. $payment["type"]." - "*/.price($payment['amount']) .'</div>';
    }

    // Calculate remaining amount due
    // $remaining_due = $invoiceObj->total_ttc - ($paid_ht + $paid_vat);
    $remaining_due = $invoiceObj->total_ttc - $paid;

    print '<tr>';
    print '<td>' . dol_print_date($invoiceObj->date, 'day') . '</td>';
    print '<td>' . $invoiceObj->ref . '</td>';
    // print '<td>' . dol_escape_htmltag($invoiceObj->libelle) . '</td>';
    print '<td>' . price($invoiceObj->total_ht) . '</td>';
    print '<td>' . price($invoiceObj->total_tva) . '</td>';
    print '<td>' . price($invoiceObj->total_ttc) . '</td>';
    print '<td>' . price($paid) . '</td>';
    print '<td>' . price($remaining_due) . '</td>';
    print '<td>' . $invoiceObj->getLibStatut(1) . '</td>';
    print '<td>';
	print empty($infoPaiement) ? 'Aucun paiement' : $infoPaiement;
    print '</td>';
    print '</tr>';
}

print '</table>';

llxFooter();
$db->close();

