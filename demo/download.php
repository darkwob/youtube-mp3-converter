<?php
if (empty($_GET['file'])) {
    die('File parameter is required');
}

$file = __DIR__ . '/downloads/' . basename($_GET['file']);

if (!file_exists($file)) {
    die('File not found');
}

header('Content-Type: audio/mpeg');
header('Content-Disposition: attachment; filename="' . basename($file) . '"');
header('Content-Length: ' . filesize($file));
header('Cache-Control: no-cache');

readfile($file); 