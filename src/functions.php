<?php

declare(strict_types=1);

/**
 * 获取实例方法
 */
if (!function_exists('getInstance')) {
    function getInstance($class)
    {
        return ($class)::getInstance();
    }
}

/**
 * 获取配置方法
 */
if (!function_exists('config')) {
    function config($name, $default = null)
    {
        return getInstance('\Carrot\Config')->get($name, $default);
    }
}
