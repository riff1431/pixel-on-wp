<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../../wp-load.php';

try {
    $processor = new \PixelOnWP\Includes\Queue\PixelOnWP_Queue_Processor();
    $processor->process_failed_queue();
    echo "Success!";
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine();
}
