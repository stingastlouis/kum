<?php

function redirectWithMessage($location, $message, $success = false)
{
    $type = $success ? 'success' : 'error';
    header("Location: {$location}?{$type}=" . urlencode($message));
    exit;
}
