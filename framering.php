<?php
/**
 * Plugin Name: Framering
 * Description: A WordPress framework built for creating and prototyping websites faster than ever.
 * Author: TechTail
 * Author URI: https://github.com/techtail/framering
 * Text Domain: framering
 */

!defined("ABSPATH") && exit;

define("FRAMERING_PREFIX", "fr_");

require_once __DIR__ . "/autoload.php";

\Framering\Core::Instance()->init();