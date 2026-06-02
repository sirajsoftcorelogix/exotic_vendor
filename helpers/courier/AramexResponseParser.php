<?php

/**
 * Normalizes Aramex SOAP responses for dispatch UI and courier_shipments.
 */
class AramexResponseParser
{
    /**
     * @param mixed $data SOAP response object or array
     * @return array{success:bool,awb?:string,label_url?:string,partner_shipment_id?:string,message?:string,errors?:list<mixed>}
     */
    public static function parseCreateShipment($data): array
    {
        $arr = self::toArray($data);
        if (!$arr) {
            return ['success' => false, 'message' => 'Empty Aramex create shipment response.'];
        }

        if (!empty($arr['HasErrors'])) {
            return [
                'success' => false,
                'message' => self::formatNotifications($arr['Notifications'] ?? null) ?: 'Aramex create shipment failed.',
                'errors' => self::toArray($arr['Notifications'] ?? null),
            ];
        }

        $processed = self::firstProcessedShipment($arr);
        if (!$processed) {
            return ['success' => false, 'message' => 'Aramex response missing ProcessedShipment.'];
        }

        if (!empty($processed['HasErrors'])) {
            return [
                'success' => false,
                'message' => self::formatNotifications($processed['Notifications'] ?? null) ?: 'Aramex shipment rejected.',
                'errors' => self::toArray($processed['Notifications'] ?? null),
            ];
        }

        $awb = trim((string) ($processed['ID'] ?? $processed['Id'] ?? ''));
        $labelUrl = trim((string) (
            $processed['ShipmentLabel']['LabelURL']
            ?? $processed['ShipmentLabel']['LabelUrl']
            ?? $processed['LabelURL']
            ?? $processed['LabelUrl']
            ?? ''
        ));

        if ($awb === '') {
            return ['success' => false, 'message' => 'Aramex did not return an AWB number.'];
        }

        return [
            'success' => true,
            'awb' => $awb,
            'label_url' => $labelUrl !== '' ? $labelUrl : null,
            'partner_shipment_id' => $awb,
        ];
    }

    /** @param mixed $notifications */
    private static function formatNotifications($notifications): string
    {
        $rows = self::toArray($notifications);
        if (!$rows) {
            return '';
        }

        if (isset($rows['Message']) || isset($rows['Code'])) {
            $rows = [$rows];
        }

        $parts = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $msg = trim((string) ($row['Message'] ?? ''));
            $code = trim((string) ($row['Code'] ?? ''));
            if ($msg !== '') {
                $parts[] = $code !== '' ? ($code . ': ' . $msg) : $msg;
            }
        }

        return implode('; ', $parts);
    }

    /** @param array<string, mixed> $arr */
    private static function firstProcessedShipment(array $arr): ?array
    {
        $shipments = $arr['Shipments'] ?? null;
        if (!is_array($shipments)) {
            return null;
        }

        $processed = $shipments['ProcessedShipment'] ?? $shipments['ProcessedShipments'] ?? null;
        if (!is_array($processed)) {
            return null;
        }

        if (self::isList($processed)) {
            return is_array($processed[0] ?? null) ? $processed[0] : null;
        }

        return $processed;
    }

    /** @return array<string, mixed>|list<mixed>|null */
    private static function toArray($data)
    {
        if ($data === null) {
            return null;
        }
        if (is_array($data)) {
            return $data;
        }
        $decoded = json_decode(json_encode($data), true);
        return is_array($decoded) ? $decoded : null;
    }

    /** @param array<mixed> $arr */
    private static function isList(array $arr): bool
    {
        if ($arr === []) {
            return true;
        }
        return array_keys($arr) === range(0, count($arr) - 1);
    }
}
