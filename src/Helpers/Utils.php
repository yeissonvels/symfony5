<?php
/**
 * Created by PhpStorm.
 * User: yeisson
 * Date: 2020-09-30
 * Time: 19:15
 */

namespace App\Helpers;


class Utils
{
    static function pre($object) {
        echo '<pre>';
        print_r($object);
        echo '</pre>';
    }
}