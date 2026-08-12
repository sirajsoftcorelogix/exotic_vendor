<?php

namespace Integrations\Alankit\Services;

use mysqli;
use Integrations\Alankit\Config\AlankitConfig;
use Integrations\Alankit\Clients\AlankitApiClient;
use Integrations\Alankit\Support\PayloadBuilder;
use Integrations\Alankit\Validators\PreFlightValidator;

/**
 * Domain Service orchestrating authentication, encryption, payload validation,
 * API requests to Alankit, and database persistence.
 */
class EInvoiceService
{
    private mysqli $db;
    private AlankitConfig $config;
    private AlankitApiClient $apiClient;

    public function __construct(mysqli $db, AlankitConfig $config)
    {
        $this->db = $db;
        $this->config = $config;
        $this->apiClient = new AlankitApiClient($config);
    }

    /**
     * Generate IRN (and optional E-Way Bill) for an invoice.
     *
     * @param int $invoiceId
     * @param array<string, mixed> $invoice
     * @param list<array<string, mixed>> $items
     * @param array<string, mixed> $customer
     * @param array<string, mixed> $firm
     * @param array<string, mixed> $ewbData
     * @return array<string, mixed>
     */
    public function generateIrn(
        int $invoiceId,
        array $invoice,
        array $items,
        array $customer,
        array $firm,
        array $ewbData = []
    ): array {
        // 1. Pre-flight validation
        $val = PreFlightValidator::validateIrnRequest($invoice, $items, $customer, $firm);
        if (!$val['valid']) {
            return [
                'status' => false,
                'message' => 'Pre-flight validation failed: ' . implode('; ', $val['errors']),
                'errors' => $val['errors'],
            ];
        }

        // 2. Ensure database record
        $this->ensureEwbIrnRecord($invoiceId);

        // 3. Build Auth Payload & Request Token
        $authPayload = $this->apiClient->createAuthPayload();
        if (!$authPayload) {
            $msg = 'Failed to build RSA authentication payload. Check public key path.';
            $this->updateIrnStatus($invoiceId, 'failed', $msg);
            return ['status' => false, 'message' => $msg, 'errors' => [$msg]];
        }

        $authResponse = $this->apiClient->sendRequest(AlankitApiClient::AUTH_ENDPOINT, ['Data' => $authPayload]);
        if (!$authResponse || empty($authResponse['Data']['AuthToken']) || empty($authResponse['Data']['Sek'])) {
            $msg = 'Authentication failed: ' . ($authResponse['message'] ?? $authResponse['Error']['message'] ?? 'Unknown error from Alankit Auth.');
            $this->updateIrnStatus($invoiceId, 'failed', $msg);
            return ['status' => false, 'message' => $msg, 'errors' => [$msg]];
        }

        $accessToken = $authResponse['Data']['AuthToken'];
        $encryptedSek = $authResponse['Data']['Sek'];

        // 4. Decrypt SEK
        $decryptedSek = $this->apiClient->decryptSek($encryptedSek);
        if (!$decryptedSek) {
            $msg = 'Failed to decrypt Symmetric Encryption Key (SEK).';
            $this->updateIrnStatus($invoiceId, 'failed', $msg);
            return ['status' => false, 'message' => $msg, 'errors' => [$msg]];
        }

        // 5. Build and Encrypt IRN Payload
        $irnPayload = PayloadBuilder::buildIrnPayload($invoice, $items, $customer, $firm, $ewbData);
        $encryptedPayload = $this->apiClient->encryptPayload($irnPayload, $decryptedSek);

        if (!$encryptedPayload) {
            $msg = 'Failed to encrypt IRN payload.';
            $this->updateIrnStatus($invoiceId, 'failed', $msg, $irnPayload);
            return ['status' => false, 'message' => $msg, 'errors' => [$msg]];
        }

        // 6. Send IRN Request
        $irnResponse = $this->apiClient->sendRequest(
            AlankitApiClient::IRN_GENERATE_ENDPOINT,
            ['Data' => $encryptedPayload],
            $accessToken
        );

        if (!$irnResponse || empty($irnResponse['Data'])) {
            $msg = 'IRN API request failed: ' . ($irnResponse['message'] ?? 'Empty response from Alankit.');
            $this->updateIrnStatus($invoiceId, 'failed', $msg, $irnPayload, $irnResponse);
            return ['status' => false, 'message' => $msg, 'errors' => [$msg]];
        }

        // 7. Decrypt Response
        $decryptedResp = $this->apiClient->decryptPayload($irnResponse['Data'], $decryptedSek);
        if (!$decryptedResp) {
            $msg = 'Failed to decrypt IRN response payload.';
            $this->updateIrnStatus($invoiceId, 'failed', $msg, $irnPayload, $irnResponse);
            return ['status' => false, 'message' => $msg, 'errors' => [$msg]];
        }

        // 8. Process Response / Duplicate Detection
        $irn = $decryptedResp['Irn'] ?? $decryptedResp['irn'] ?? null;
        $ackNo = $decryptedResp['AckNo'] ?? $decryptedResp['ack_no'] ?? null;
        $ackDt = $decryptedResp['AckDt'] ?? $decryptedResp['ack_dt'] ?? null;
        $ewbNo = $decryptedResp['EwbNo'] ?? $decryptedResp['ewb_no'] ?? null;

        // Check for duplicate IRN details in InfoDtls
        if (!$irn && !empty($decryptedResp['InfoDtls'])) {
            foreach ($decryptedResp['InfoDtls'] as $info) {
                if (!empty($info['Desc']['Irn'])) {
                    $irn = $info['Desc']['Irn'];
                    $ackNo = $info['Desc']['AckNo'] ?? $ackNo;
                    $ackDt = $info['Desc']['AckDt'] ?? $ackDt;
                    break;
                }
            }
        }

        if ($irn) {
            $this->updateIrnStatus($invoiceId, 'generated', null, $irnPayload, $decryptedResp, $irn);
            $this->syncInvoiceTable($invoiceId, $irn, $ewbNo, $ackNo, $ackDt);

            return [
                'status' => true,
                'irn' => $irn,
                'ack_number' => $ackNo,
                'ack_date' => $ackDt,
                'ewb' => $ewbNo,
                'message' => 'E-Invoice (IRN) generated successfully.',
                'raw' => $decryptedResp,
            ];
        }

        $errMsg = $decryptedResp['ErrorDetails'][0]['ErrorMessage'] ?? $decryptedResp['message'] ?? 'Failed to obtain IRN.';
        $this->updateIrnStatus($invoiceId, 'failed', $errMsg, $irnPayload, $decryptedResp);

        return [
            'status' => false,
            'message' => $errMsg,
            'errors' => [$errMsg],
            'raw' => $decryptedResp,
        ];
    }

