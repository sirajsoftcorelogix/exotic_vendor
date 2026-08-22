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
        $docs['commercial_invoice'] = 'Commercial Invoice';

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

    $destCountry = trim((string)($inv['shipping_country'] ?: ($inv['country'] ?: '')));
    $destCity = trim((string)($inv['shipping_city'] ?: ($inv['city'] ?: '')));
    $finalDest = $destCity ? $destCity . ($destCountry ? ', ' . $destCountry : '') : $destCountry;

    $consigneeName = trim(($inv['shipping_first_name'] ?? '') . ' ' . ($inv['shipping_last_name'] ?? ''));
    if ($consigneeName === '') {
        $consigneeName = trim(($inv['first_name'] ?? '') . ' ' . ($inv['last_name'] ?? ''));
    }
    if ($consigneeName === '') {
        $consigneeName = trim((string)($inv['customer_master_name'] ?? ''));
    }

    $consigneeAddr1 = $inv['shipping_address_line1'] ?: ($inv['address_line1'] ?: '');
    $consigneeAddr2 = $inv['shipping_address_line2'] ?: ($inv['address_line2'] ?: '');
    $consigneeZip = $inv['shipping_zipcode'] ?: ($inv['zipcode'] ?: '');
    $consigneeState = $inv['shipping_state'] ?: ($inv['state'] ?: '');

    $currency = strtoupper(trim((string)($inv['currency'] ?? 'USD')));
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
        'consignee_phone' => $inv['mobile'] ?: ($inv['customer_master_phone'] ?: ''),
        'consignee_email' => $inv['email'] ?: ($inv['customer_master_email'] ?: ''),
        'port_of_loading' => $intl['port_of_loading'] ?? 'New Delhi (INABG1)',
        'port_of_discharge' => $intl['port_of_discharge'] ?? $destCity,
        'country_of_origin' => 'India',
        'country_of_destination' => $destCountry,
        'final_destination' => $finalDest,
        'currency' => $currency,
        'exchange_rate' => (float)($intl['usd_export_rate'] ?? 83.50),
        'total_amount' => (float)($inv['total_amount'] ?? 0),
        'gross_weight' => $totalGrossWeight,
        'net_weight' => round($totalNetWeight, 2),
        'total_packages' => 1,
        'terms_of_delivery' => 'DDP / Express Courier',
        'declaration_date' => date('Y-m-d'),
        'authorized_signatory' => $firm['authorized_signatory'] ?? 'Authorized Signatory'
    ];
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
            'hsn_code' => $it['hsn_code'] ?? '',
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
                'document_title' => 'COMMERCIAL INVOICE',
                'terms_of_payment' => 'Prepaid / Advance Payment',
                'buyer_order_ref' => $commonData['order_number'] ?? '',
                'buyer_order_date' => $commonData['invoice_date'] ?? date('Y-m-d'),
                'lut_number' => 'AD070324001234X',
                'declaration_clause' => 'We declare that this invoice shows the actual price of the goods described and that all particulars are true and correct. Export under LUT without payment of Integrated Tax.'
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
