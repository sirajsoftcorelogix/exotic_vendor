<?php

namespace Integrations\Alankit\Support;

/**
 * Payload Builder for GST E-Invoice Schema v1.1 & E-Way Bill JSON payloads.
 */
class PayloadBuilder
{
    /**
     * Build IRN Generation Payload (with optional EWB details).
     *
     * @param array<string, mixed> $invoice
     * @param list<array<string, mixed>> $items
     * @param array<string, mixed> $customer
     * @param array<string, mixed> $firm
     * @param array<string, mixed> $ewbData
     * @return array<string, mixed>
     */
    public static function buildIrnPayload(
        array $invoice,
        array $items,
        array $customer,
        array $firm,
        array $ewbData = []
    ): array {
        $sellerState = sprintf('%02d', (int) ($firm['state_code'] ?? $firm['state'] ?? 7));
        $buyerState = sprintf('%02d', (int) ($customer['state_code'] ?? $invoice['buyer_state_code'] ?? 7));

        $country = strtoupper(trim((string) ($customer['country'] ?? $invoice['country'] ?? 'IN')));
        $isExport = ($country !== 'IN' && $country !== 'INDIA');

        $buyerGstin = strtoupper(trim((string) ($customer['gstin'] ?? $invoice['gstin'] ?? 'URP')));
        if ($buyerGstin === '' || $isExport) {
            $buyerGstin = 'URP';
        }

        $supTyp = self::determineSupplyType($buyerGstin, $isExport, $invoice);

        // Doc Date format DD/MM/YYYY
        $rawDocDt = trim((string) ($invoice['invoice_date'] ?? date('Y-m-d')));
        $docDtFormatted = self::formatDateDdMmYyyy($rawDocDt);

        $docNo = trim((string) ($invoice['invoice_number'] ?? $invoice['doc_no'] ?? 'INV-' . rand(1000, 9999)));

        // Seller details
        $sellerGstin = strtoupper(trim((string) ($firm['gst'] ?? $firm['gstin'] ?? '')));
        $sellerName = trim((string) ($firm['firm_name'] ?? $firm['legal_name'] ?? 'EXOTIC INDIA ART PVT LTD'));
        $sellerAddr1 = self::truncateStr(trim((string) ($firm['address'] ?? 'New Delhi')), 100);
        $sellerLoc = self::truncateStr(trim((string) ($firm['city'] ?? 'New Delhi')), 50);
        $sellerPin = (int) ($firm['pin'] ?? $firm['pincode'] ?? 110055);

        // Buyer details
        $buyerName = trim((string) ($customer['name'] ?? trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''))));
        if ($buyerName === '') {
            $buyerName = 'Valued Customer';
        }

        $buyerAddr1 = self::truncateStr(trim((string) ($customer['address'] ?? $customer['address_line1'] ?? 'Customer Address')), 100);
        $buyerLoc = self::truncateStr(trim((string) ($customer['city'] ?? 'New Delhi')), 50);
        $buyerPin = (int) ($customer['pin'] ?? $customer['zipcode'] ?? ($isExport ? 999999 : 110001));

        $pos = sprintf('%02d', (int) ($customer['state_code'] ?? ($isExport ? 96 : $buyerState)));

        // Process line items and compute tax split
        $isIntrastate = ($sellerState === $buyerState) && !$isExport;
        $itemList = [];
        $totalTaxable = 0.0;
        $totalCgst = 0.0;
        $totalSgst = 0.0;
        $totalIgst = 0.0;
        $totalDiscount = 0.0;

        foreach ($items as $idx => $item) {
            $sn = (string) ($idx + 1);
            $title = self::truncateStr(trim((string) ($item['item_name'] ?? $item['title'] ?? 'Goods Item')), 100);
            $hsn = preg_replace('/[^0-9]/', '', (string) ($item['hsn'] ?? '1001'));
            $hsn = strlen($hsn) >= 4 ? substr($hsn, 0, 8) : '1001';

            $qty = max((float) ($item['quantity'] ?? $item['qty'] ?? 1), 0.001);
            $unitPrice = max((float) ($item['unit_price'] ?? $item['price'] ?? 0), 0.0);
            $totAmt = round($qty * $unitPrice, 2);

            $discount = round((float) ($item['discount'] ?? 0), 2);
            $assAmt = round(max($totAmt - $discount, 0.0), 2);

            $gstRate = (float) ($item['tax_rate'] ?? $item['gst_rate'] ?? 0);

            $cgstAmt = 0.0;
            $sgstAmt = 0.0;
            $igstAmt = 0.0;

            if ($isIntrastate) {
                $halfRate = $gstRate / 2.0;
                $cgstAmt = round($assAmt * ($halfRate / 100.0), 2);
                $sgstAmt = round($assAmt * ($halfRate / 100.0), 2);
            } else {
                $igstAmt = round($assAmt * ($gstRate / 100.0), 2);
            }

            $totItemVal = round($assAmt + $cgstAmt + $sgstAmt + $igstAmt, 2);

            $totalTaxable += $assAmt;
            $totalCgst += $cgstAmt;
            $totalSgst += $sgstAmt;
            $totalIgst += $igstAmt;
            $totalDiscount += $discount;

            $itemRow = [
                'SlNo' => $sn,
                'PrdDesc' => $title,
                'IsServc' => 'N',
                'HsnCd' => $hsn,
                'Qty' => round($qty, 3),
                'Unit' => 'NOS',
                'UnitPrice' => round($unitPrice, 2),
                'TotAmt' => round($totAmt, 2),
                'Discount' => round($discount, 2),
                'AssAmt' => round($assAmt, 2),
                'GstRt' => round($gstRate, 2),
                'IgstAmt' => round($igstAmt, 2),
                'CgstAmt' => round($cgstAmt, 2),
                'SgstAmt' => round($sgstAmt, 2),
                'TotItemVal' => round($totItemVal, 2),
            ];

            $itemList[] = $itemRow;
        }

        $totalInvVal = round($totalTaxable + $totalCgst + $totalSgst + $totalIgst, 2);

        $payload = [
            'Version' => '1.1',
            'TranDtls' => [
                'TaxSch' => 'GST',
                'SupTyp' => $supTyp,
                'RegRev' => 'N',
                'IgstOnIntra' => 'N',
            ],
            'DocDtls' => [
                'Typ' => 'INV',
                'No' => $docNo,
                'Dt' => $docDtFormatted,
            ],
            'SellerDtls' => [
                'Gstin' => $sellerGstin,
                'LglName' => $sellerName,
                'TrdName' => $sellerName,
                'Addr1' => $sellerAddr1,
                'Loc' => $sellerLoc,
                'Pin' => $sellerPin,
                'Stcd' => $sellerState,
            ],
            'BuyerDtls' => [
                'Gstin' => $buyerGstin,
                'LglName' => $buyerName,
                'TrdName' => $buyerName,
                'Pos' => $pos,
                'Addr1' => $buyerAddr1,
                'Loc' => $buyerLoc,
                'Pin' => $buyerPin,
                'Stcd' => $buyerState,
            ],
            'ItemList' => $itemList,
            'ValDtls' => [
                'AssVal' => round($totalTaxable, 2),
                'CgstVal' => round($totalCgst, 2),
                'SgstVal' => round($totalSgst, 2),
                'IgstVal' => round($totalIgst, 2),
                'Discount' => round($totalDiscount, 2),
                'OthChrg' => 0.0,
                'RndOffAmt' => 0.0,
                'TotInvVal' => round($totalInvVal, 2),
            ],
        ];

        // Add Export details if Export
        if ($isExport) {
            $shipBNo = trim((string) ($invoice['shipping_bill_number'] ?? 'SB-' . rand(100000, 999999)));
            $shipBDt = self::formatDateDdMmYyyy((string) ($invoice['shipping_bill_date'] ?? date('Y-m-d')));

            $payload['ExpDtls'] = [
                'ShipBNo' => $shipBNo,
                'ShipBDt' => $shipBDt,
                'Port' => trim((string) ($invoice['shipping_port'] ?? 'INABG1')),
                'RefClm' => 'N',
                'ForCur' => trim((string) ($invoice['currency'] ?? 'USD')),
                'CntCode' => substr($country, 0, 2),
            ];
        }

        // Add EWB details if vehicle details provided
        if (!empty($ewbData['veh_no'])) {
            $payload['EwbDtls'] = [
                'TransId' => trim((string) ($ewbData['trans_id'] ?? '')),
                'TransName' => trim((string) ($ewbData['trans_name'] ?? '')),
                'TransMode' => trim((string) ($ewbData['trans_mode'] ?? '1')),
                'Distance' => max((int) ($ewbData['distance'] ?? 100), 1),
                'TransDocNo' => trim((string) ($ewbData['trans_doc_no'] ?? '')),
                'TransDocDt' => !empty($ewbData['trans_doc_dt']) ? self::formatDateDdMmYyyy((string) $ewbData['trans_doc_dt']) : '',
                'VehNo' => strtoupper(trim((string) $ewbData['veh_no'])),
                'VehType' => strtoupper(trim((string) ($ewbData['veh_type'] ?? 'R'))),
            ];
        }

        return $payload;
    }

    /**
     * Determine GST Supply Type (SupTyp).
     */
    public static function determineSupplyType(string $buyerGstin, bool $isExport, array $invoice = []): string
    {
        if ($isExport) {
            $withPay = !empty($invoice['export_with_payment']);
            return $withPay ? 'EXPWP' : 'EXPWOP';
        }

        if ($buyerGstin !== '' && $buyerGstin !== 'URP') {
            return 'B2B';
        }

        return 'B2C';
    }

    private static function formatDateDdMmYyyy(string $rawDate): string
    {
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $rawDate)) {
            return $rawDate;
        }

        try {
            $dt = new \DateTime($rawDate);
            return $dt->format('d/m/Y');
        } catch (\Throwable $e) {
            return date('d/m/Y');
        }
    }

    private static function truncateStr(string $str, int $maxLen): string
    {
        if (mb_strlen($str, 'UTF-8') <= $maxLen) {
            return $str;
        }

        return mb_substr($str, 0, $maxLen, 'UTF-8');
    }
}
