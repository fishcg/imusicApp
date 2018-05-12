<?php
/**
 * Created by PhpStorm.
 * User: tomcao
 * Date: 2017/7/14
 * Time: 11:24
 */
foreach (glob(__DIR__ . '/*.php', GLOB_BRACE) as $file) {
    $file = basename($file);
    switch($file) {
        case 'index.php':
        case 'key_bac.php':
            continue;
        default:
            require(__DIR__ . '/' . $file);
    }
}
