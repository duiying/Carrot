<?php

namespace Carrot\Lib;

class Logger
{
    // 日志目录
    private $dir        = '/data/logs/common';

    /**
     * 获取 Logger 实例
     *
     * @param string $dir
     * @return Logger
     */
    public static function getInstance(string $dir = '')
    {
        $logger = new self();
        $logger->setDir($dir);
        return $logger;
    }

    /**
     * 设置目录
     *
     * @param string $dir
     * @return $this
     */
    public function setDir(string $dir = '')
    {
        if (empty($dir)) {
            $dir = $this->dir;
        }
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        $this->dir  = $dir;
        return $this;
    }

    /**
     * 组装日志内容
     *
     * @param $msg
     * @param $data
     * @return string
     */
    public static function assembleContent($msg, $data)
    {
        $content = [
            'time'      => date('Y-m-d H:i:s'),
            'message'   => $msg,
            'data'      => $data
        ];
        return json_encode($content) . PHP_EOL;
    }

    public function info($msg, $data)
    {
        @file_put_contents($this->dir . date('Ymd') . '.info.log', self::assembleContent($msg, $data), FILE_APPEND | LOCK_EX);
    }

    public function debug($msg, $data)
    {
        @file_put_contents($this->dir . date('Ymd') . '.debug.log', self::assembleContent($msg, $data), FILE_APPEND | LOCK_EX);
    }

    public function error($msg, $data)
    {
        @file_put_contents($this->dir . date('Ymd') . '.error.log', self::assembleContent($msg, $data), FILE_APPEND | LOCK_EX);
    }

    public function success($msg, $data)
    {
        @file_put_contents($this->dir . date('Ymd') . '.success.log', self::assembleContent($msg, $data), FILE_APPEND | LOCK_EX);
    }
}