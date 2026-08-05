<?php
require_once '../../../wp-load.php';
$activator = new \PixelOnWP\PixelOnWP_Activator();
$activator::activate();
echo "Activated";
