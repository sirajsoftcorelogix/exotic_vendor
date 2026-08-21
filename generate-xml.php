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
     * Translate payment type into BUSY MasterName1 / PartyName
     * Rules:
     * - UPI -> YES2971
     * - Bank Transfer -> YES2971
     * - Cheque -> YES2971
     * - POS -> POS
     * - COD -> courier_name from vp_dispatch_details
     */
    public function translatePaymentType(string $rawPaymentType, array $data = []): string
    {
        $clean = strtolower(trim(str_replace(['_', '-'], ' ', $rawPaymentType)));

        if ($clean === 'upi' || $clean === 'bank transfer' || $clean === 'cheque') {
            return 'YES2971';
        }

        if ($clean === 'pos' || $clean === 'pos machine') {
            return 'POS';
        }

        if ($clean === 'cod') {
            $courierName = trim((string)($data['dispatch_courier_name'] ?? $data['courier_name'] ?? ''));
            if ($courierName !== '') {
                return $courierName;
            }
            return 'COD';
        }

        if ($rawPaymentType !== '') {
            return (strtolower($rawPaymentType) === 'cod') ? 'COD' : ucwords(str_replace('_', ' ', $rawPaymentType));
        }

        return '';
    }
    public function resolveStptName(array $data): string
    {
        // If explicitly set and non-empty, respect it
        if (!empty($data['stpt_name'])) {
            return $data['stpt_name'];
        }

        $country  = trim((string)($data['customer_country'] ?? $data['country'] ?? ''));
        $state    = trim((string)($data['customer_state'] ?? $data['state'] ?? $data['customer_address4'] ?? ''));
        $gstin    = trim((string)($data['customer_gstin'] ?? $data['gstin'] ?? ''));
        $stateCode = trim((string)($data['state_code'] ?? $data['customer_state_code'] ?? ''));

        // Resolve 2-digit Indian GST State Code
        $resolvedGstStateCode = $this->resolveGstStateCode($stateCode !== '' ? $stateCode : $state, $gstin);

        // Check if country is India or if a valid Indian GST State Code exists (01-38, 97)
        $isIndia = false;
        if ($country === '') {
            $isIndia = true;
        } else {
            $cUpper = strtoupper($country);
            if (in_array($cUpper, ['IN', 'IND', 'INDIA'], true)) {
                $isIndia = true;
            } elseif (!empty($resolvedGstStateCode) && ctype_digit($resolvedGstStateCode) && $resolvedGstStateCode !== '96') {
                // StateCode is an Indian GST state code (01 to 38 or 97, excluding 96=Foreign)
                $isIndia = true;
            }
        }

        if ($isIndia) {
            $isDelhi = ($resolvedGstStateCode === '07');
            if (!$isDelhi) {
                $sUpper = strtoupper($state);
                $isDelhi = in_array($sUpper, ['DELHI', 'NCT OF DELHI', 'NEW DELHI'], true);
            }
            return $isDelhi ? 'L/GST-ItemWise' : 'I/GST-ItemWise';
        }

        // Country != India (Export)
        $taxAmount = (float)($data['tax_amount'] ?? $data['st_amount'] ?? 0);
        $taxPercent = (float)($data['tax_percent'] ?? $data['tax_rate'] ?? 0);
        $hasGst = ($taxAmount > 0 || $taxPercent > 0);

        return $hasGst ? 'I/GST-Export' : 'I/GST-EXPORT-ZERO RATED';
    }

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
        
        $stptName = $this->resolveStptName($invoice);
        $xml->addChild('STPTName', htmlspecialchars($stptName));
        
        // Resolve Payment Type / Party Name / MasterName1
        $paymentType = trim($invoice['order_payment_mode'] ?? $invoice['payment_type'] ?? $invoice['payment_mode'] ?? '');
        if ($paymentType !== '') {
            if (strtoupper($paymentType) === 'YES2971') {
                $paymentType = 'YES2971';
            } elseif (strtolower($paymentType) === 'cod') {
                $paymentType = 'COD';
            } else {
                $paymentType = ucwords(str_replace('_', ' ', $paymentType));
            }
        }

        $partyName = !empty($invoice['party_name']) && $invoice['party_name'] !== $rawPaymentType
            ? $this->translatePaymentType($invoice['party_name'], $invoice)
            : ($translated !== '' ? $translated : ($invoice['customer_name'] ?? 'Walk-in Customer'));

        $masterName1 = !empty($invoice['master_name1']) && $invoice['master_name1'] !== $rawPaymentType
            ? $this->translatePaymentType($invoice['master_name1'], $invoice)
            : ($translated !== '' ? $translated : 'Main');

        // Master details
        $xml->addChild('MasterName1', htmlspecialchars($masterName1));
        $xml->addChild('MasterName2', htmlspecialchars($invoice['master_name2'] ?? 'Main Store'));
        $xml->addChild('TranCurName', htmlspecialchars($invoice['currency'] ?? 'Rs.'));
        $xml->addChild('InputType', $invoice['input_type'] ?? '1');
        
        // Billing Details
        $billingDetails = $xml->addChild('BillingDetails');
        $billingDetails->addChild('PartyName', htmlspecialchars($partyName));
        $billingDetails->addChild('Address1', htmlspecialchars($invoice['customer_address1'] ?? ''));
        $billingDetails->addChild('Address2', htmlspecialchars($invoice['customer_address2'] ?? ''));
        $billingDetails->addChild('Address3', htmlspecialchars($invoice['customer_address3'] ?? ''));
        $billingDetails->addChild('Address4', htmlspecialchars($invoice['customer_address4'] ?? ''));
        $billingDetails->addChild('MobileNo', htmlspecialchars($invoice['customer_mobile'] ?? ''));
        $billingDetails->addChild('Email', htmlspecialchars($invoice['customer_email'] ?? ''));
        $billingDetails->addChild('tmpVchCode', '0');
        $billingDetails->addChild('ITPAN', htmlspecialchars($invoice['customer_pan'] ?? ''));
        $gstStateCode = $this->resolveGstStateCode($invoice['customer_state'] ?? '', $invoice['customer_gstin'] ?? '');
        $billingDetails->addChild('StateCode', htmlspecialchars($gstStateCode));
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
        
        // Item details - use account group name if available, fallback to item_name
        $itemName = !empty($item['account_group_name'])
            ? $item['account_group_name']
            : (!empty($item['account_group'])
                ? $item['account_group']
                : ($item['item_name'] ?? $item['name'] ?? ''));
        $itemDetail->addChild('ItemName', htmlspecialchars($itemName));
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
        $itemDetail->addChild('MC', htmlspecialchars($invoice['exotic_address'] ?? 'Main Store'));
        $itemDetail->addChild('tmpDiscountBasis', '1');
        $tmpGroupName = !empty($item['account_group_name'])
            ? $item['account_group_name']
            : (!empty($item['account_group'])
                ? $item['account_group']
                : ($item['groupname'] ?? $item['item_name'] ?? ''));
        $itemDetail->addChild('tmpGroupName', htmlspecialchars($tmpGroupName));
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
     * Generate Busy XML for a Sales Return (Credit Note)
     * 
     * @param array $salesReturn Sales return header data
     * @param array $items       Sales return line items
     * @return string            XML string
     */
    public function generateSalesReturn(array $salesReturn, array $items = []): string
    {
        $xml = new SimpleXMLElement('<SaleReturn/>');
        
        // Voucher Series & Metadata
        $xml->addChild('VchSeriesName', htmlspecialchars($salesReturn['vch_series_name'] ?? 'Main'));
        
        // Date formatting (d-m-Y as per Busy format)
        $returnDate = $salesReturn['return_date'] ?? date('Y-m-d');
        $formattedDate = date('d-m-Y', strtotime($returnDate));
        $xml->addChild('Date', $formattedDate);
        
        $xml->addChild('VchType', $salesReturn['vch_type'] ?? '3'); // 3 = Sales Return in BUSY
        $xml->addChild('StockUpdationDate', $formattedDate);
        $xml->addChild('VchNo', htmlspecialchars($salesReturn['vch_no'] ?? $salesReturn['return_number'] ?? ''));
        
        $stptName = $this->resolveStptName($salesReturn);
        $xml->addChild('STPTName', htmlspecialchars($stptName));
        
        // Resolve Payment Type / Party Name / MasterName1
        $paymentType = trim($salesReturn['order_payment_mode'] ?? $salesReturn['payment_type'] ?? $salesReturn['payment_mode'] ?? '');
        if ($paymentType !== '') {
            if (strtoupper($paymentType) === 'YES2971') {
                $paymentType = 'YES2971';
            } elseif (strtolower($paymentType) === 'cod') {
                $paymentType = 'COD';
            } else {
                $paymentType = ucwords(str_replace('_', ' ', $paymentType));
            }
        }

        $masterName1 = !empty($salesReturn['master_name1']) && $salesReturn['master_name1'] !== $rawPaymentType
            ? $this->translatePaymentType($salesReturn['master_name1'], $salesReturn)
            : ($translated !== '' ? $translated : ($salesReturn['customer_name'] ?? 'Main'));

        // Master details
        $xml->addChild('MasterName1', htmlspecialchars($masterName1));
        $xml->addChild('MasterName2', htmlspecialchars($salesReturn['master_name2'] ?? 'Main Store'));
        $xml->addChild('TranCurName', htmlspecialchars($salesReturn['currency'] ?? 'Rs.'));
        $xml->addChild('InputType', $salesReturn['input_type'] ?? '1');
        
        // Billing Details
        $billingDetails = $xml->addChild('BillingDetails');
        $billingDetails->addChild('PartyName', htmlspecialchars($partyName));
        $billingDetails->addChild('Address1', htmlspecialchars($salesReturn['customer_address1'] ?? ''));
        $billingDetails->addChild('Address2', htmlspecialchars($salesReturn['customer_address2'] ?? ''));
        $billingDetails->addChild('Address3', htmlspecialchars($salesReturn['customer_address3'] ?? ''));
        $billingDetails->addChild('Address4', htmlspecialchars($salesReturn['customer_address4'] ?? ''));
        $billingDetails->addChild('MobileNo', htmlspecialchars($salesReturn['customer_mobile'] ?? ''));
        $billingDetails->addChild('Email', htmlspecialchars($salesReturn['customer_email'] ?? ''));
        $billingDetails->addChild('tmpVchCode', '0');
        $billingDetails->addChild('ITPAN', htmlspecialchars($salesReturn['customer_pan'] ?? ''));
        $gstStateCode = $this->resolveGstStateCode($salesReturn['customer_state'] ?? '', $salesReturn['customer_gstin'] ?? '');
        $billingDetails->addChild('StateCode', htmlspecialchars($gstStateCode));
        $billingDetails->addChild('GSTNo', htmlspecialchars($salesReturn['customer_gstin'] ?? ''));
        
        // Voucher Other Info Details
        $vchOtherInfo = $xml->addChild('VchOtherInfoDetails');
        $vchOtherInfo->addChild('OFInfo');
        $vchOtherInfo->addChild('Transport', htmlspecialchars($salesReturn['transport'] ?? 'others'));
        $vchOtherInfo->addChild('GRNo', htmlspecialchars($salesReturn['gr_no'] ?? ''));
        $vchOtherInfo->addChild('Station', htmlspecialchars($salesReturn['station'] ?? ''));
        $vchOtherInfo->addChild('TotalQty', htmlspecialchars($salesReturn['total_qty'] ?? '0.00'));
        $vchOtherInfo->addChild('Narration1', htmlspecialchars($salesReturn['remarks'] ?? $salesReturn['narration'] ?? 'Sales Return against ' . ($salesReturn['invoice_number'] ?? '')));
        $vchOtherInfo->addChild('GrDate', $formattedDate);
        $vchOtherInfo->addChild('Purpose', $salesReturn['purpose'] ?? '1');
        
        // Item Entries
        $itemEntries = $xml->addChild('ItemEntries');
        
        if (!empty($items)) {
            $srNo = 1;
            foreach ($items as $item) {
                // Adapt item structure for return line
                $itemData = [
                    'account_group_name' => $item['account_group_name'] ?? $item['account_group'] ?? '',
                    'item_name' => !empty($item['account_group_name']) ? $item['account_group_name'] : (!empty($item['account_group']) ? $item['account_group'] : ($item['item_code'] ?? $item['item_name'] ?? '')),
                    'unit' => $item['unit'] ?? 'PCS.',
                    'quantity' => $item['return_qty'] ?? $item['quantity'] ?? 0,
                    'unit_price' => $item['unit_price'] ?? 0,
                    'line_total' => ($item['return_qty'] ?? $item['quantity'] ?? 0) * ($item['unit_price'] ?? 0),
                    'net_amount' => ($item['return_qty'] ?? $item['quantity'] ?? 0) * ($item['unit_price'] ?? 0),
                    'tax_amount' => $item['tax_amount'] ?? 0,
                    'tax_percent' => $item['tax_rate'] ?? $item['tax_percent'] ?? 0,
                    'groupname' => $item['groupname'] ?? ''
                ];
                
                $salesReturnCtx = $salesReturn;
                $salesReturnCtx['invoice_date'] = $salesReturn['return_date'] ?? date('Y-m-d');
                $salesReturnCtx['vch_type'] = '3';
                $salesReturnCtx['vch_no'] = $salesReturn['return_number'] ?? '';
                
                $this->addItemDetail($itemEntries, $itemData, $salesReturnCtx, $srNo++);
            }
        }
        
        // Original Sale Details (link to original invoice)
        $orgSalePurc = $xml->addChild('OrgSalePurcDet');
        $orgSalePurc->addChild('VchNo', htmlspecialchars($salesReturn['invoice_number'] ?? $salesReturn['SalesReturnVchNo'] ?? ''));
        $orgSalePurc->addChild('VchDate', date('d-m-Y', strtotime($salesReturn['invoice_date'] ?? date('Y-m-d'))));
        $orgSalePurc->addChild('TaxableAmt', $salesReturn['subtotal'] ?? '0.00');
        $orgSalePurc->addChild('TaxAmt', $salesReturn['tax_amount'] ?? '0.00');
        $orgSalePurc->addChild('tmpVchCode', '0');
        $orgSalePurc->addChild('tmpFound', 'True');
        
        // Format and return XML
        $dom = dom_import_simplexml($xml)->ownerDocument;
        $dom->formatOutput = true;
        $xmlOutput = $dom->saveXML();
        return preg_replace('/<\?xml[^?]*\?>\n?/', '', $xmlOutput, 1);
    }

    /**
     * Generate consolidated XML with multiple invoices or returns
     * 
     * @param array $voucherArray Array of ['type' => 'invoice'|'sales_return', 'data' => header, 'items' => items]
     * @return string XML string with multiple voucher entries wrapped in <Vouchers>
     */
    public function generateConsolidated(array $voucherArray): string
    {
        $xmlString = '';
        
        foreach ($voucherArray as $voucherData) {
            $type = $voucherData['type'] ?? 'invoice';
            $data = $voucherData['data'] ?? $voucherData['invoice'] ?? $voucherData['sales_return'] ?? [];
            $items = $voucherData['items'] ?? [];
            
            if ($type === 'sales_return') {
                $xmlString .= $this->generateSalesReturn($data, $items);
            } else {
                $xmlString .= $this->generate($data, $items);
            }
        }
        
        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<Vouchers>\n" . $xmlString . "</Vouchers>";
    }

    /**
     * Helper to resolve 2-digit numeric GST state code (e.g., '19' for West Bengal)
     * 
     * @param string|null $state State name or code
     * @param string|null $gstin GSTIN number
     * @return string 2-digit numeric state code or fallback string
     */
    private function resolveGstStateCode(?string $state, ?string $gstin = ''): string
    {
        $gstin = strtoupper(trim((string)$gstin));
        // If GSTIN has valid 2-digit state prefix (e.g. 19ABCDE1234F1Z5)
        if (strlen($gstin) >= 2 && ctype_digit(substr($gstin, 0, 2))) {
            return substr($gstin, 0, 2);
        }

        $state = trim((string)$state);
        if ($state === '') {
            return '';
        }

        // If already numeric state code (e.g. "19" or "07")
        if (ctype_digit($state)) {
            return str_pad($state, 2, '0', STR_PAD_LEFT);
        }

        static $map = [
            'JAMMU AND KASHMIR' => '01', 'JAMMU & KASHMIR' => '01', 'J&K' => '01',
            'HIMACHAL PRADESH' => '02',
            'PUNJAB' => '03',
            'CHANDIGARH' => '04',
            'UTTARAKHAND' => '05', 'UTTARANCHAL' => '05',
            'HARYANA' => '06',
            'DELHI' => '07', 'NCT OF DELHI' => '07', 'NEW DELHI' => '07',
            'RAJASTHAN' => '08',
            'UTTAR PRADESH' => '09', 'UP' => '09',
            'BIHAR' => '10',
            'SIKKIM' => '11',
            'ARUNACHAL PRADESH' => '12',
            'NAGALAND' => '13',
            'MANIPUR' => '14',
            'MIZORAM' => '15',
            'TRIPURA' => '16',
            'MEGHALAYA' => '17',
            'ASSAM' => '18',
            'WEST BENGAL' => '19', 'WB' => '19',
            'JHARKHAND' => '20',
            'ODISHA' => '21', 'ORISSA' => '21',
            'CHHATTISGARH' => '22',
            'MADHYA PRADESH' => '23', 'MP' => '23',
            'GUJARAT' => '24',
            'DADRA AND NAGAR HAVELI AND DAMAN AND DIU' => '26', 'DAMAN AND DIU' => '26', 'DAMAN & DIU' => '26',
            'MAHARASHTRA' => '27', 'MH' => '27',
            'ANDHRA PRADESH' => '37', 'AP' => '37',
            'KARNATAKA' => '29',
            'GOA' => '30',
            'LAKSHADWEEP' => '31',
            'KERALA' => '32',
            'TAMIL NADU' => '33', 'TN' => '33',
            'PUDUCHERRY' => '34', 'PONDICHERRY' => '34',
            'ANDAMAN AND NICOBAR ISLANDS' => '35', 'ANDAMAN & NICOBAR' => '35',
            'TELANGANA' => '36',
            'LADAKH' => '38',
            'OTHER TERRITORY' => '97',
            'FOREIGN' => '96', 'OUTSIDE INDIA' => '96'
        ];

        $upperState = strtoupper($state);
        if (isset($map[$upperState])) {
            return $map[$upperState];
        }

        // DB lookup via State model if connection is available
        if (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli) {
            require_once __DIR__ . '/models/country/state.php';
            $stateModel = new State($GLOBALS['conn']);
            $code = $stateModel->resolveGstStateCode($state);
            if ($code) {
                return $code;
            }
        }

        return $state;
    }
}