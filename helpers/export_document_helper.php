<?php

/**
 * Helper logic for Export Document Generation Module.
 * Matrix resolution, document definitions, and template default builders.
 */

/**
 * Get available shipment types.
 *
 * @return array<string, string>
 */
function getExportShipmentTypes(): array
{
    return [
        'csb5' => 'CSB-5 (Express Courier Courier Shipping Bill)',
        'commercial' => 'Commercial Shipment (Cargo / Formal Customs Clearing)'
    ];
}

/**
 * Get product categories for export.
 *
 * @return array<string, string>
 */
function getExportCategories(): array
{
    return [
        'sculpture_painting_home' => 'Sculpture, Painting & Home & Living',
        'books' => 'Books & Publications',
        'audio_cd' => 'Audio CD / Film / Media',
        'handloom' => 'Handloom & Textiles'
    ];
}

/**
 * Get supported courier partners.
 *
 * @return array<string, string>
 */
function getExportCourierPartners(): array
{
    return [
        'ups' => 'UPS',
        'dhl' => 'DHL Express',
        'fedex' => 'FedEx',
        'aramex' => 'Aramex Express',
        'delhivery' => 'Delhivery Express',
        'shiprocket' => 'Shiprocket Express',
        'bluedart' => 'Blue Dart Express',
        'other' => 'Other Courier / Freight Forwarder'
    ];
}

/**
 * Resolve required documents based on rules.
 *
 * @param string $shipmentType 'csb5' or 'commercial'
 * @param string $category 'sculpture_painting_home', 'books', 'audio_cd', 'handloom'
 * @param string $courierPartner 'ups', 'dhl', 'fedex', 'other'
 * @param bool $isDrawback
 * @param bool $hasRodtep
 * @param bool $hasLacey
 * @return array<string, string> Keyed by document_code => document_title
 */
function resolveRequiredExportDocuments(
    string $shipmentType,
    string $category,
    string $courierPartner,
    bool $isDrawback = false,
    bool $hasRodtep = false,
    bool $hasLacey = false
): array {
    $docs = [];

    $isUPS = (strtolower($courierPartner) === 'ups');

    if ($shipmentType === 'csb5') {
        // CSB-5 Invoices
        $docs['csb5_invoice'] = 'Invoice CSB-5';

        // Category specific docs
        switch ($category) {
            case 'sculpture_painting_home':
                $docs['origin_cert'] = 'Certificate of Origin';
                $docs['declaration_work_of_art'] = 'Work of Art Declaration';
                break;
            case 'books':
                $docs['declaration_book'] = 'Book Non-Objection Declaration';
                break;
            case 'audio_cd':
                $docs['declaration_audio_film'] = 'Audio / Film Declaration';
                break;
            case 'handloom':
                $docs['origin_cert'] = 'Certificate of Origin';
                $docs['declaration_textile'] = 'Handloom / Textile Declaration';
                break;
        }

        // UPS CSB5 SLI if shipped via UPS
        if ($isUPS) {
            $docs['sli_ups_csb5'] = 'UPS CSB-5 SLI';
        }

    } else {
        // Commercial Shipment
        $docs['commercial_invoice'] = 'Commercial Invoice Cum Packing List';

        // SLI for DHL / FedEx / UPS
        $sliTitle = 'Shipper\'s Letter of Instruction (SLI) - ' . ($isDrawback ? 'Drawback' : 'Non-Drawback');
        $docs['sli_commercial'] = $sliTitle;

        // Origin Certificate & RODTEP
        $docs['origin_cert'] = 'Certificate of Origin';
        if ($hasRodtep || $shipmentType === 'commercial') {
            $docs['rodtep_annexure'] = 'RODTEP Annexure';
        }

        // Category specific declarations
        switch ($category) {
            case 'sculpture_painting_home':
                $docs['declaration_work_of_art'] = 'Work of Art Declaration';
                if ($hasLacey) {
                    $docs['declaration_lacey'] = 'Lacey Act Declaration';
                }
                break;
            case 'books':
                $docs['declaration_book'] = 'Book Non-Objection Declaration';
                break;
            case 'handloom':
                $docs['declaration_textile'] = 'Handloom / Textile Declaration';
                break;
        }
    }

    return $docs;
}

