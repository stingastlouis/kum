<?php

function getFilteredStatuses(array $statuses, string $latestStatus, bool $isDelivery = false): array
{
    $filtered = [];

    foreach ($statuses as $status) {

        $statusName = strtoupper(trim($status['StatusName']));
        switch ($latestStatus) {
            case 'PROCESSING':
                if (in_array($statusName, ['CONFIRMED', 'CANCELLED'])) {
                    $filtered[] = $status;
                }
                break;
            case 'CONFIRMED':
                if (in_array($statusName, ['CANCELLED'])) {
                    $filtered[] = $status;
                }
                break;
            case 'READY TO BAKE':
                if (in_array($statusName, ['CANCELLED', 'BAKED'])) {
                    $filtered[] = $status;
                }
                break;
            case 'OUT FOR DELIVERY':
                if (in_array($statusName, ['DELIVERED'])) {
                    $filtered[] = $status;
                }
                break;

            case 'PENDING':
                if (in_array($statusName, ['OUT FOR DELIVERY'])) {
                    $filtered[] = $status;
                }
                break;
            case 'READY FOR PICKUP':
                if (in_array($statusName, ['COLLECTED'])) {
                    $filtered[] = $status;
                }
                break;
            case 'COLLECTED':
                if (in_array($statusName, ['COMPLETED'])) {
                    $filtered[] = $status;
                }
                break;
            case 'BAKED':
                $allowedStatuses = [];

                if ($isDelivery) {
                    $allowedStatuses[] = 'READY FOR DELIVERY';
                } else {
                    $allowedStatuses[] = 'READY FOR PICKUP';
                }

                if (in_array($statusName, $allowedStatuses)) {
                    $filtered[] = $status;
                }
                break;
            default:
                $filtered[] = $status;
                break;
        }
    }

    return $filtered;
}


function isOrderAndDeliveCompletedStatus(string $status): bool
{
    if ($status === null) {
        return false;
    }

    $finalStatuses = ['DELIVERED', 'CANCELLED', 'COMPLETED'];

    return in_array(strtoupper($status), $finalStatuses);
}
