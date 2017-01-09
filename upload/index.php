<?php
// Version
define('VERSION', '2.3.0.2');
// debug hack start
error_reporting(E_ALL); ini_set('display_errors', 1);
// debug hack endd

// Configuration
if (is_file('config.php')) {
	require_once('config.php');
}

// Install
if (!defined('DIR_APPLICATION')) {
	header('Location: install/index.php');
	exit;
}

function m()
{
    //     return;
    $args = func_get_args();
    foreach ($args as $arg) {
        echo '<pre>';
        print_r($arg);
        echo '</pre>';
    }
}
function mm()
{
    //     return;
    $args = func_get_args();
    foreach ($args as $arg) {
        echo '<pre>';
        print_r($arg);
        echo '</pre>';
    }
    die;
}

// Startup
require_once(DIR_SYSTEM . 'startup.php');

start('catalog');