/**
 * Helper to return first non-empty string value.
 */
function export_first_non_empty(...$values): string
{
    foreach ($values as $val) {
        if ($val === null) {
            continue;
        }
        $str = trim((string)$val);
        if ($str !== '' && strtolower($str) !== 'null' && strtolower($str) !== 'n/a') {
            return $str;
        }
    }
    return '';
}

/**
 * Build common session data array from auto-pulled invoice details.
 *
 * @param array<string, mixed> $autoPulledData
 * @return array<string, mixed>
 */
function buildCommonExportSessionData(array $autoPulledData): array
{
    $inv = $autoPulledData['invoice'] ?? [];
    $intl = $autoPulledData['international'] ?? [];
    $firm = $autoPulledData['firm'] ?? [];
    $items = $autoPulledData['items'] ?? [];

    $destCountry = export_first_non_empty(
        $inv['shipping_country'] ?? '',
        $inv['ship_country'] ?? '',
        $inv['shipping_country_code'] ?? '',
        $inv['country'] ?? '',
        $inv['country_code'] ?? '',
        $inv['bill_country'] ?? '',
        $inv['country_of_residence'] ?? ''
    );

    $destCity = export_first_non_empty(
        $inv['shipping_city'] ?? '',
        $inv['ship_city'] ?? '',
        $inv['city'] ?? '',
        $inv['bill_city'] ?? ''
    );

    $finalDest = $destCity ? $destCity . ($destCountry ? ', ' . $destCountry : '') : $destCountry;

    $sName = trim(($inv['shipping_first_name'] ?? '') . ' ' . ($inv['shipping_last_name'] ?? ''));
    $bName = trim(($inv['first_name'] ?? '') . ' ' . ($inv['last_name'] ?? ''));
    $consigneeName = export_first_non_empty(
        $sName,
        $bName,
        $inv['shipping_name'] ?? '',
        $inv['shipping_display_name'] ?? '',
        $inv['shipping_company'] ?? '',
        $inv['trade_name'] ?? '',
        $inv['customer_master_name'] ?? '',
        $inv['customer_name'] ?? '',
        $inv['name'] ?? '',
        $inv['company'] ?? ''
    );

    $consigneeAddr1 = export_first_non_empty(
        $inv['shipping_address_line1'] ?? '',
        $inv['shipping_address1'] ?? '',
        $inv['shipping_address'] ?? '',
        $inv['ship_address1'] ?? '',
        $inv['address_line1'] ?? '',
        $inv['address1'] ?? '',
        $inv['address'] ?? '',
        $inv['bill_address1'] ?? '',
        $inv['customer_address1'] ?? ''
    );

    $consigneeAddr2 = export_first_non_empty(
        $inv['shipping_address_line2'] ?? '',
        $inv['shipping_address2'] ?? '',
        $inv['ship_address2'] ?? '',
        $inv['address_line2'] ?? '',
        $inv['address2'] ?? '',
        $inv['bill_address2'] ?? '',
        $inv['customer_address2'] ?? ''
    );

    $consigneeZip = export_first_non_empty(
        $inv['shipping_zipcode'] ?? '',
        $inv['shipping_zip'] ?? '',
        $inv['ship_zip'] ?? '',
        $inv['zipcode'] ?? '',
        $inv['zip'] ?? '',
        $inv['bill_zip'] ?? '',
        $inv['customer_zipcode'] ?? ''
    );

    $consigneeState = export_first_non_empty(
        $inv['shipping_state'] ?? '',
        $inv['ship_state'] ?? '',
        $inv['state'] ?? '',
        $inv['bill_state'] ?? '',
        $inv['customer_state'] ?? ''
    );

    $consigneePhone = export_first_non_empty(
        $inv['shipping_mobile'] ?? '',
        $inv['shipping_phone'] ?? '',
        $inv['mobile'] ?? '',
        $inv['phone'] ?? '',
        $inv['customer_master_phone'] ?? '',
        $inv['customer_phone'] ?? ''
    );

    $consigneeEmail = export_first_non_empty(
        $inv['shipping_email'] ?? '',
        $inv['email'] ?? '',
        $inv['customer_master_email'] ?? '',
        $inv['customer_email'] ?? ''
    );

    $currency = export_first_non_empty(
        $intl['shipping_currency'] ?? '',
        $inv['currency'] ?? '',
        $inv['shipping_currency'] ?? ''
    );
    $currency = strtoupper(trim($currency));
    if ($currency === '' || $currency === 'INR') {
        $currency = 'USD';
    }

    $totalNetWeight = 0.0;
    $totalGrossWeight = 0.0;
    foreach ($items as $it) {
        $qty = (float)($it['quantity'] ?? 1);
        $w = (float)($it['product_weight'] ?? $it['net_weight'] ?? 0.25);
        $totalNetWeight += ($w * $qty);
    }
    if ($totalNetWeight <= 0) {
        $totalNetWeight = 0.50; // default 500g
    }
    $totalGrossWeight = round($totalNetWeight * 1.15, 2); // 10-15% tare buffer

    if ($currency === 'USD') {
        $exchangeRate = (float)($intl['usd_export_rate'] ?? ($inv['usd_export_rate'] ?? ($inv['master_rate_export'] ?? 83.50)));
    } else {
        // For non-USD currencies (EUR, GBP, CAD, AUD, DKK, etc.), prioritize the rate_export from currency_master
        $exchangeRate = (float)($inv['master_rate_export'] ?? ($intl['usd_export_rate'] ?? 83.50));
    }
    if ($exchangeRate <= 0) {
        $exchangeRate = 83.50;
    }

    return [
        'invoice_number' => $inv['invoice_number'] ?? '',
        'invoice_date' => $inv['invoice_date'] ?? date('Y-m-d'),
        'order_number' => $inv['order_number'] ?? '',
        'exporter_name' => !empty($firm['firm_name']) ? $firm['firm_name'] : ($firm['exporter_name'] ?? 'EXOTIC INDIA ART PVT LTD'),
        'exporter_address' => !empty($firm['firm_address']) ? $firm['firm_address'] : ($firm['address'] ?? ($firm['exporter_address'] ?? '101, Plaza A-1, Paschim Vihar')),
        'exporter_city' => !empty($firm['firm_city']) ? $firm['firm_city'] : ($firm['city'] ?? ($firm['exporter_city'] ?? 'New Delhi')),
        'exporter_pincode' => !empty($firm['firm_pin']) ? $firm['firm_pin'] : ($firm['firm_pincode'] ?? ($firm['pincode'] ?? ($firm['exporter_pincode'] ?? '110063'))),
        'exporter_country' => !empty($firm['firm_country']) ? $firm['firm_country'] : ($firm['exporter_country'] ?? 'India'),
        'exporter_iec' => !empty($firm['firm_iec']) ? $firm['firm_iec'] : ($firm['iec_code'] ?? ($firm['exporter_iec'] ?? '0505012345')),
        'exporter_gstin' => !empty($firm['gstin']) ? $firm['gstin'] : ($firm['firm_gstin'] ?? ($firm['exporter_gstin'] ?? '07AADCE1400C1ZJ')),
        'exporter_pan' => !empty($firm['firm_pan']) ? $firm['firm_pan'] : ($firm['pan'] ?? ($firm['exporter_pan'] ?? 'AADCE1400C')),
        'consignee_name' => $consigneeName,
        'consignee_address_line1' => $consigneeAddr1,
        'consignee_address_line2' => $consigneeAddr2,
        'consignee_city' => $destCity,
        'consignee_state' => $consigneeState,
        'consignee_zipcode' => $consigneeZip,
        'consignee_country' => $destCountry,
        'consignee_phone' => $consigneePhone,
        'consignee_email' => $consigneeEmail,
        'port_of_loading' => $intl['port_of_loading'] ?? 'New Delhi (INABG1)',
        'port_of_discharge' => $intl['port_of_discharge'] ?? $destCity,
        'country_of_origin' => 'India',
        'country_of_destination' => $destCountry,
        'final_destination' => $finalDest,
        'currency' => $currency,
        'exchange_rate' => $exchangeRate,
        'total_amount' => (float)($inv['total_amount'] ?? 0),
        'gross_weight' => $totalGrossWeight,
        'net_weight' => round($totalNetWeight, 2),
        'total_packages' => 1,
        'terms_of_delivery' => 'DDP / Express Courier',
        'declaration_date' => date('Y-m-d'),
        'authorized_signatory' => $firm['authorized_signatory'] ?? 'Authorized Signatory',
        'irn' => export_first_non_empty($intl['irn'] ?? '', $inv['irn'] ?? ''),
        'ack_number' => export_first_non_empty($intl['ack_number'] ?? '', $inv['ack_number'] ?? ''),
        'ack_date' => export_first_non_empty($intl['ack_date'] ?? '', $inv['ack_date'] ?? ''),
        'qrcode_string' => export_first_non_empty($intl['qrcode_string'] ?? '', $inv['qrcode_string'] ?? '')
    ];
}

