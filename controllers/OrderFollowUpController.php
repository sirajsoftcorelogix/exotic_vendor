<?php

require_once __DIR__ . '/../helpers/order_follow_up.php';
require_once __DIR__ . '/../models/order_follow_up/OrderFollowUp.php';

class OrderFollowUpController
{
    public function start(): void
    {
        is_login();
        global $conn;

        if (!canSrEmpAccess()) {
            $_SESSION['order_follow_up_flash'] = ['type' => 'error', 'text' => 'Access denied.'];
            header('Location: ' . base_url('index.php?page=posorders&action=list'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . base_url('index.php?page=posorders&action=list'));
            exit;
        }

        $sourceOrderNumber = trim((string) ($_POST['source_order_number'] ?? ''));
        $followUpType = strtolower(trim((string) ($_POST['follow_up_type'] ?? 'copy')));
        $pricingMode = strtolower(trim((string) ($_POST['pricing_mode'] ?? '')));
        $lineIdsRaw = $_POST['line_ids'] ?? [];
        $lineIds = is_array($lineIdsRaw)
            ? array_values(array_filter(array_map('intval', $lineIdsRaw), static fn (int $id): bool => $id > 0))
            : [];

        if ($pricingMode === '') {
            $pricingMode = OrderFollowUp::defaultPricingModeForType($followUpType);
        }

        $result = order_follow_up_start_session(
            $conn,
            $sourceOrderNumber,
            $followUpType,
            $pricingMode,
            $lineIds
        );

        if (empty($result['success'])) {
            $_SESSION['order_follow_up_flash'] = [
                'type' => 'error',
                'text' => (string) ($result['message'] ?? 'Could not start follow-up order.'),
            ];
            $backPage = in_array(trim((string) ($_POST['return_page'] ?? '')), ['orders', 'posorders'], true)
                ? trim((string) $_POST['return_page'])
                : 'posorders';
            header('Location: ' . order_follow_up_order_details_url($sourceOrderNumber, $backPage));
            exit;
        }

        header('Location: ' . base_url('index.php?page=pos_register&action=list&follow_up_seed=1'));
        exit;
    }

    public function preview(): void
    {
        is_login();
        global $conn;
        header('Content-Type: application/json; charset=utf-8');

        if (!canSrEmpAccess()) {
            echo json_encode(['success' => false, 'message' => 'Access denied.']);
            exit;
        }

        $sourceOrderNumber = trim((string) ($_GET['source_order_number'] ?? ''));
        $model = new OrderFollowUp($conn);
        $eligibility = $model->resolveStartEligibility($sourceOrderNumber);

        echo json_encode([
            'success' => !empty($eligibility['can_start']),
            'eligibility' => $eligibility,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
