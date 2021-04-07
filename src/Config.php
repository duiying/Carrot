<?php

declare(strict_types=1);

namespace Carrot;

class Config
{
    private static $instance;

    private static $config = [];

    private function __construct()
    {
    }

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 获取配置信息
     *
     * @param $key
     * @param null $default
     * @return mixed|null
     */
    public function get($key, $default = null)
    {
        if (empty(self::$config[$key])) {
            if (!is_file(CONFIG_PATH . $key . '.php')) {
                return $default;
            }
            self::$config[$key] = include CONFIG_PATH . $key . '.php';
        }

        return self::$config[$key];
    }
}