/**
 * Helper to dynamically resolve IRN details for an export session if missing in common_data.
 *
 * @param \mysqli $db
 * @param array<string, mixed> $session
 * @param array<string, mixed> $common
 * @return array<string, mixed>
 */
function resolveExportSessionIrnDetails(\mysqli $db, array $session, array $common): array
{
    $irn = export_first_non_empty($common['irn'] ?? '', $common['inv_irn'] ?? '');
    $qr = export_first_non_empty($common['qrcode_string'] ?? '');
    $ackNum = export_first_non_empty($common['ack_number'] ?? '', $common['ack_no'] ?? '');
    $ackDt = export_first_non_empty($common['ack_date'] ?? '');

    if (!empty($irn) && !empty($qr)) {
        return $common;
    }

    $invoiceId = (int)($session['invoice_id'] ?? $common['invoice_id'] ?? 0);
    $invoiceNum = trim((string)($session['invoice_number'] ?? $common['invoice_number'] ?? ''));
    $orderNum = trim((string)($session['order_number'] ?? $common['order_number'] ?? ''));

    // 1. Check vp_invoices_international
    if ($invoiceId > 0) {
        $stmt = $db->prepare("SELECT irn, ack_number, ack_date, qrcode_string FROM vp_invoices_international WHERE invoice_id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('i', $invoiceId);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                if (empty($irn) && !empty($row['irn'])) $irn = trim((string)$row['irn']);
                if (empty($qr) && !empty($row['qrcode_string'])) $qr = trim((string)$row['qrcode_string']);
                if (empty($ackNum) && !empty($row['ack_number'])) $ackNum = trim((string)$row['ack_number']);
                if (empty($ackDt) && !empty($row['ack_date'])) $ackDt = trim((string)$row['ack_date']);
            }
            $stmt->close();
        }
    }

    // 2. Check vp_domestic_ewb_irn if still missing
    if (empty($irn) || empty($qr)) {
        $ewbRow = null;
        if ($invoiceId > 0) {
            $stmt = $db->prepare("SELECT irn, irn_response FROM vp_domestic_ewb_irn WHERE vp_invoices_id = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param('i', $invoiceId);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($r = $res->fetch_assoc()) $ewbRow = $r;
                $stmt->close();
            }
        }

        if (!$ewbRow && ($orderNum !== '' || $invoiceNum !== '')) {
            $searchVal = $orderNum !== '' ? $orderNum : $invoiceNum;
            $stmt = $db->prepare("SELECT d.irn, d.irn_response FROM vp_domestic_ewb_irn d
                                  JOIN vp_invoices i ON i.id = d.vp_invoices_id
                                  LEFT JOIN vp_order_info oi ON oi.id = i.vp_order_info_id
                                  LEFT JOIN vp_invoice_items ii ON ii.invoice_id = i.id
                                  WHERE oi.order_number = ? OR i.invoice_number = ? OR ii.order_number = ?
                                  ORDER BY d.id DESC LIMIT 1");
            if ($stmt) {
                $stmt->bind_param('sss', $searchVal, $searchVal, $searchVal);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($r = $res->fetch_assoc()) $ewbRow = $r;
                $stmt->close();
            }
        }

        if ($ewbRow) {
            if (empty($irn) && !empty($ewbRow['irn'])) {
                $irn = trim((string)$ewbRow['irn']);
            }
            if (!empty($ewbRow['irn_response'])) {
                $resp = json_decode($ewbRow['irn_response'], true);
                if (is_array($resp)) {
                    if (empty($irn)) {
                        $irn = export_first_non_empty($resp['Irn'] ?? '', $resp['irn'] ?? '', $resp['InfoDtls'][0]['Desc']['Irn'] ?? '');
                    }
                    if (empty($qr)) {
                        $qr = export_first_non_empty($resp['SignedQRCode'] ?? '', $resp['SignedQrCode'] ?? '', $resp['signed_qr_code'] ?? '', $resp['qr_code'] ?? '');
                    }
                    if (empty($ackNum)) {
                        $ackNum = export_first_non_empty($resp['AckNo'] ?? '', $resp['ack_no'] ?? '', $resp['ack_number'] ?? '');
                    }
                    if (empty($ackDt)) {
                        $ackDt = export_first_non_empty($resp['AckDt'] ?? '', $resp['ack_date'] ?? '');
                    }
                }
            }
        }
    }

    $common['irn'] = $irn;
    $common['qrcode_string'] = $qr;
    $common['ack_number'] = $ackNum;
    $common['ack_date'] = $ackDt;

    return $common;
}

