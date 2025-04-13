<?php

class ExtraitCompte
{
    public function __construct($db)
    {
        $this->db = $db;
    }

    public function displayExtraitCompte($client_id)
    {
        global $langs;

        require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
        require_once DOL_DOCUMENT_ROOT.'/societe/class/client.class.php';
        require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';

        // Load translation files required by the page
        $langs->loadLangs(array("companies", "bills", "payments"));

        // Fetch client information
        $client = new Societe($this->db);
        if ($client->fetch($client_id) < 0) {
            setEventMessages($client->error, $client->errors, 'errors');
            return;
        }

        $facture_static = new Facture($this->db);
        $invoices = $facture_static->liste_array(0, 0, null, $client_id);

        if (!is_array($invoices)) {
            setEventMessages($facture_static->error, $facture_static->errors, 'errors');
            return;
        }

        // Display the extract
        print '<div id="extraitcompte-tab-content">';
        print '<h1>Extrait de Compte pour ' . $client->nom . '</h1>';
        print '<table class="noborder" width="100%">';
        print '<tr class="liste_titre">';
        print '<td>Date</td>';
        print '<td>Référence</td>';
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
            $invoiceObj = new Facture($this->db);
            if ($invoiceObj->fetch($invoice['id']) < 0) {
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
            foreach ($payments as $payment) {
                $paid += $payment['amount'];
                $infoPaiement .= '<div>'. $payment['ref'] ." - <b>".dol_print_date($payment['date'], 'day') . '</b> - '.price($payment['amount'])
.'</div>';
            }

            // Retrieve credit note ids
            $invoiceObj->getListIdAvoirFromInvoice();  // fills property $creditnote_ids
            $creditnote_ids = $invoiceObj->creditnote_ids;  // fills property $creditnote_ids
            $creditnotes = $this->getCreditNotesFromInvoice($invoice['id']);

            if (!is_array($creditnotes)) {
                setEventMessages($this->db->error(), null, 'errors');
                continue;
            }

            $credits = [];
            foreach ($creditnotes as $creditnote) {
                $destInvoice = new Facture($this->db);
                if ($destInvoice->fetch($creditnote['invoice_used_id']) < 0) {
                    setEventMessages($destInvoice->error, $destInvoice->errors, 'errors');
                    continue;
                }
                $ref = $destInvoice->ref;
                $datec = $creditnote['date'];
                $amount = $creditnote['amount_ttc'];
                $credits[] = '<div>'. $ref ." - <b>".dol_print_date($datec, 'day') . '</b> - '.price(-$amount) .'</div>';
                $paid -= $amount;
            }
            $infoPaiement .= implode("", $credits);

            // Calculate remaining amount due
            $remaining_due = $invoiceObj->total_ttc - $paid;

            print '<tr>';
            print '<td>' . dol_print_date($invoiceObj->date, 'day') . '</td>';
            print '<td>' . $invoiceObj->ref . '</td>';
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
        print '</div>';
    }

    private function getCreditNotesFromInvoice($id)
    {
        $result = array();

        $sql = "SELECT rowid, datec, fk_facture, fk_facture_source, amount_ttc";
        $sql .= " FROM ".$this->db->prefix().'societe_remise_except';
        $sql .= " WHERE fk_facture_source = ".((int) $id);
        $resql = $this->db->query($sql);

        if (!$resql) {
            setEventMessages($this->db->error(), null, 'errors');
            return false;
        }

        $num = $this->db->num_rows($resql);
        $i = 0;
        while ($i < $num) {
            $row = $this->db->fetch_row($resql);
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
}


