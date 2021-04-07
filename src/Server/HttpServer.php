<?php

namespace Carrot\Server;

use Carrot\Application;
use Carrot\Route;

class HttpServer
{
    private $_server;

    private $_config;

    private $_route;

    public static $pidFile = BASE_PATH . 'master.pid';

    public function __construct()
    {
        $config = config('servers');
        $httpConfig = $config['http'];
        $this->_config = $httpConfig;

        $this->_server = new \Swoole\Http\Server($httpConfig['ip'], $httpConfig['port'], $config['mode'], $httpConfig['sock_type']);

        $this->_server->on('workerStart', [$this, 'onWorkerStart']);
        $this->_server->on('request', [$this, 'onRequest']);
        if ($config['mode'] == SWOOLE_BASE) {
            $this->_server->on('managerStart', [$this, 'onManagerStart']);
        } else {
            $this->_server->on('start', [$this, 'onStart']);
        }

        $this->_server->set($httpConfig['settings']);

        $this->_server->start();
    }

    public function onStart(\Swoole\Server $server)
    {
        // 记录进程 id，通过脚本实现自动重启
        $pid = $server->master_pid;
        file_put_contents(self::$pidFile, $pid);
        Application::printSuccess("Swoole Http Server running：http://{$this->_config['ip']}:{$this->_config['port']}");
    }

    public function onManagerStart(\Swoole\Server $server)
    {
        Application::printSuccess("Swoole Http Server running：http://{$this->_config['ip']}:{$this->_config['port']}");
    }

    public function onWorkerStart(\Swoole\Server $server, int $workerId)
    {
        $this->_route = Route::getInstance();
    }

    public function onRequest(\Swoole\Http\Request $request, \Swoole\Http\Response $response)
    {
        $this->_route->dispatch($request, $response);
    }
}