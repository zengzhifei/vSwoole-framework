<?php
/**
 * swoole 定时器
 *
 * User: zengzhifei
 * Date: 2018/3/9
 * Time: 16:35
 */

namespace app\server\controller;

use app\serverLogic\controller\Timer;

class TimerServer extends SwooleServer
{
    //服务类型
    protected $serverType = 'timer';
    //进程模式
    protected $mode = SWOOLE_PROCESS;
    //连接类型
    protected $sockType = SWOOLE_SOCK_TCP;
    //监听IP
    protected $host = '0.0.0.0';
    //监听端口
    protected $port = 9502;
    //服务配置
    protected $option = [
        //日志
        'log_file' => LOG_PATH . '/swoole_timer.log',
    ];

    //构建服务器
    public function __construct()
    {
        parent::__construct();
    }

    //主进程回调函数，用来初始化服务器
    public function onStart(\swoole_server $server)
    {
        if (function_exists('cli_set_process_title')) {
			@cli_set_process_title(parent::PROCESS_NAME['Timer_master']);
		} else {
			@swoole_set_process_name(parent::PROCESS_NAME['Timer_master']);
		}
    }

    //接收消息回调函数，用来控制服务端
    public function onReceive(\swoole_server $server, $fd, $reactor_id, $data)
    {
        if (is_string($data)) {
            $data = json_decode($data, true);
            if (is_array($data) && isset($data['cmd'])) {
                switch (strtoupper($data['cmd'])) {
                    case parent::STOP_WORKER:
                        $server->stop();
                        break;
                    case parent::RELOAD_WORKER:
                        $server->reload();
                        break;
                    case parent::SHUTDOWN_SERVER:
                        $server->shutdown();
                        break;
                }
            }
        }
    }

    //工作进程回调函数,用来处理业务逻辑
    public function onWorkerStart(\swoole_server $server, $work_id)
    {
        if (!$server->taskworker) {
            $timer = new Timer($server);
        }
    }
	
	//异步任务处理
    public function onTask(\swoole_server $server, $task_id, $src_worker_id, $data)
    {
        //todo 异步任务逻辑处理
    }

    //异步任务处理结果
    public function onFinish(\swoole_server $server, $task_id, $data)
    {
        //todo 异步任务逻辑处理结果
    }
}