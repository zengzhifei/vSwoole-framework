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
     * 子进程实例
     * @var array
     */
    protected $process_instance = [];

    /**
     * 默认配置参数
     * @var array
     */
    protected $process_options = [
        'is_signal' => true,
        'is_daemon' => true,
    ];


    /**
     * 设置进程配置参数
     * Process constructor.
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->process_options = !empty($options) ? array_merge($this->process_options, $options) : $this->process_options;

        if ($this->process_options['is_daemon'] === true) {
            $this->daemonProcess();
        }

        return $this;
    }

    /**
     * 工厂模式创建process子进程
     * @param callable $callback
     * @return int
     */
    private function createProcess(callable $callback)
    {
        $process = new \swoole_process($callback, true, true);
        $pid = $process->start();
        if (false !== $pid) {
            $this->process_instance[$pid] = $process;
        }
        return $pid;
    }

    /**
     * 监听子进程状态，子进程退出后，释放子进程
     */
    private function releaseProcess()
    {
        \swoole_process::signal(SIGCHLD, function ($sig) {
            while ($ret = \swoole_process::wait(false)) {
                var_dump($ret);
                unset($this->process_instance[$ret['pid']]);
            }
        });
    }

    /**
     * 设置当前进程为守护进程
     */
    private function daemonProcess()
    {
        \swoole_process::daemon();
    }

    /**
     * 添加进程回调函数，并创建子进程
     * @param $callback
     * @param array $arguments
     * @return bool|int
     */
    public function add($callback, array $arguments = [])
    {
        if (is_null($callback)) {
            return false;
        }

        if ((is_string($callback) && function_exists($callback)) || (is_object($callback) && is_callable($callback))) {
            $pid = $this->createProcess(function (\swoole_process $process) use ($callback, $arguments) {
                array_unshift($arguments, $process);
                $callback(...$arguments);
            });
        } else if (is_array($callback) && count($callback) == 2) {
            if (is_object($callback[0]) && is_callable([$callback[0], $callback[1]])) {
                $pid = $this->createProcess(function (\swoole_process $process) use ($callback, $arguments) {
                    $object = $callback[0];
                    $method = $callback[1];
                    array_unshift($arguments, $process);
                    $object->$method(...$arguments);
                });
            } else if (is_string($callback[0]) && is_callable([$callback[0], $callback[1]])) {
                $pid = $this->createProcess(function (\swoole_process $process) use ($callback, $arguments) {
                    $object = $callback[0];
                    $method = $callback[1];
                    array_unshift($arguments, $process);
                    $object::$method(...$arguments);
                });
            }
        }

        if ($this->process_options['is_signal'] === true && isset($pid) && $pid) {
            $this->releaseProcess();
        }

        return isset($pid) ? $pid : false;
    }

    /**
     * 获取已创建子进程
     * @param int $pid
     * @return mixed|null
     */
    public function getProcess(int $pid = 0)
    {
        return !empty($this->process_instance) && isset($this->process_instance[$pid]) ? $this->process_instance[$pid] : null;
    }

    /**
     * 获取已创建的子进程列表
     * @return array
     */
    public function getProcessList()
    {
        return $this->process_instance;
    }
}