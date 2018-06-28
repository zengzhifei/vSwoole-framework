<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+

namespace vSwoole\library\common;


class Process
{
    /**
     * 进程管理实例
     * @var null
     */
    protected static $_instance = null;

    /**
     * 子进程实例
     * @var array
     */
    protected static $process_instance = [];

    /**
     * 默认配置参数
     * @var array
     */
    protected static $default_options = [
        'redirect_stdin_stdout'     => true,
        'create_pipe'               => true,
        'enable_memory_security'    => true,
        'memory_security_threshold' => 204800
    ];

    /**
     * 当前子进程配置
     * @var array
     */
    protected static $process_options = [];


    /**
     * 设置进程配置参数，获取进程管理实例
     * @param array $options
     * @return null|static
     */
    public static function getInstance(array $options = [])
    {
        self::$process_options = array_merge(self::$default_options, $options);
        self::$_instance = is_null(self::$_instance) ? new static() : self::$_instance;

        return self::$_instance;
    }

    /**
     * 添加进程回调函数，并创建子进程
     * @param $callback
     * @param array $arguments
     * @return bool|int
     */
    public function add($callback, array $arguments = [])
    {
        if (self::$process_options['enable_memory_security'] && !$this->checkMemorySecurity()) {
            return false;
        }

        $pid = $this->createProcess(function (\swoole_process $process) use ($callback, $arguments) {
            array_unshift($arguments, $process);
            call_user_func_array($callback, $arguments);
        });

        return $pid;
    }

    /**
     * 工厂模式创建process子进程
     * @param callable $callback
     * @return int
     */
    private function createProcess(callable $callback)
    {
        $process = new \swoole_process($callback, self::$process_options['redirect_stdin_stdout'], self::$process_options['create_pipe']);
        $pid = $process->start();
        if (false !== $pid) {
            self::$process_instance[$pid] = $process;
        }
        return $pid;
    }

    /**
     * 检测内存大小是否大于阈值
     * @return bool
     */
    private function checkMemorySecurity()
    {
        exec("cat /proc/meminfo | grep MemFree | awk '{print $2}'", $output, $status);
        $check = $status === 0 && $output[0] >= self::$process_options['memory_security_threshold'] ? true : false;
        unset($output);
        return $check;
    }

    /**
     *  监听子进程状态，子进程退出后，释放子进程
     * @param bool $is_blocking
     */
    public static function signalProcess(bool $is_blocking = true)
    {
        if ($is_blocking) {
            while ($ret = \swoole_process::wait(true)) {
                if ($ret) {
                    unset(self::$process_instance[$ret['pid']]);
                }
            }
        } else {
            \swoole_process::signal(SIGCHLD, function ($sign) {
                while ($ret = \swoole_process::wait(false)) {
                    if ($ret) {
                        unset(self::$process_instance[$ret['pid']]);
                    }
                }
            });
        }
    }

    /**
     * 获取已创建子进程
     * @param int $pid
     * @return mixed|null
     */
    public function getProcess(int $pid = -1)
    {
        return !empty(self::$process_instance) && isset(self::$process_instance[$pid]) ? self::$process_instance[$pid] : null;
    }

    /**
     * 获取已创建的子进程列表
     * @return array
     */
    public function getProcessList()
    {
        return self::$process_instance;
    }

    /**
     * 终止指定子进程
     * @param int $pid
     */
    public static function killProcess($pid = -1)
    {
        if (is_int($pid) && $pid > -1) {
            \swoole_process::kill($pid);
        } else if (is_array($pid)) {
            for ($i = 0; $i < count($pid); $i++) {
                if (is_int($pid[$i]) && $pid[$i] > -1) {
                    \swoole_process::kill($pid[$i]);
                }
            }
        }
    }

}