<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+

namespace vSwoole\application\server\logic;


use vSwoole\library\common\Config;
use vSwoole\library\common\Curl;
use vSwoole\library\common\Exception;
use vSwoole\library\common\Process;
use vSwoole\library\common\Redis;

class TimerLogic
{
    /**
     * 服务对象
     * @var
     */
    protected $server;

    /**
     * curl连接对象
     * @var
     */
    protected $curl;

    /**
     * process进程管理对象
     * @var
     */
    protected $process;

    /**
     * 设置对象
     * @param \swoole_server $server
     */
    public function __construct(\swoole_server $server)
    {
        $this->server = $server;
        $this->curl = new Curl();
        $this->process = new Process();
    }

    public function add(array $data = [])
    {
        try {
            if (isset($data['task_url'])) {

                $task_key = md5($data['task_url']);
                $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
                $task = $redis->hGet(Config::loadConfig('redis')->get('redis_key.Timer.Task_List'), $task_key);
                if ($task) {

                } else {
                    $redis->hSet(Config::loadConfig('redis')->get('redis_key.Timer.Task_List'), $task_key, json_encode($data));
                    $this->process->add(function ($process) use ($data) {
                        swoole_timer_tick(3000, function ($timer_id) use ($data) {
                            $res = $this->curl->get($data['task_url']);
                            var_dump($res);
                        });
                    });
                }
            }
        } catch (\Exception $e) {
            Exception::reportException($e);
        }
    }

}