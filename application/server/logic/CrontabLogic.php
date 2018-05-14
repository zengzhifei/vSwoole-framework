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
use vSwoole\library\common\cache\Redis;
use vSwoole\library\common\Process;
use vSwoole\library\common\Timer;
use vSwoole\library\common\Utils;

class CrontabLogic
{
    //获取任务进程ID
    const GET_TASK = 0;
    //执行任务进程ID
    const EXECUTE_TASK = 1;

    /**
     * 任务参数
     * @var array
     */
    protected $task_options = [
        //任务唯一ID
        'task_id'          => 0,
        //任务执行命令全路径
        'task_cmd'         => '',
        //任务地址(接口地址或实际地址)
        'task_url'         => '',
        //任务名称
        'task_name'        => '',
        //任务执行频率
        'task_time'        => '* * * * * *',
        //任务并发数
        'task_concurrent'  => 1,
        //任务状态
        'task_status'      => 0,
        //任务创建时间
        'task_create_time' => 0
    ];

    /**
     * 设置对象
     * @param \swoole_server $server
     * @throws \ReflectionException
     */
    public function __construct(\swoole_server $server)
    {
        $GLOBALS['Crontab'] = $server;

        //启动任务读取解析并执行执行进程
        self::run();
    }

    /**
     * 添加任务到任务列表
     * @param array $data
     * @throws \ReflectionException
     */
    public function add(array $data = [])
    {
        try {
            if (!isset($data['task_cmd']) || !self::checkTaskCmd($data['task_cmd'])) {
                throw new \InvalidArgumentException("Arguments task cmd invalid: {$data['task_cmd']}");
            }
            if (!isset($data['task_url']) || !self::checkTaskUrl($data['task_url'])) {
                throw new \InvalidArgumentException("Arguments task url invalid: {$data['task_url']}");
            }
            if (!isset($data['task_time']) || !self::checkTaskTime($data['task_time'])) {
                throw new \InvalidArgumentException("Arguments task time invalid: {$data['task_time']}");
            }
            $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
            if (isset($data['task_id']) && ($task = $redis->hGet(Config::loadConfig('redis')->get('redis_key.Crontab.Task_List'), $data['task_id']))) {
                $task = json_decode($task, true);
                $redis->hSet(Config::loadConfig('redis')->get('redis_key.Crontab.Task_List'), $data['task_id'], json_encode(array_merge($task, $data)));
            } else {
                $task['task_cmd'] = $data['task_cmd'];
                $task['task_url'] = $data['task_url'];
                $task['task_name'] = $data['task_name'] ?? $this->task_options['task_name'];
                $task['task_time'] = $data['task_time'];
                $task['task_concurrent'] = isset($data['task_concurrent']) && is_int($data['task_concurrent']) && $data['task_concurrent'] > 0 ? $data['task_concurrent'] : $this->task_options['task_concurrent'];
                $task['task_status'] = $this->task_options['task_status'];
                $task['task_create_time'] = time();
                $task_key = Config::loadConfig('crontab')->get('other.task_key');
                $redis->hSet(Config::loadConfig('redis')->get('redis_key.Crontab.Task_List'), md5($task_key . time()), json_encode($task));
            }
        } catch (\Exception $e) {
            Exception::reportException($e);
        }
    }

