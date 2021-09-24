<?php

namespace Carrot;

class HttpUtil
{
    /**
     * HTTP 成功响应数据
     *
     * @param array $data
     * @param string $msg
     * @param int $code
     * @return false|string
     */
    public static function success($data = [], $msg = 'success', $code = 0)
    {
        $formatData = [
            'code'  => $code,
            'msg'   => $msg,
            'data'  => $data
        ];

        return json_encode($formatData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * HTTP 失败响应数据
     *
     * @param string $msg
     * @param int $code
     * @param array $data
     * @return false|string
     */
    public static function error($msg = '服务异常，请稍后再试', $code = 500,  $data = [])
    {
        $formatData = [
            'code'  => $code,
            'msg'   => $msg,
            'data'  => $data
        ];

        return json_encode($formatData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}