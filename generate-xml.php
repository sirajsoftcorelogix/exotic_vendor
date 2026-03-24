<?php
// export/generate-xml.php

/**
 * BusyXmlGenerator - Generates XML compatible with Busy accounting software
 * Supports both legacy and current invoice data structures
 */
class BusyXmlGenerator
{
    /**
     * Generate Busy XML from invoice data
     * 
     * @param array $invoice Invoice data array
     * @param array $items   (Optional) Invoice line items
     * @return string        XML string
     */
    public function generate(array $invoice, array $items = []): string
    {
        $xml = new SimpleXMLElement('<BUSYXMLDATA/>');
        
        // Get company name from invoice or use default
        $company = $invoice['company'] ?? 'EXOTIC INDIA';
        $xml->addChild('COMPANY', htmlspecialchars($company));
        $xml->addChild('VOUCHERTYPE', 'Sales');

        $vouchers = $xml->addChild('VOUCHERS');
        $voucher = $vouchers->addChild('VOUCHER');

        // Invoice date - handle both 'date' and 'invoice_date' field names
        $invoiceDate = isset($invoice['date']) ? $invoice['date'] : 
                      (isset($invoice['invoice_date']) ? $invoice['invoice_date'] : date('Y-m-d'));
        $date = date('d/m/Y', strtotime($invoiceDate));
        
        $voucher->addChild('DATE', $date);
        $voucher->addChild('VOUCHERNO', htmlspecialchars($invoice['invoice_number'] ?? $invoice['invoice_no'] ?? ''));
        
        // Get customer name
        $customer = $invoice['customer'] ?? 'Walk-in Customer';
        $voucher->addChild('PARTY', htmlspecialchars($customer));
        $voucher->addChild('NARRATION', htmlspecialchars($invoice['narration'] ?? ''));

        $entries = $voucher->addChild('LEDGERENTRIES');

        // If items provided, create line-by-line entries
        if (!empty($items)) {
            $this->addLineItemEntries($entries, $items, $invoice);
        } else {
            // Legacy single entry format
            $this->addLegacyEntries($entries, $invoice);
        }

        $dom = dom_import_simplexml($xml)->ownerDocument;
        $dom->formatOutput = true;
        return $dom->saveXML();
    }

    /**
     * Generate consolidated XML with multiple invoices (multiple vouchers)
     * 
     * @param array $invoiceArray Array of ['invoice' => invoice_data, 'items' => items_array]
     * @return string             XML string with multiple vouchers
     */
    public function generateConsolidated(array $invoiceArray): string
    {
        $xml = new SimpleXMLElement('<BUSYXMLDATA/>');
        
        // Get company name from first invoice or use default
        $firstInvoice = $invoiceArray[0]['invoice'] ?? [];
        $company = $firstInvoice['company'] ?? 'EXOTIC INDIA';
        $xml->addChild('COMPANY', htmlspecialchars($company));
        $xml->addChild('VOUCHERTYPE', 'Sales');

        $vouchers = $xml->addChild('VOUCHERS');

        // Create voucher for each invoice
        foreach ($invoiceArray as $invoiceData) {
            $invoice = $invoiceData['invoice'];
            $items = $invoiceData['items'] ?? [];

            $voucher = $vouchers->addChild('VOUCHER');

            // Invoice date
            $invoiceDate = isset($invoice['date']) ? $invoice['date'] : 
                          (isset($invoice['invoice_date']) ? $invoice['invoice_date'] : date('Y-m-d'));
            $date = date('d/m/Y', strtotime($invoiceDate));
            
            $voucher->addChild('DATE', $date);
            $voucher->addChild('VOUCHERNO', htmlspecialchars($invoice['invoice_number'] ?? $invoice['invoice_no'] ?? ''));
            
            // Get customer name
            $customer = $invoice['customer'] ?? 'Walk-in Customer';
            $voucher->addChild('PARTY', htmlspecialchars($customer));
            $voucher->addChild('NARRATION', htmlspecialchars($invoice['narration'] ?? ''));

            $entries = $voucher->addChild('LEDGERENTRIES');

            // Add line items or legacy entries
            if (!empty($items)) {
                $this->addLineItemEntries($entries, $items, $invoice);
            } else {
                $this->addLegacyEntries($entries, $invoice);
            }
        }

        $dom = dom_import_simplexml($xml)->ownerDocument;
        $dom->formatOutput = true;
        return $dom->saveXML();
    }

    /**
     * Add line-by-line entries for each invoice item
     */
    private function addLineItemEntries($entries, array $items, array $invoice)
    {
        // Debit entry — customer (total amount)
        $debit = $entries->addChild('ENTRY');
        $debit->addChild('LEDGER', 'Customers');
        $debit->addChild('AMOUNT', $invoice['total_amount'] ?? 0);

        // Add individual item entries
        foreach ($items as $item) {
            $itemEntry = $entries->addChild('ENTRY');
            
            // Use item name or code for ledger
            $ledgerName = $item['item_name'] ?? $item['item_code'] ?? 'Sales';
            $itemEntry->addChild('LEDGER', htmlspecialchars($ledgerName));
            $itemEntry->addChild('AMOUNT', -($item['line_total'] ?? 0));
            
            // Add quantity and HSN if available
            if (isset($item['quantity'])) {
                $itemEntry->addChild('QUANTITY', $item['quantity']);
            }
            if (isset($item['hsn'])) {
                $itemEntry->addChild('HSNSAC', htmlspecialchars($item['hsn']));
            }
        }

        // Tax entries
        if (isset($invoice['sgst']) && $invoice['sgst'] > 0) {
            $sgstEntry = $entries->addChild('ENTRY');
            $sgstEntry->addChild('LEDGER', 'SGST');
            $sgstEntry->addChild('AMOUNT', -($invoice['sgst'] ?? 0));
        }
        if (isset($invoice['cgst']) && $invoice['cgst'] > 0) {
            $cgstEntry = $entries->addChild('ENTRY');
            $cgstEntry->addChild('LEDGER', 'CGST');
            $cgstEntry->addChild('AMOUNT', -($invoice['cgst'] ?? 0));
        }
        if (isset($invoice['igst']) && $invoice['igst'] > 0) {
            $igstEntry = $entries->addChild('ENTRY');
            $igstEntry->addChild('LEDGER', 'IGST');
            $igstEntry->addChild('AMOUNT', -($invoice['igst'] ?? 0));
        }
    }

    /**
     * Add legacy format entries (backward compatibility)
     */
    private function addLegacyEntries($entries, array $invoice)
    {
        // Debit entry — customer
        $debit = $entries->addChild('ENTRY');
        $debit->addChild('LEDGER', htmlspecialchars($invoice['customer'] ?? 'Customers'));
        $debit->addChild('AMOUNT', $invoice['total_amount'] ?? 0);

        // Credit entry — sales
        $sales = $entries->addChild('ENTRY');
        $sales->addChild('LEDGER', 'Sales Account');
        $sales->addChild('AMOUNT', -($invoice['net_amount'] ?? 0));

        // Credit entries — taxes (if provided as array)
        if (isset($invoice['taxes']) && is_array($invoice['taxes'])) {
            foreach ($invoice['taxes'] as $tax) {
                $entry = $entries->addChild('ENTRY');
                $entry->addChild('LEDGER', htmlspecialchars($tax['ledger'] ?? 'Tax'));
                $entry->addChild('AMOUNT', -($tax['amount'] ?? 0));
            }
        }
    }
}