/**
 * Generate default field map for a given document template.
 *
 * @param string $docCode
 * @param array<string, mixed> $commonData
 * @param array<int, array<string, mixed>> $items
 * @return array<string, mixed>
 */
function buildDefaultDocumentFormData(string $docCode, array $commonData, array $items = []): array
{
    $formattedItems = [];
    $totalQty = 0;
    $grandTotal = 0.0;

    foreach ($items as $idx => $it) {
        $qty = (int)($it['quantity'] ?? 1);
        $rate = (float)($it['unit_price'] ?? $it['price'] ?? 0);
        $amount = (float)($it['total_price'] ?? ($qty * $rate));

        $totalQty += $qty;
        $grandTotal += $amount;

        $formattedItems[] = [
            'sno' => $idx + 1,
            'item_code' => $it['item_code'] ?? $it['order_sku'] ?? '',
            'description' => $it['title'] ?? $it['order_title'] ?? 'Art & Craft Item',
            'hsn_code' => $it['hsn_code'] ?? $it['hsn'] ?? $it['hscode'] ?? '',
            'quantity' => $qty,
            'unit_price' => $rate,
            'amount' => $amount
        ];
    }

    $base = [
        'common' => $commonData,
        'items' => $formattedItems,
        'total_quantity' => $totalQty,
        'grand_total' => $grandTotal,
        'remarks' => ''
    ];

    switch ($docCode) {
        case 'csb5_invoice':
            return array_merge($base, [
                'document_title' => 'EXPRESS COURIER INVOICE (CSB-5)',
                'csb_type' => 'CSB-V (Commercial Express)',
                'nature_of_transaction' => 'Outright Sale',
                'declaration_clause' => 'We declare that this invoice shows the actual price of the goods described and that all particulars are true and correct.'
            ]);

        case 'commercial_invoice':
            return array_merge($base, [
                'document_title' => 'TAX INVOICE / COMMERCIAL INVOICE CUM PACKING LIST',
                'supply_type' => 'SUPPLY MEANT FOR EXPORT WITH PAYMENT OF IGST',
                'terms_of_payment' => 'Prepaid / Advance Payment',
                'buyer_order_ref' => $commonData['order_number'] ?? '',
                'buyer_order_date' => $commonData['invoice_date'] ?? date('Y-m-d'),
                'lut_number' => '',
                'igst_rate' => 18.0,
                'declaration_clause' => 'We declare that this invoice shows the actual price of the goods described and that all particulars are true and correct. SUPPLY MEANT FOR EXPORT WITH PAYMENT OF INTEGRATED TAX (IGST).'
            ]);

        case 'sli_ups_csb5':
            return array_merge($base, [
                'document_title' => 'UPS CSB-5 SHIPPER\'S LETTER OF INSTRUCTION',
                'carrier_name' => 'UPS India Private Limited',
                'service_type' => 'UPS Worldwide Express / Saver',
                'customs_clearance_mode' => 'CSB-V Express Clearance',
                'account_number' => 'UPS-EXOTIC-001',
                'special_instructions' => 'Express air shipment under CSB-V regulations. Handle with care.'
            ]);

        case 'sli_commercial':
            return array_merge($base, [
                'document_title' => 'SHIPPER\'S LETTER OF INSTRUCTION (SLI)',
                'drawback_status' => 'Drawback Shipment',
                'export_scheme' => 'RODTEP / Drawback',
                'port_code' => 'INABG1',
                'cha_name' => 'Self / Express Courier Clearance Agent'
            ]);

        case 'origin_cert':
            return array_merge($base, [
                'document_title' => 'CERTIFICATE OF ORIGIN',
                'issuing_authority' => 'Delhi Chamber of Commerce & Industry / Self Declaration',
                'origin_criterion' => 'Wholly Produced in India (P)',
                'transport_details' => 'Air Freight via ' . ($commonData['consignee_country'] ?? 'International Destination')
            ]);

        case 'rodtep_annexure':
            return array_merge($base, [
                'document_title' => 'ANNEXURE FOR RODTEP SCHEME',
                'scheme_code' => 'RODTEP-2021',
                'rodtep_declaration' => 'I/We declare that I/we shall abide by the provisions, rules and conditions of the RODTEP scheme as notified by DGFT/Customs.'
            ]);

        case 'declaration_work_of_art':
            return array_merge($base, [
                'document_title' => 'WORK OF ART DECLARATION',
                'art_type' => 'Handcrafted Sculpture / Traditional Painting / Artifact',
                'artist_creator' => 'Traditional Indian Artisan',
                'year_of_creation' => 'Modern / Contemporary (Under 100 Years)',
                'antiquity_declaration' => 'We certify that the artwork(s) listed herein are modern handicraft/art pieces created within the last 100 years and do NOT fall under the Antiquities and Art Treasures Act, 1972.'
            ]);

        case 'declaration_book':
            return array_merge($base, [
                'document_title' => 'BOOK / PUBLICATION DECLARATION',
                'publication_type' => 'Printed Books / Religious & Cultural Literature',
                'language' => 'English / Sanskrit / Hindi',
                'non_objection_statement' => 'We hereby declare that the books/publications exported herewith do not contain any objectionable, obscene, political, or prohibited material under Indian law.'
            ]);

        case 'declaration_audio_film':
            return array_merge($base, [
                'document_title' => 'AUDIO / FILM DECLARATION',
                'media_type' => 'Compact Disc (Audio CD) / DVD / Media Storage',
                'content_description' => 'Indian Devotional & Cultural Audio Recording',
                'copyright_statement' => 'We certify that we hold valid rights/licenses for the content recorded on these media discs and they do not violate copyright laws.'
            ]);

        case 'declaration_textile':
            return array_merge($base, [
                'document_title' => 'HANDLOOM / TEXTILE DECLARATION',
                'material_composition' => '100% Pure Cotton / Silk / Handwoven Yarn',
                'fabric_type' => 'Handloom Woven Textile / Garment',
                'textile_statement' => 'We declare that the textile items exported herewith are handloom/handcrafted goods made in India.'
            ]);

        case 'declaration_lacey':
            return array_merge($base, [
                'document_title' => 'LACEY ACT DECLARATION (US CUSTOMS)',
                'genus_species' => 'Tectona grandis / Dalbergia sissoo / Miscellaneous Wood',
                'country_harvested' => 'India',
                'lacey_statement' => 'This declaration is submitted in compliance with the U.S. Lacey Act requirements regarding plant & wood products.'
            ]);

        default:
            return $base;
    }
}
