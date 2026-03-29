<?php
// export/generate-xml.php

/**
 * BusyXmlGenerator - Generates XML compatible with Busy accounting software
 * Formats invoices according to Busy POS/Inventory schema
 * 
 * Expected invoice array structure:
 * - vch_series_name: Series name (default: 'Main')
 * - date: Invoice date (Y-m-d format)
 * - vch_type: Voucher type (9 for Sales)
 * - vch_no: Invoice/voucher number
 * - stpt_name: Sales tax type name (e.g., 'I/GST-Export')
 * - master_name1: Customer/Party name
 * - master_name2: Location/Store (default: 'Main Store')
 * - party_name: Party name for billing
 * - mobile: Customer phone
 * - transport: Transport mode (U for others)
 * - gr_no: GR/AWB number
 * - station: Station/destination
 * - narration: Additional narration
 * - items: Array of item entries
 * - total_amount: Total invoice amount
 * - net_amount: Net sale amount (before tax)
 * - tax_amount: Total tax amount
 * - tax_percent: Tax percentage
 */
class BusyXmlGenerator
{
    /**
     * Generate Busy XML from invoice data
     * 
     * @param array $invoice Invoice data array
     * @param array $items   Invoice line items
     * @return string        XML string
     */
    public function generate(array $invoice, array $items = []): string
    {
        $xml = new SimpleXMLElement('<Sale/>');
        
        // Voucher Series & Metadata
        $xml->addChild('VchSeriesName', htmlspecialchars($invoice['vch_series_name'] ?? 'Main'));
        
        // Date formatting (d-m-Y as per Busy format)
        $invoiceDate = $invoice['invoice_date'] ?? date('Y-m-d');
        $formattedDate = date('d-m-Y', strtotime($invoiceDate));
        $xml->addChild('Date', $formattedDate);
        
        $xml->addChild('VchType', $invoice['vch_type'] ?? '9'); // 9 = Sales
        $xml->addChild('StockUpdationDate', $formattedDate);
        $xml->addChild('VchNo', htmlspecialchars($invoice['vch_no'] ?? $invoice['invoice_number'] ?? ''));
        $xml->addChild('STPTName', htmlspecialchars($invoice['stpt_name'] ?? 'I/GST-Export'));
        
        // Master details
        $xml->addChild('MasterName1', htmlspecialchars($invoice['master_name1'] ?? 'Main'));
        $xml->addChild('MasterName2', htmlspecialchars($invoice['master_name2'] ?? 'Main Store'));
        $xml->addChild('TranCurName', htmlspecialchars($invoice['currency'] ?? 'Rs.'));
        $xml->addChild('InputType', $invoice['input_type'] ?? '1');
        
        // Billing Details
        $billingDetails = $xml->addChild('BillingDetails');
        $billingDetails->addChild('PartyName', htmlspecialchars($invoice['customer_name'] ?? $invoice['master_name1'] ?? 'Walk-in Customer'));
        $billingDetails->addChild('Address1', htmlspecialchars($invoice['customer_address1'] ?? ''));
        $billingDetails->addChild('Address2', htmlspecialchars($invoice['customer_address2'] ?? ''));
        $billingDetails->addChild('Address3', htmlspecialchars($invoice['customer_address3'] ?? ''));
        $billingDetails->addChild('Address4', htmlspecialchars($invoice['customer_address4'] ?? ''));
        $billingDetails->addChild('MobileNo', htmlspecialchars($invoice['customer_mobile'] ?? ''));
        $billingDetails->addChild('Email', htmlspecialchars($invoice['customer_email'] ?? ''));
        $billingDetails->addChild('tmpVchCode', '0');
        $billingDetails->addChild('ITPAN', htmlspecialchars($invoice['customer_pan'] ?? ''));
        $billingDetails->addChild('StateCode', htmlspecialchars($invoice['customer_state'] ?? ''));
        $billingDetails->addChild('GSTNo', htmlspecialchars($invoice['customer_gstin'] ?? ''));
        
        // Voucher Other Info Details (shipping, transport, etc.)
        $vchOtherInfo = $xml->addChild('VchOtherInfoDetails');
        $vchOtherInfo->addChild('OFInfo');
        $vchOtherInfo->addChild('Transport', htmlspecialchars($invoice['transport'] ?? 'others'));
        $vchOtherInfo->addChild('GRNo', htmlspecialchars($invoice['gr_no'] ?? ''));
        $vchOtherInfo->addChild('Station', htmlspecialchars($invoice['station'] ?? ''));
        $vchOtherInfo->addChild('TotalQty', htmlspecialchars($invoice['total_qty'] ?? '0.00'));
        $vchOtherInfo->addChild('PurchaseBillNo', htmlspecialchars($invoice['purchase_bill_no'] ?? ''));
        $vchOtherInfo->addChild('PurchaseBillDate', $formattedDate);
        $vchOtherInfo->addChild('Narration1', htmlspecialchars($invoice['narration'] ?? ''));
        $vchOtherInfo->addChild('GrDate', $formattedDate);
        $vchOtherInfo->addChild('Purpose', $invoice['purpose'] ?? '1');
        $vchOtherInfo->addChild('PortCode', htmlspecialchars($invoice['port_code'] ?? ''));
        
        // Item Entries
        $itemEntries = $xml->addChild('ItemEntries');
        
        if (!empty($items)) {
            $srNo = 1;
            foreach ($items as $item) {
                $this->addItemDetail($itemEntries, $item, $invoice, $srNo++);
            }
        }
        
        // Original Sale/Purchase Details
        $orgSalePurc = $xml->addChild('OrgSalePurcDet');
        $orgSalePurc->addChild('VchNo', htmlspecialchars($invoice['org_vch_no'] ?? ''));
        $orgSalePurc->addChild('VchDate', date('d-m-Y', strtotime($invoice['org_vch_date'] ?? date('Y-m-d'))));
        $orgSalePurc->addChild('TaxableAmt', $invoice['subtotal'] ?? '0.00');
        $orgSalePurc->addChild('TaxAmt', $invoice['tax_amount'] ?? '0.00');
        $orgSalePurc->addChild('tmpVchCode', '0');
        $orgSalePurc->addChild('tmpFound', 'True');
        
        // Format and return XML
        $dom = dom_import_simplexml($xml)->ownerDocument;
        $dom->formatOutput = true;
        // return $dom->saveXML();
        $xmlOutput = $dom->saveXML();
        // Remove XML declaration
        return preg_replace('/<\?xml[^?]*\?>\n?/', '', $xmlOutput, 1);
    }

