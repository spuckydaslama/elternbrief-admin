<?php
$file = "../ebg-tracking/ebg-prod.txt";
$content = file_get_contents($file);
header('Content-Type: application/text');
echo $content;
