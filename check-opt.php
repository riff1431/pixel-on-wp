<?php
require_once '../../../wp-load.php';
$option = get_option('PixelOnWP_cookie_consent', []);
echo json_encode($option, JSON_PRETTY_PRINT);
