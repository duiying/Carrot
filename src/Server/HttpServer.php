<?php

namespace Carrot\Server;

use App\Constant\TaskConstant;
use Carrot\Application;
use Carrot\HttpUtil;
use Carrot\Route;
use DuiYing\Logger;

class HttpServer
{
    public static $_server;

    private $_config;

    private $_route;

    public static $pidFile = BASE_PATH . '/master.pid';

    public function __construct()
    {
        $config = config('servers');
        $httpConfig = $config['http'];
        $this->_config = $httpConfig;

        self::$_server = new \Swoole\Http\Server($httpConfig['ip'], $httpConfig['port'], $config['mode'], $httpConfig['sock_type']);

        self::$_server->on('workerStart', [$this, 'onWorkerStart']);
        self::$_server->on('request', [$this, 'onRequest']);
        if ($config['mode'] == SWOOLE_BASE) {
            self::$_server->on('managerStart', [$this, 'onManagerStart']);
        } else {
            self::$_server->on('start', [$this, 'onStart']);
        }

        // TASK 异步任务相关配置 begin
        self::$_server->on('task', [$this, 'onTask']);
        self::$_server->on('finish', [$this, 'onFinish']);
        $httpConfig['settings']['task_enable_coroutine'] = true;
        // TASK 异步任务相关配置 end

        self::$_server->set($httpConfig['settings']);

        self::$_server->start();
    }

    public function onStart(\Swoole\Server $server)
    {
        swoole_set_process_name("swoole-master");
        // 记录进程 id，通过脚本实现自动重启
        $pid = $server->master_pid;
        file_put_contents(self::$pidFile, $pid);
        Application::printSuccess("Swoole Http Server running：http://{$this->_config['ip']}:{$this->_config['port']}");
    }

    public function onManagerStart(\Swoole\Server $server)
    {
        swoole_set_process_name("swoole-manager");
        Application::printSuccess("Swoole Http Server running：http://{$this->_config['ip']}:{$this->_config['port']}");
    }

    public function onWorkerStart(\Swoole\Server $server, int $workerId)
    {
        $this->_route = Route::getInstance();
        if ($workerId >= $this->_config['settings']['worker_num']) {
            swoole_set_process_name("swoole-task");
        } else {
            swoole_set_process_name("swoole-worker");
        }
    }

    /**
     * 调用 $serv->task() 后，程序立即返回，继续向下执行代码。onTask 回调函数 Task 进程池内被异步执行。执行完成后调用 $serv->finish() 返回结果。
     *
     * $task->worker_id 投递任务的 worker 进程 id
     * $task->id 任务编号
     * $task->data 任务数据
     *
     * @param \Swoole\Server $server
     * @param \Swoole\Server\Task $task
     */
    public function onTask(\Swoole\Server $server, \Swoole\Server\Task $task)
    {
        $data = $task->data;
        $taskName = $data['task_name'];
        $taskData = $data['task_data'];
        $classAndFunctionInfo = TaskConstant::TASK_MAP[$taskName] ?? '';
        if (empty($classAndFunctionInfo)) {
            return false;
        }
        $class = explode('@', $classAndFunctionInfo)[0];
        $function = explode('@', $classAndFunctionInfo)[1];
        $class::getInstance()->$function($taskData);

        return true;
    }

    /**
     * 此回调函数在 Worker 进程被调用，当 Worker 进程投递的任务在 Task 进程中完成时，Task 进程会通过 Swoole\Server->finish() 方法将任务处理的结果发送给 Worker 进程。
     *
     * @param \Swoole\Server $server
     * @param int $task_id
     * @param mixed $data
     */
    public function onFinish(\Swoole\Server $server, int $task_id, mixed $data)
    {

    }

    public function onRequest(\Swoole\Http\Request $request, \Swoole\Http\Response $response)
    {
        $logger = Logger::getInstance('/home/work/logs/api');
        if ($request->server['request_uri'] !== '/favicon.ico') {
            $logger->info('客户端开始请求', [
                'request_method'    => $request->server['request_method'],
                'request_uri'       => $request->server['request_uri'],
                'query_string'      => $request->server['query_string'] ?? '',
                'remote_addr'       => $request->server['remote_addr']
            ]);
        }
        try {
            $this->_route->dispatch($request, $response);
        } catch (\Exception $exception) {
            $logger->error($exception->getMessage(), ['code' => $exception->getCode()]);
            return $response->end(HttpUtil::error());
        }
    }

    /**
     * 投递任务
     *
     * @param $taskName
     * @param array $taskData
     */
    public static function deliveryTask($taskName, $taskData = [])
    {
        self::$_server->task([
            'task_name' => $taskName,
            'task_data' => $taskData
        ]);
    }
}