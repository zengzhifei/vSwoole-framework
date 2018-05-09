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
use vSwoole\library\common\exception\Exception;
use vSwoole\library\common\Process;
use vSwoole\library\common\cache\Redis;
use vSwoole\library\common\Timer;

class CrontabLogic
{
    /**
     * process进程管理对象
     * @var
     */
    protected $process;

    /**
     * 任务参数
     * @var array
     */
    protected $task_options = [
        //任务执行命令全路径
        'task_cmd'        => '',
        //任务地址(接口地址或实际地址)
        'task_url'        => '',
        //任务名称
        'task_name'       => '',
        //任务唯一key
        'task_key'        => '',
        //任务进程数量
        'task_number'     => 1,
        //任务执行频率
        'task_time'       => 100,
        //任务状态
        'task_status'     => 0,
        //任务进程ID组
        'task_process'    => [],
        //任务启动时间
        'task_start_time' => 0
    ];

    /**
     * 设置对象
     * @param \swoole_server $server
     */
    public function __construct(\swoole_server $server)
    {
        $GLOBALS['Crontab'] = $server;
    }

    /**
     * 添加任务
     * @param array $data
     * @throws \ReflectionException
     */
    public function add(array $data = [])
    {
        try {
            if (isset($data['task_url']) && is_string($data['task_url']) && !preg_match("/\s+rm\s+/", $data['task_url'])) {
                $task_key = md5($data['task_url']);
                $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
                $task = $redis->hGet(Config::loadConfig('redis')->get('redis_key.Crontab.Task_List'), $task_key);
                if ($task) {
                    $task = json_decode($task, true);
                    $task_options['task_cmd'] = isset($data['task_cmd']) && is_string($data['task_cmd']) ? $data['task_cmd'] : $task['task_cmd'];
                    $task_options['task_url'] = isset($data['task_url']) && is_string($data['task_url']) ? $data['task_url'] : $task['task_url'];
                    $task_options['task_name'] = isset($data['task_name']) && preg_match("/^\w+&/", $data['task_name']) ? $data['task_name'] : $task['task_name'];
                    $task_options['task_number'] = isset($data['task_number']) && $data['task_number'] > 0 ? $data['task_number'] : $task['task_number'];
                    $task_options['task_time'] = isset($data['task_time']) && $data['task_time'] > 0 ? $data['task_time'] : $task['task_time'];
                    $task_options['task_process'] = $task['task_process'];
                    $task_options['task_status'] = $task['task_status'];
                    $task_options['task_start_time'] = $task['task_start_time'];
                    $task_options['task_key'] = $task_key;
                } else {
                    $task_options['task_cmd'] = isset($data['task_cmd']) && is_string($data['task_cmd']) ? $data['task_cmd'] : $this->task_options['task_cmd'];
                    $task_options['task_url'] = isset($data['task_url']) && is_string($data['task_url']) ? $data['task_url'] : $this->task_options['task_url'];
                    $task_options['task_name'] = isset($data['task_name']) && is_string($data['task_name']) && preg_match("/^\w+&/", $data['task_name']) ? $data['task_name'] : $task_key;
                    $task_options['task_number'] = isset($data['task_number']) && $data['task_number'] > 0 ? $data['task_number'] : $this->task_options['task_number'];
                    $task_options['task_time'] = isset($data['task_time']) && $data['task_time'] > 0 ? $data['task_time'] : $this->task_options['task_time'];
                    $task_options['task_process'] = $this->task_options['task_process'];
                    $task_options['task_status'] = $this->task_options['task_status'];
                    $task_options['task_start_time'] = $this->task_options['task_start_time'];
                    $task_options['task_key'] = $task_key;
                }
                $redis->hSet(Config::loadConfig('redis')->get('redis_key.Crontab.Task_List'), $task_key, json_encode($task_options));
            }
        } catch (\Exception $e) {
            Exception::reportException($e);
        }
    }

    /**
     * 启动任务
     * @param array $data
     * @throws \ReflectionException
     */
    public function start(array $data = [])
    {
        try {
            if (isset($data['task_key']) && is_string($data['task_key'])) {
                $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
                $task = $redis->hGet(Config::loadConfig('redis')->get('redis_key.Crontab.Task_List'), $data['task_key']);
                if ($task) {
                    $task = json_decode($task, true);
                    if (0 == $task['task_status']) {
                        if ($task['task_cmd'] == '') {
                            for ($i = 0; $i < $task['task_number']; $i++) {
                                $pid = Process::getInstance()->add(function ($process) use ($i, $task, $redis) {
                                    $process->name($task['task_name'] . '_' . $i);
                                    $curl = new Curl();
                                    Timer::tick($task['task_time'], function ($timer_id) use ($curl, $task, $redis) {
                                        $res = $curl->get($task['task_url']);
                                        var_dump($res);
                                    });
                                });
                                if (false !== $pid) {
                                    $task['task_process'][] = $pid;
                                }
                            }
                            $task['task_status'] = 1;
                            $task['task_start_time'] = time();
                            $redis->hSet(Config::loadConfig('redis')->get('redis_key.Crontab.Task_List'), $data['task_key'], json_encode($task));
                        } else {
                            for ($i = 0; $i < $task['task_number']; $i++) {
                                $pid = Process::getInstance()->add(function ($process) use ($i, $task, $redis) {
                                    $process->name($task['task_name'] . '_' . $i);
                                    $process->exec($task['task_cmd'], explode(' ', $task['task_url']));
                                });
                                if (false !== $pid) {
                                    $task['task_process'][] = $pid;
                                }
                            }
                            $task['task_status'] = 1;
                            $task['task_start_time'] = time();
                            $redis->hSet(Config::loadConfig('redis')->get('redis_key.Crontab.Task_List'), $data['task_key'], json_encode($task));
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
     * @throws \ReflectionException
     */
    public function stop(array $data = [])
    {
        try {
            if (isset($data['task_key']) && is_string($data['task_key'])) {
                $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
                $task = $redis->hGet(Config::loadConfig('redis')->get('redis_key.Crontab.Task_List'), $data['task_key']);
                if ($task) {
                    $task = json_decode($task, true);
                    if (1 == $task['task_status']) {
                        Process::getInstance()->killProcess($task['task_process']);
                        $task['task_status'] = 0;
                        $task['task_process'] = [];
                        $redis->hSet(Config::loadConfig('redis')->get('redis_key.Crontab.Task_List'), $data['task_key'], json_encode($task));
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
     * @throws \ReflectionException
     */
    public function delete(array $data = [])
    {
        try {
            if (isset($data['task_key']) && is_string($data['task_key'])) {
                $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
                $task = $redis->hGet(Config::loadConfig('redis')->get('redis_key.Crontab.Task_List'), $data['task_key']);
                if ($task) {
                    $task = json_decode($task, true);
                    Process::getInstance()->killProcess($task['task_process']);
                    $redis->hDel(Config::loadConfig('redis')->get('redis_key.Crontab.Task_List'), $data['task_key']);
                }
            }
        } catch (\Exception $e) {
            Exception::reportException($e);
        }
    }
}