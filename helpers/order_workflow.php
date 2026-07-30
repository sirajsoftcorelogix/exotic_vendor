<?php

require_once __DIR__ . '/html_helpers.php';

function order_workflow_transition_model(mysqli $conn): WorkflowTransition
{
    require_once __DIR__ . '/../models/workflow/WorkflowTransition.php';

    return new WorkflowTransition($conn);
}

/**
 * Returns an error message when the transition is blocked, or null when allowed.
 */
function assert_order_status_transition_allowed(
    mysqli $conn,
    string $fromSlug,
    string $toSlug,
    int $userId
): ?string {
    unset($userId);

    $fromSlug = strtolower(trim($fromSlug));
    $toSlug = strtolower(trim($toSlug));
    if ($fromSlug === '' || $toSlug === '' || $fromSlug === $toSlug) {
        return null;
    }
    if (isAdministratorUser()) {
        return null;
    }

    $model = order_workflow_transition_model($conn);
    if (!$model->isTransitionAllowedBySlug($fromSlug, $toSlug)) {
        $fromTitle = $model->getStatusTitleBySlug($fromSlug) ?: $fromSlug;
        $toTitle = $model->getStatusTitleBySlug($toSlug) ?: $toSlug;

        return 'Status change from "' . $fromTitle . '" to "' . $toTitle . '" is not allowed.';
    }

    return null;
}

/**
 * @param list<array<string, mixed>> $orderRows
 */
function assert_bulk_order_status_transitions_allowed(
    mysqli $conn,
    array $orderRows,
    string $newStatusSlug,
    int $userId
): ?string {
    foreach ($orderRows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $fromSlug = (string) ($row['status'] ?? '');
        $error = assert_order_status_transition_allowed($conn, $fromSlug, $newStatusSlug, $userId);
        if ($error !== null) {
            $orderRef = trim((string) ($row['order_number'] ?? ''));
            if ($orderRef === '') {
                $orderRef = (string) (int) ($row['id'] ?? 0);
            }

            return $orderRef !== '' && $orderRef !== '0'
                ? 'Order ' . $orderRef . ': ' . $error
                : $error;
        }
    }

    return null;
}

/**
 * @return array{
 *   enforced:bool,
 *   filter_options:bool,
 *   allowed_slugs:list<string>,
 *   stock_affecting_slugs:list<string>
 * }
 */
function order_workflow_allowed_targets(mysqli $conn, string $fromSlug): array
{
    return order_workflow_transition_model($conn)->getAllowedTargetsForFromSlug($fromSlug);
}
