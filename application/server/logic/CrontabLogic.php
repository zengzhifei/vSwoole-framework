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
    protected static $get_task_worker = 0;
    //执行任务进程ID
    protected static $execute_task_worker = 1;

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
        //任务执行频率
        'task_time'        => '* * * * * *',
        //任务并发数
        'task_process_num' => 1,
        //任务分组
        'task_group'       => '',
        //任务名称
        'task_name'        => '',
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
        $GLOBALS['server'] = $server;

        //启动任务读取解析并执行执行进程
        $this->run();
    }

    /**
     * 异步处理任务
     * @param int $fd
     * @param string $data
     * @throws \Exception
     * @throws \ReflectionException
     */
    public function receive(int $fd, string $data)
    {
        if ($data && ($data = json_decode($data, true)) && is_array($data) && isset($data['cmd'])) {
            switch (strtolower($data['cmd'])) {
                case 'reload':
                    $GLOBALS['server']->reload();
                    break;
                case 'shutdown':
                    $GLOBALS['server']->shutdown();
                    break;
                case 'add':
                    $this->add($fd, $data['data']);
                    break;
                case 'start':
                    $this->start($data['data']);
                    break;
                case 'stop':
                    $this->stop($data['data']);
                    break;
                case 'delete':
                    $this->delete($data['data']);
                    break;
            }
            $GLOBALS['server']->send($fd, 'pong');
        }

    }

    /**
     * 添加任务到任务列表
     * @param array $data
     * @throws \Exception
     */
    protected function add(int $fd, array $data = [])
    {
        try {
            if (!isset($data['task_cmd']) || !$data['task_cmd'] || !self::checkTaskCmd($data['task_cmd'])) {
                throw new \InvalidArgumentException("Arguments task cmd invalid: {$data['task_cmd']}");
            }
            if (!isset($data['task_url']) || !$data['task_url'] || !self::checkTaskUrl($data['task_url'])) {
                throw new \InvalidArgumentException("Arguments task url invalid: {$data['task_url']}");
            }
            if (!isset($data['task_time']) || !$data['task_time'] || !self::checkTaskTime($data['task_time'])) {
                throw new \InvalidArgumentException("Arguments task time invalid: {$data['task_time']}");
            }
            $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
            if (isset($data['task_id']) && $data['task_id'] && ($task = $redis->hGet(Config::loadConfig('redis')->get('redis_key.Crontab.Task_List'), $data['task_id']))) {
                $task = json_decode($task, true);
                $task = array_merge($task, $data);
                $redis->hSet(Config::loadConfig('redis')->get('redis_key.Crontab.Task_List'), $data['task_id'], json_encode($task));
                $this->taskToPool($task);
            } else {
                $task['task_cmd'] = $data['task_cmd'];
                $task['task_url'] = $data['task_url'];
                $task['task_time'] = $data['task_time'];
                $task['task_process_num'] = isset($data['task_process_num']) && $data['task_process_num'] > 0 ? $data['task_process_num'] : $this->task_options['task_process_num'];
                $task['task_group'] = $data['task_group'] ?? $this->task_options['task_group'];
                $task['task_name'] = $data['task_name'] ?? $this->task_options['task_name'];
                $task['task_status'] = $this->task_options['task_status'];
                $task['task_create_time'] = time();
                $task_key = Config::loadConfig('crontab')->get('other.task_key');
                $task['task_id'] = md5($task_key . time());
                $redis->hSet(Config::loadConfig('redis')->get('redis_key.Crontab.Task_List'), $task['task_id'], json_encode($task));
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
            if (isset($data['task_id']) && $data['task_id'] && ($task = $redis->hGet(Config::loadConfig('redis')->get('redis_key.Crontab.Task_List'), $data['task_id']))) {
                $task = json_decode($task, true);
                $task['task_status'] = 1;
                $redis->hSet(Config::loadConfig('redis')->get('redis_key.Crontab.Task_List'), $data['task_id'], json_encode($task));
                $this->taskToPool($task);
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
            if (isset($data['task_id']) && $data['task_id'] && ($task = $redis->hGet(Config::loadConfig('redis')->get('redis_key.Crontab.Task_List'), $data['task_id']))) {
                $task = json_decode($task, true);
                $task['task_status'] = 0;
                $redis->hSet(Config::loadConfig('redis')->get('redis_key.Crontab.Task_List'), $data['task_id'], json_encode($task));
                $this->taskToPool($task);
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
            if (isset($data['task_id']) && $data['task_id'] && ($task = $redis->hGet(Config::loadConfig('redis')->get('redis_key.Crontab.Task_List'), $data['task_id']))) {
                $task = json_decode($task, true);
                $task['task_status'] = 0;
                $redis->hDel(Config::loadConfig('redis')->get('redis_key.Crontab.Task_List'), $data['task_id']);
                $this->taskToPool($task);
            }
        } catch (\Exception $e) {
            Exception::reportException($e);
        }
    }

    /**
     * 定时读取任务
     * @throws \ReflectionException
     */
    protected function run()
    {
        try {
            if ($GLOBALS['server']->taskworker) {
                if ($GLOBALS['server']->worker_id == ($GLOBALS['server']->setting['worker_num'] + self::$get_task_worker)) {
                    Utils::setProcessName(VSWOOLE_CRONTAB_SERVER . ' run task');
                    $this->serverStartGetTaskToPool();
                    Timer::after((60 - date('s')) * 1000, function () {
                        $this->getTask();
                        Timer::tick(60000, function ($timer_id) {
                            $this->getTask();
                        });
                    });
                }
            }
        } catch (\Exception $e) {
            Exception::reportException($e);
        }
    }

    /**
     * 服务器启动读取可执行任务到任务池
     * @throws \ReflectionException
     */
    protected function serverStartGetTaskToPool()
    {
        try {
            $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
            if ($task_list = $redis->hGetAll(Config::loadConfig('redis')->get('redis_key.Crontab.Task_List'))) {
                foreach ($task_list as $task) {
                    $this->taskToPool(json_decode($task, true));
                }
            }
        } catch (\Exception $e) {
            Exception::reportException($e);
        }
    }

    /**
     * 计划任务写入或写出任务池
     * @param array $task
     */
    protected function taskToPool(array $task)
    {
        if ($task && isset($task['task_status'])) {
            if ($task['task_status'] == 1) {
                $GLOBALS['task_table']->set($task['task_id'], ['task_cmd' => $task['task_cmd'], 'task_url' => $task['task_url'], 'task_time' => $task['task_time'], 'task_process_num' => $task['task_process_num']]);
            } else {
                $GLOBALS['task_table']->del($task['task_id']);
            }
        }
    }

    /**
     * 读取已启动任务列表
     */
    protected function getTask()
    {
        if ($task_list = $GLOBALS['task_table']->getAll()) {
            $execute_task_worker = self::$execute_task_worker + $GLOBALS['server']->setting['worker_num'];
            foreach ($task_list as $task_id => $task) {
                if ($task_execute_time = self::parse($task['task_time'])) {
                    $task['task_execute_time'] = $task_execute_time;
                    $GLOBALS['server']->sendMessage($task, $execute_task_worker);
                }
            }
            $this->calculateExecuteTaskWorker();
        }
    }

    /**
     * 计算下一个轮询的执行进程号
     */
    protected function calculateExecuteTaskWorker()
    {
        $current_execute_worker = self::$execute_task_worker;
        if ($GLOBALS['server']->setting['task_worker_num'] > ($next_execute_worker = $current_execute_worker + 1)) {
            self::$execute_task_worker = $next_execute_worker;
        } else {
            self::$execute_task_worker = 1;
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
            Utils::setProcessName(VSWOOLE_CRONTAB_SERVER . ' execute task');
            if (preg_match("/(\/curl)[\s]*$/i", trim($task['task_cmd']))) {
                for ($i = 1; $i <= $task['task_process_num']; $i++) {
                    Process::getInstance()->add(function ($process) use ($task) {
                        $curl = new Curl();
                        foreach ($task['task_execute_time'] as $task_key => $task_time) {
                            Timer::after($task_time * 1000, function () use ($process, $curl, $task, $task_key) {
                                $res = $curl->get(trim($task['task_url']));
                                if (($task_key + 1) == count($task['task_execute_time'])) {
                                    $process->exit(0);
                                }
                            });
                        }
                    });
                }
                Process::signalProcess(true);
            } else {
                for ($i = 1; $i <= $task['task_process_num']; $i++) {
                    Process::getInstance()->add(function ($process) use ($task) {
                        foreach ($task['task_execute_time'] as $task_key => $task_time) {
                            Timer::after($task_time * 1000, function () use ($process, $task, $task_key) {
                                $process->exec(trim($task['task_cmd']), preg_split("/[\s]+/i", trim($task['task_url'])));
                                if (($task_key + 1) == count($task['task_execute_time'])) {
                                    $process->exit(0);
                                }
                            });
                        }
                    });
                }
                Process::signalProcess(true);
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