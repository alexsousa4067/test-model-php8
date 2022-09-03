<?php

if (file_exists(__DIR__ . "/../../.env")) {
    $lines = file(__DIR__ . "/../../.env");
    foreach ($lines as $line) {
        $line = trim($line);
        if (!empty($line)) {
            putenv($line);
        }
    }
}