    /**
     * 开启任务
     * @param array $data
     * @throws \ReflectionException
     */
    public function start(array $data = [])
    {
        try {
            $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
            if (isset($data['task_id']) && ($task = $redis->hGet(Config::loadConfig('redis')->get('redis_key.Crontab.Task_List'), $data['task_id']))) {
                $task = json_decode($task, true);
                $task['task_status'] = 1;
                $redis->hSet(Config::loadConfig('redis')->get('redis_key.Crontab.Task_List'), $data['task_id'], json_encode($task));
                $redis->hSet(Config::loadConfig('redis')->get('redis_key.Crontab.Task_Pool'), $data['task_id'], json_encode($task));
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
            $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
            if (isset($data['task_id']) && ($task = $redis->hGet(Config::loadConfig('redis')->get('redis_key.Crontab.Task_List'), $data['task_id']))) {
                $task = json_decode($task, true);
                $task['task_status'] = 0;
                $redis->hSet(Config::loadConfig('redis')->get('redis_key.Crontab.Task_List'), $data['task_id'], json_encode($task));
                $redis->hDel(Config::loadConfig('redis')->get('redis_key.Crontab.Task_Pool'), $data['task_id']);
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
            $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
            $redis->hDel(Config::loadConfig('redis')->get('redis_key.Crontab.Task_List'), $data['task_id']);
            $redis->hDel(Config::loadConfig('redis')->get('redis_key.Crontab.Task_Pool'), $data['task_id']);
        } catch (\Exception $e) {
            Exception::reportException($e);
        }
    }

    /**
     * 定时读取任务
     * @throws \ReflectionException
     */
    public static function run()
    {
        try {
            if ($GLOBALS['Crontab']->taskworker) {
                if ($GLOBALS['Crontab']->worker_id == ($GLOBALS['Crontab']->setting['worker_num'] + self::GET_TASK)) {
                    Utils::setProcessName(VSWOOLE_CRONTAB_SERVER . ' crontab get');
                    $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
                    $task_list_key = Config::loadConfig('redis')->get('redis_key.Crontab.Task_Pool');
                    Timer::after((60 - date('s')) * 1000, function () use ($redis, $task_list_key) {
                        self::getTask($redis, $task_list_key);
                        Timer::tick(60000, function ($timer_id) use ($redis, $task_list_key) {
                            self::getTask($redis, $task_list_key);
                        });
                    });
                }
            }
        } catch (\Exception $e) {
            Exception::reportException($e);
        }
    }

    /**
     * 读取已启动任务列表
     * @param $redis
     * @param $task_list_key
     */
    private static function getTask($redis, $task_list_key)
    {
        if ($task_list = $redis->hGetAll($task_list_key)) {
            foreach ($task_list as $task_id => $task) {
                $task = json_decode($task, true);
                if ($task_execute_time = self::parse($task['task_time'])) {
                    $task['task_execute_time'] = $task_execute_time;
                    $GLOBALS['Crontab']->sendMessage($task, $GLOBALS['Crontab']->setting['worker_num'] + self::EXECUTE_TASK);
                }
            }
        }
    }

    /**
     * 执行任务
     * @param $task
     * @throws \ReflectionException
     */
    public function execute($task)
    {
        try {
            Utils::setProcessName(VSWOOLE_CRONTAB_SERVER . ' crontab execute');
            if (preg_match("/(\/curl)[\s]*$/i", trim($task['task_cmd']))) {
                var_dump('curl');
                for ($i = 1; $i <= $task['task_concurrent']; $i++) {
                    Process::getInstance(['redirect_stdin_stdout' => false])->add(function ($process) use ($task) {
                        $curl = new Curl();
                        foreach ($task['task_execute_time'] as $task_time) {
                            Timer::after($task_time * 1000, function () use ($curl, $task) {
                                $res = $curl->get(trim($task['task_url']));
                            });
                        }
                    });
                }
            } else {
                var_dump('cmd');
                for ($i = 1; $i <= $task['task_concurrent']; $i++) {
                    foreach ($task['task_execute_time'] as $task_time) {
                        Process::getInstance(['redirect_stdin_stdout' => false])->add(function ($process) use ($task, $task_time) {
                            Timer::after($task_time * 1000, function () use ($process, $task) {
                                $process->exec(trim($task['task_cmd']), preg_split("/[\s]+/i", trim($task['task_url'])));
                                $process->exit(0);
                            });
                        });
                    }
                }
            }
        } catch (\Exception $e) {
            Exception::reportException($e);
        }
    }

    /**
     * 解析可执行任务
     * @param string $task_time
     * @return array|null
     */
    private static function parse(string $task_time)
    {
        if ($task_time) {
            $task_time_arr = preg_split("/[\s]+/i", trim($task_time));
            if (count($task_time_arr) == 6) {
                $task_cron['seconds'] = self::parseCron($task_time_arr[0], 1, 59);
                $task_cron['minutes'] = self::parseCron($task_time_arr[1], 0, 59);
                $task_cron['hours'] = self::parseCron($task_time_arr[2], 0, 23);
                $task_cron['days'] = self::parseCron($task_time_arr[3], 1, 31);
                $task_cron['month'] = self::parseCron($task_time_arr[4], 1, 12);
                $task_cron['weeks'] = self::parseCron($task_time_arr[5], 0, 6);
            } else if (count($task_time_arr) == 5) {
                $task_cron['seconds'] = [1];
                $task_cron['minutes'] = self::parseCron($task_time_arr[1], 0, 59);
                $task_cron['hours'] = self::parseCron($task_time_arr[2], 0, 23);
                $task_cron['days'] = self::parseCron($task_time_arr[3], 1, 31);
                $task_cron['month'] = self::parseCron($task_time_arr[4], 1, 12);
                $task_cron['weeks'] = self::parseCron($task_time_arr[5], 0, 6);
            }


            if (in_array(date('w'), $task_cron['weeks']) && in_array(date('n'), $task_cron['month']) && in_array(date('j'), $task_cron['days']) &&
                in_array(date('G'), $task_cron['hours']) && in_array(date('i'), $task_cron['minutes'])) {
                return $task_cron['seconds'];
            }
        }
        return null;
    }

    /**
     * 解析crontab任务时间
     * @param $cron_time
     * @param $min
     * @param $max
     * @return array
     */
    private static function parseCron($cron_time, $min, $max)
    {
        $result = array();
        $cron_arr = explode(',', $cron_time);
        foreach ($cron_arr as $cron) {
            $cron_a = explode('/', $cron);
            $cron_a_step = $cron_a[1] ?? 1;
            $cron_b = explode('-', $cron_a[0]);
            $_min = count($cron_b) == 2 ? $cron_b[0] : ($cron_a[0] == '*' ? $min : $cron_a[0]);
            $_max = count($cron_b) == 2 ? $cron_b[1] : ($cron_a[0] == '*' ? $max : $cron_a[0]);
            for ($i = $_min; $i <= $_max; $i += $cron_a_step) {
                if (intval($i) < $min) {
                    $result[] = $min;
                } elseif (intval($i) > $max) {
                    $result[] = $max;
                } else {
                    $result[] = intval($i);
                }
            }
        }
        ksort($result);
        return $result;
    }

    /**
     * 检验任务命令
     * @param string $task_cmd
     * @return bool
     */
    private static function checkTaskCmd(string $task_cmd)
    {
        if (preg_match("/^\/[\w|\/]+[^\/]$/", trim($task_cmd))) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 校验任务地址
     * @param string $task_url
     * @return bool
     */
    private static function checkTaskUrl(string $task_url)
    {
        if (preg_match("/\s+(rm){1}\s+\w+/", trim($task_url))) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 校验任务时间
     * @param string $task_time
     * @return bool
     */
    private static function checkTaskTime(string $task_time)
    {
        if (!preg_match('/^((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)$/i', trim($task_time))) {
            if (!preg_match('/^((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)$/i', trim($task_time))) {
                return false;
            }
        }
        return true;
    }
}