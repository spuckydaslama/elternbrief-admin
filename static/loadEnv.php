<?php
$file = "../ebg-postkarten/.env";
$content = file_get_contents($file);
header('Content-Type: application/json');
echo $content;