    private function ensureEwbIrnRecord(int $invoiceId): void
    {
        $stmt = $this->db->prepare("SELECT id FROM vp_domestic_ewb_irn WHERE vp_invoices_id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("i", $invoiceId);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows > 0) {
                $stmt->close();
                return;
            }
            $stmt->close();
        }

        $ins = $this->db->prepare("INSERT INTO vp_domestic_ewb_irn (vp_invoices_id, irn_status, ewb_status) VALUES (?, 'pending', 'pending')");
        if ($ins) {
            $ins->bind_param("i", $invoiceId);
            $ins->execute();
            $ins->close();
        }
    }

    private function updateIrnStatus(
        int $invoiceId,
        string $status,
        ?string $error = null,
        ?array $payload = null,
        ?array $response = null,
        ?string $irn = null
    ): void {
        $stmt = $this->db->prepare("
            UPDATE vp_domestic_ewb_irn
            SET irn_status = ?, irn_error = ?, irn_payload = ?, irn_response = ?, irn = ?, irn_generated_at = NOW()
            WHERE vp_invoices_id = ?
        ");

        if ($stmt) {
            $pJson = $payload ? json_encode($payload) : null;
            $rJson = $response ? json_encode($response) : null;
            $stmt->bind_param("sssssi", $status, $error, $pJson, $rJson, $irn, $invoiceId);
            $stmt->execute();
            $stmt->close();
        }
    }

    private function syncInvoiceTable(
        int $invoiceId,
        string $irn,
        ?string $ewb = null,
        ?string $ackNo = null,
        ?string $ackDt = null
    ): void {
        $stmt = @$this->db->prepare("
            UPDATE vp_invoices
            SET irn = ?, ewb_number = COALESCE(?, ewb_number), ack_number = COALESCE(?, ack_number), ack_date = COALESCE(?, ack_date)
            WHERE id = ?
        ");

        if ($stmt) {
            $stmt->bind_param("ssssi", $irn, $ewb, $ackNo, $ackDt, $invoiceId);
            $stmt->execute();
            $stmt->close();
        }
    }
}
