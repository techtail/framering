<?php
/**
 * Framering - Autoloader
 * @author Matheus Giovani <matheus@ad3com.com.br>
 * @author TechTail <falecom@techtail.net>
 */

namespace Framering\Autoload;

$file_prefix = "Framering";
$prefix_length = strlen($file_prefix);

spl_autoload_register(function($class_name) use ($file_prefix, $prefix_length) {
    if (substr($class_name, 0, $prefix_length) !== $file_prefix) {
        return;
    }

    $parts = explode("\\", $class_name);
    array_shift($parts);

    $filename = __DIR__ . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts) . ".php";

    require_once $filename;
});