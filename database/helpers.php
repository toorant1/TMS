<?php
require_once __DIR__ . '/config.php';

function base_url($path = '') {
    return BASE_URL . ltrim($path, '/');
}
?>
