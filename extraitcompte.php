<?php
// Load Dolibarr environment

if (false === (@include_once '../../main.inc.php')) {  // From htdocs directory
    include_once '../../../main.inc.php'; // From "custom" directory
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
    setEventMessages($client->error, $client->errors, 'errors');
    exit;
}

$facture_static = new Facture($db);
$invoices = $facture_static->liste_array(0, 0, null, $client_id);

if (!is_array($invoices)) {
    setEventMessages($facture_static->error, $facture_static->errors, 'errors');
    exit;
}

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
    if ($invoiceObj->fetch($invoice['id']) <= 0) {
        setEventMessages($invoiceObj->error, $invoiceObj->errors, 'errors');
        continue;
    }

    // Fetch payments for the invoice
    $payments = $invoiceObj->getListOfPayments();

    if (!is_array($payments)) {
        setEventMessages($invoiceObj->error, $invoiceObj->errors, 'errors');
        continue;
    }

    $paid_total = $invoiceObj->getSommePaiement();
    // Calculate paid amounts
    $paid = 0;
    $infoPaiement = '';
    // array(7) { ["amount"]=> string(13) "3000.00000000" ["type"]=> string(3) "VIR" ["date"]=> string(19) "2022-09-13 12:00:00" ["num"]=> string(0) "" ["ref"]=> string(12) "PAY2209-0076" ["ref_ext"]=> string(0) "" ["fk_bank_line"]=> string(3) "182" }
    foreach ($payments as $payment) {
        $paid += $payment['amount'];
        $infoPaiement .= '<div>'. $payment['ref'] ." - <b>".dol_print_date($payment['date'], 'day') . '</b> - ' /*. $payment["type"]." - "*/.price($payment['amount']) .'</div>';
    }

        // Retrieve credit note ids
        $invoiceObj->getListIdAvoirFromInvoice();  // fills property $creditnote_ids
        $creditnote_ids = $invoiceObj->creditnote_ids;  // fills property $creditnote_ids
    $creditnotes = getCreditNotesFromInvoice($invoice['id']);

    if (!is_array($creditnotes)) {
        continue;
    }

    $credits = [];
    foreach ($creditnotes as $creditnote) {
        $destInvoice = new Facture($db);
        if ($destInvoice->fetch($creditnote['invoice_used_id']) <= 0) {
            setEventMessages($destInvoice->error, $destInvoice->errors, 'errors');
            continue;
        }
        $ref = $destInvoice->ref;
        $datec = $creditnote['date'];
        $amount = $creditnote['amount_ttc'];
        $credits[] = '<div>'. $ref ." - <b>".dol_print_date($datec, 'day') . '</b> - ' /*. $payment["type"]." - "*/.price(-$amount) .'</div>';
        $paid -= $amount;
    }
    $infoPaiement.=implode("", $credits);

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
    print '<td>' . $invoiceObj->getLibStatut(1, $paid) . '</td>';
    print '<td>';
    print empty($infoPaiement) ? 'Aucun paiement' : $infoPaiement;
    print '</td>';
    print '</tr>';
}

print '</table>';

llxFooter();
$db->close();

function getCreditNotesFromInvoice($id)
{
    global $db;

    $result = array();

    $sql = "SELECT rowid, datec, fk_facture, fk_facture_source, amount_ttc";
    // $sql .= " FROM ".$this->db->prefix().$this->table_element;
    $sql .= " FROM ".$db->prefix().'societe_remise_except';
    $sql .= " WHERE fk_facture_source = ".((int) $id);
    // $sql .= " AND type = 2";
    $resql = $db->query($sql);

    if (!$resql) {
        setEventMessages($db->error(), null, 'errors');
        return -1;
    }

    $num = $db->num_rows($resql);
    $i = 0;
    while ($i < $num) {
        $row = $db->fetch_row($resql);
        $amount_ttc;
        $item = array(
	        'date' => $row[1],
	        'invoice_used_id' => $row[2],
	        'invoice_src_id' => $row[3],
	        'amount_ttc' => $row[4]
        );
        $result[] = $item;
        $i++;
    }

    return $result;
}
