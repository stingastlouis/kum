<?php

function getFilteredStatuses(array $statuses, string $latestStatus): array
{
    $filtered = [];

    foreach ($statuses as $status) {

        $statusName = strtoupper($status['StatusName']);

        switch ($latestStatus) {
            case 'PROCESSING':
                if (in_array($statusName, ['CONFIRMED', 'CANCELLED'])) {
                    $filtered[] = $status;
                }
                break;
            case 'CONFIRMED':
                if ($statusName !== 'CONFIRMED') {
                    $filtered[] = $status;
                }
                break;
            case 'READY TO BAKE':
                if (in_array($statusName, ['READY FOR PICKUP', 'READY FOR DELIVERY'])) {
                    $filtered[] = $status;
                }
                break;
            case 'BAKED':
                if (in_array($statusName, ['COMPLETED'])) {
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
    $finalStatuses = ['DELIVERED', 'COLLECTED', 'CANCELLED', 'COMPLETED'];

    return in_array(strtoupper($status), $finalStatuses);
}
