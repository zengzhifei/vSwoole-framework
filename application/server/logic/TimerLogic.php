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
        $GLOBALS['timer'] = $server;

        $this->process = new Process([
            'redirect_stdin_stdout' => false,
            'create_pipe'           => true,
        ]);
    }

    /**
     * 添加任务
     * @param array $data
     */
    public function add(array $data = [])
    {
        try {
            if (isset($data['task_url'])) {
                $task_key = md5($data['task_url']);
                $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
                $task = $redis->hGet(Config::loadConfig('redis')->get('redis_key.Timer.Task_List'), $task_key);
                if ($task) {
                    $data = array_merge(json_decode($task, true), $data);
                } else {
                    $data['is_running'] = false;
                    $data['worker_id'] = false;
                    $data['process_id'] = [];
                    $data['task_key'] = $task_key;
                }
                $redis->hSet(Config::loadConfig('redis')->get('redis_key.Timer.Task_List'), $task_key, json_encode($data));
            }
        } catch (\Exception $e) {
            Exception::reportException($e);
        }
    }

    /**
     * 运行任务
     * @param array $data
     */
    public function start(array $data = [])
    {
        try {
            if (isset($data['task_key'])) {
                $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
                $task = $redis->hGet(Config::loadConfig('redis')->get('redis_key.Timer.Task_List'), $data['task_key']);
                if ($task) {
                    $task = json_decode($task, true);
                    if (!$task['is_running']) {
                        $task_num = isset($task['task_num']) && $task['task_num'] > 0 ? $task['task_num'] : 1;
                        for ($i = 0; $i < $task_num; $i++) {
                            $pid = $this->process->add(function ($process) use ($i, $task, $redis) {
                                $task_time = isset($task['task_time']) && $task['task_time'] > 0 ? $task['task_time'] : 100;
                                $task_name = isset($task['task_name']) && $task['task_name'] ? $task['task_name'] : $task['task_key'];
                                $process->name($task_name . '_' . $i);
                                $curl = new Curl();
                                swoole_timer_tick($task_time, function ($timer_id) use ($curl, $task, $redis) {
                                    $res = $curl->get($task['task_url']);
                                    var_dump($res);
                                });
                            });
                            if (false !== $pid) {
                                $task['is_running'] = true;
                                $task['worker_id'] = $GLOBALS['timer']->worker_id;
                                $task['process_id'][] = $pid;
                                $redis->hSet(Config::loadConfig('redis')->get('redis_key.Timer.Task_List'), $data['task_key'], json_encode($task));
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Exception::reportException($e);
        }
    }

    /**
     * 暂停任务
     * @param array $data
     */
    public function stop(array $data = [])
    {
        try {
            if (isset($data['task_key'])) {
                $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
                $task = $redis->hGet(Config::loadConfig('redis')->get('redis_key.Timer.Task_List'), $data['task_key']);
                if ($task) {
                    $task = json_decode($task, true);
                    if ($task['is_running']) {
                        $this->process->killProcess($task['process_id']);
                        $task['is_running'] = false;
                        $task['worker_id'] = false;
                        $task['process_id'] = [];
                        $redis->hSet(Config::loadConfig('redis')->get('redis_key.Timer.Task_List'), $data['task_key'], json_encode($task));
                    }
                }
            }
        } catch (\Exception $e) {
            Exception::reportException($e);
        }
    }

    /**
     * 删除任务
     * @param array $data
     */
    public function delete(array $data = [])
    {
        try {
            if (isset($data['task_key'])) {
                $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
                $task = $redis->hGet(Config::loadConfig('redis')->get('redis_key.Timer.Task_List'), $data['task_key']);
                if ($task) {
                    $task = json_decode($task, true);
                    if ($task['is_running']) {
                        $this->process->killProcess($task['process_id']);
                        $redis->hDel(Config::loadConfig('redis')->get('redis_key.Timer.Task_List'), $data['task_key']);
                    }
                }
            }
        } catch (\Exception $e) {
            Exception::reportException($e);
        }
    }
}