    /**
     * Add individual item detail to ItemEntries
     */
    private function addItemDetail($itemEntries, array $item, array $invoice, int $srNo): void
    {
        $itemDetail = $itemEntries->addChild('ItemDetail');
        
        // Invoice reference info
        $invoiceDate = $invoice['invoice_date'] ?? date('Y-m-d');
        $formattedDate = date('d-m-Y', strtotime($invoiceDate));
        
        $itemDetail->addChild('Date', $formattedDate);
        $itemDetail->addChild('VchType', $invoice['vch_type'] ?? '9');
        $itemDetail->addChild('VchNo', htmlspecialchars($invoice['vch_no'] ?? $invoice['invoice_number'] ?? ''));
        $itemDetail->addChild('SrNo', $srNo);
        
        // Item details
        $itemDetail->addChild('ItemName', htmlspecialchars($item['item_name'] ?? $item['name'] ?? ''));
        $itemDetail->addChild('UnitName', htmlspecialchars($item['unit'] ?? 'PCS.'));
        $itemDetail->addChild('AltUnitName', htmlspecialchars($item['alt_unit'] ?? $item['unit'] ?? 'PCS.'));
        $itemDetail->addChild('ConFactor', $item['con_factor'] ?? '1');
        
        // Quantity
        $qty = isset($item['quantity']) ? floatval($item['quantity']) : 0.00;
        $itemDetail->addChild('Qty', number_format($qty, 2, '.', ''));
        // $itemDetail->addChild('QtyMainUnit', number_format($qty, 2, '.', ''));
        // $itemDetail->addChild('QtyAltUnit', number_format($qty, 2, '.', ''));
        $itemDetail->addChild('QtyMainUnit', $qty);
        $itemDetail->addChild('QtyAltUnit', $qty);
        
        $itemDetail->addChild('ItemDescInfo');
        
        // Pricing
        $price = isset($item['unit_price']) ? floatval($item['unit_price']) : 0.00;
        $amt = isset($item['line_total']) ? floatval($item['line_total']) : 0.00;
        $nettAmount = isset($item['net_amount']) ? floatval($item['net_amount']) : $price * $qty;
        
        $itemDetail->addChild('Price', number_format($price, 2, '.', ''));
        $itemDetail->addChild('ListPrice', number_format($price, 2, '.', ''));
        $itemDetail->addChild('Amt', number_format($amt, 2, '.', ''));
        $itemDetail->addChild('NettAmount', number_format($nettAmount, 2, '.', ''));
        
        // Discount
        $discount = isset($item['discount']) ? floatval($item['discount']) : 0;
        $discountPercent = isset($item['discount_percent']) ? floatval($item['discount_percent']) : 0;
        
        $itemDetail->addChild('Discount', number_format($discount, 2, '.', ''));
        $itemDetail->addChild('DiscountPercent', number_format($discountPercent, 2, '.', ''));
        $itemDetail->addChild('CompoundDiscount', '0');
        
        // Tax details
        $itemDetail->addChild('Exempted', isset($item['exempted']) && $item['exempted'] ? 'TRUE' : 'FALSE');
        
        $taxAmount = isset($item['tax_amount']) ? floatval($item['tax_amount']) : 0;
        $taxPercent = isset($item['tax_percent']) ? floatval($item['tax_percent']) : 0;
        
        $itemDetail->addChild('STAmount', number_format($taxAmount, 2, '.', ''));
        $itemDetail->addChild('STPercent', number_format($taxPercent, 2, '.', ''));
        $itemDetail->addChild('STPercent1', number_format($taxPercent, 2, '.', ''));
        $itemDetail->addChild('TaxBeforeSurcharge', number_format($taxAmount, 2, '.', ''));
        
        // Additional fields
        $itemDetail->addChild('tmpNettPrice', '0');
        $itemDetail->addChild('MC', htmlspecialchars($invoice['master_name2'] ?? 'Main Store'));
        $itemDetail->addChild('tmpDiscountBasis', '1');
        $itemDetail->addChild('tmpGroupName', htmlspecialchars($item['groupname'] ?? $item['item_name'] ?? ''));
        $itemDetail->addChild('tmpMainUnitName', htmlspecialchars($item['unit'] ?? 'PCS.'));
        $itemDetail->addChild('tmpAltUnitName', htmlspecialchars($item['alt_unit'] ?? $item['unit'] ?? 'PCS.'));
        $itemDetail->addChild('tmpConFactorType', '1');
        $itemDetail->addChild('tmpItemMastConFactor', '1');
        
        // Empty collections
        $itemDetail->addChild('ItemSerialNoEntries');
        $itemDetail->addChild('ParamStockEntries');
        $itemDetail->addChild('BatchEntries');
        
        $itemDetail->addChild('DiscountStructure', 'Simple Discount, % of Price');
    }

    /**
     * Generate consolidated XML with multiple invoices
     * 
     * @param array $invoiceArray Array of ['invoice' => invoice_data, 'items' => items_array]
     * @return string XML string with multiple sales entries
     */
    public function generateConsolidated(array $invoiceArray): string
    {
        $xmlString = '';
        
        foreach ($invoiceArray as $invoiceData) {
            $invoice = $invoiceData['invoice'];
            $items = $invoiceData['items'] ?? [];
            $xmlString .= $this->generate($invoice, $items);
        }
        
        return $xmlString;
    }
}