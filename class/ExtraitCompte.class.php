<?php

class ExtraitCompte
{
    public function __construct($db)
    {
        $this->db = $db;
    }

    public function displayExtraitCompte($client_id, $start_date = null, $all_dates = false)
    {
        global $langs, $conf;

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

        // If "All" button was clicked, use a very early date
        if ($all_dates) {
            $start_date = '1900-01-01';
        } elseif ($start_date === null || empty($start_date)) {
            // Calculate default start date (first day of previous trimester) if not provided
            $start_date = $this->getFirstDayOfPreviousTrimester();
        } else {
            // Ensure date is in YYYY-MM-DD format (HTML5 date input already provides this)
            $start_date = preg_replace('/[^0-9\-]/', '', $start_date);
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) {
                $start_date = $this->getFirstDayOfPreviousTrimester();
            }
        }

        // Get all invoices for the client
        $facture_static = new Facture($this->db);
        $all_invoices = $facture_static->liste_array(0, 0, null, $client_id);

        if (!is_array($all_invoices)) {
            setEventMessages($facture_static->error, $facture_static->errors, 'errors');
            return;
        }

        // Find the earliest unpaid invoice date that is incomplete and not abandoned
        $earliest_unpaid_date = $this->findEarliestUnpaidInvoiceDate($all_invoices);

        // Apply backtrack rule: if there's an unpaid invoice older than start_date, adjust start_date
        // $earliest_unpaid_date is a timestamp, $start_date is a string in YYYY-MM-DD format
        if ($earliest_unpaid_date && $start_date && $earliest_unpaid_date < strtotime($start_date)) {
            $start_date = dol_print_date($earliest_unpaid_date, '%Y-%m-%d');
        }

        $end_date = date('Y-m-d');

        // Filter invoices by start date and track actual date range
        $invoices = array();
        $actual_start_date = null;
        $actual_end_date = null;
        foreach ($all_invoices as $invoice) {
            $invoiceObj = new Facture($this->db);
            if ($invoiceObj->fetch($invoice['id']) >= 0) {
                $invoice_date = $invoiceObj->date;
                // Normalize date for comparison (handle both string and timestamp)
                $invoice_date_str = is_numeric($invoice_date) ? date('Y-m-d', $invoice_date) : (string)$invoice_date;
                if ($all_dates || $invoice_date_str >= $start_date) {
                    $invoices[] = $invoice;
                    // Track actual date range from filtered invoices
                    if ($actual_start_date === null || $invoice_date_str < $actual_start_date) {
                        $actual_start_date = $invoice_date_str;
                    }
                    if ($actual_end_date === null || $invoice_date_str > $actual_end_date) {
                        $actual_end_date = $invoice_date_str;
                    }
                }
            }
        }

        if (!is_array($invoices)) {
            setEventMessages($facture_static->error, $facture_static->errors, 'errors');
            return;
        }

        // If no invoices found, use filter dates
        if (empty($invoices)) {
            $actual_start_date = $start_date;
            $actual_end_date = $end_date;
        } elseif ($actual_start_date === null || $actual_end_date === null) {
            // Fallback to filter dates if tracking failed
            $actual_start_date = $start_date;
            $actual_end_date = $end_date;
        }

        // Display the extract
        print '<div id="extraitcompte-tab-content">';
        print '<h1>' . $langs->trans("AccountExtractTitle", $client->nom, dol_print_date($actual_start_date, 'day'), dol_print_date($actual_end_date, 'day')) . '</h1>';
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

    /**
     * Get the first day of the previous trimester
     * 
     * @return string Date in YYYY-MM-DD format
     */
    private function getFirstDayOfPreviousTrimester()
    {
        $current_month = (int)date('n');
        $current_year = (int)date('Y');
        
        // Determine current trimester (1-4, 5-8, 9-12)
        $current_trimester = ceil($current_month / 3);
        
        // Previous trimester
        if ($current_trimester == 1) {
            $prev_trimester = 4;
            $year = $current_year - 1;
        } else {
            $prev_trimester = $current_trimester - 1;
            $year = $current_year;
        }
        
        // Calculate first month of previous trimester
        $first_month = (($prev_trimester - 1) * 3) + 1;
        
        // Return first day of that month
        return sprintf('%04d-%02d-01', $year, $first_month);
    }

    /**
     * Find the earliest unpaid invoice date that is incomplete and not abandoned
     * 
     * @param array $invoices Array of invoice entries from liste_array
     * @return string|null Date in YYYY-MM-DD format, or null if no such invoice
     */
    private function findEarliestUnpaidInvoiceDate($invoices)
    {
        $earliest_date = null;
        
        foreach ($invoices as $invoice_entry) {
            $invoiceObj = new Facture($this->db);
            if ($invoiceObj->fetch($invoice_entry['id']) < 0) {
                continue;
            }
            
            // Check if invoice is unpaid and not abandoned
            $paid = $invoiceObj->getSommePaiement();
            $remaining = $invoiceObj->total_ttc - $paid;
            
            // Incomplete means status is not closed/completed
            // Not abandoned means it's not in a cancelled/abandoned state
            // Unpaid means remaining > 0
            $status = $invoiceObj->statut;
            
            // In Dolibarr, status values:
            // 0 = Draft, 1 = Validated, 2 = Paid (closed), 3 = Cancelled
            // We want invoices that are not cancelled (status != 3) and not fully paid
            if ($status != 3 && $remaining > 0) {
                if ($earliest_date === null || $invoiceObj->date < $earliest_date) {
                    $earliest_date = $invoiceObj->date;
                }
            }
        }
        
        return $earliest_date;
    }
}


