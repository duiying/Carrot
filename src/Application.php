<?php

declare(strict_types=1);

namespace Carrot;

use Carrot\Server\HttpServer;
use Carrot\Server\WebsocketServer;
use Swoole\Process;

class Application
{
    const CARROT_VERSION = '1.0.0';

    public static function welcome()
    {
        $frameVersion = self::CARROT_VERSION;
        $swooleVersion = SWOOLE_VERSION;
        echo "Welcome Carrot Version : {$frameVersion}, Swoole Version : {$swooleVersion}" . PHP_EOL;
    }

    public static function println($strings)
    {
        echo $strings . PHP_EOL;
    }

    public static function printSuccess($msg)
    {
        self::println('[' . date('Y-m-d H:i:s') . '] [INFO] ' . "\033[32m{$msg}\033[0m");
    }

    public static function printError($msg)
    {
        self::println('[' . date('Y-m-d H:i:s') . '] [ERROR] ' . "\033[31m{$msg}\033[0m");
    }

    public static function run()
    {
        // 输出框架欢迎语
        self::welcome();
        global $argv;
        $argvCount = count($argv);
        $funcName = $argv[$argvCount - 1];
        $command = explode(':', $funcName);

        // 服务
        switch ($command[0]) {
            case 'http':
                $className = HttpServer::class;
                break;
            case 'ws':
                $className = WebsocketServer::class;
                break;
            default:
                // 用户自定义server
                $serverConfig = config('servers', []);
                if (isset($serverConfig[$command[0]], $serverConfig[$command[0]]['class_name'])) {
                    $className = $serverConfig[$command[0]]['class_name'];
                } else {
                    exit(self::printError("command {$command[0]} is not exist, you can use {$argv[0]} [http:start, ws:start]"));
                }
        }

        if ($command[0] == 'http') {
            $pid = (int)file_get_contents(HttpServer::$pidFile);
        } else {
            $pid = null;
        }

        // 动作
        switch ($command[1]) {
            case 'start':
                new $className();
                break;
            case 'stop':
                if ($command[0] == 'http') {
                    if ($pid){
                        Process::kill($pid, SIGTERM);
                        self::printSuccess('Swoole Http Server Stoped');
                    } else {
                        self::printError('PidFile Not Found');
                    }
                }
                break;
            case 'reload':
                if ($command[0] == 'http') {
                    if ($pid){
                        Process::kill($pid, SIGUSR1);
                        self::printSuccess('Swoole Http Server Reloaded');
                    } else {
                        self::printError('PidFile Not Found');
                    }
                }
                break;
            default:
                self::printError("use {$argv[0]} [http:start, http:stop, http:reload, ws:start]");
        }
    }
}