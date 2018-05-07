<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+

namespace vSwoole\library\common\reflection;


class ReflectionFunction
{
    /**
     * 反射函数实例
     * @var \ReflectionFunction
     */
    protected $function_instance;

    /**
     * 实例化反射函数
     * @param $function
     * @throws \ReflectionException
     */
    public function __construct($function)
    {
        $this->function_instance = new \ReflectionFunction($function);
    }

    /**
     * 获取反射函数文件名
     * @return string
     */
    public function getFileName()
    {
        return $this->function_instance->getFileName();
    }

    /**
     * 获取反射函数源码数组
     * @return array|bool
     */
    public function getSource()
    {
        return @file($this->function_instance->getFileName());
    }

    /**
     * 获取反射函数源码
     * @param int $start_line
     * @param int $end_line
     * @param bool $show_line
     * @return string
     */
    public function getSourceCode(int $start_line = 0, int $end_line = 0, bool $show_line = false)
    {
        $start_line = $start_line && $start_line >= $this->getStartLine() ? $start_line : $this->getStartLine();
        $end_line = $end_line && $end_line <= $this->getEndLine() ? $end_line : $this->getEndLine();
        $length = $end_line - $start_line + 1;
        $source_code = array_slice($this->getSource(), $start_line - 1, $length);
        if ($show_line) {
            foreach ($source_code as $key => $code) {
                $code = $start_line + $key . ' ' . $code;
                $source_code[$key] = $code;
            }
        }

        return implode($source_code);
    }

    /**
     * 获取反射函数开始行号
     * @return int
     */
    public function getStartLine()
    {
        return $this->function_instance->getStartLine();
    }

    /**
     * 获取反射函数结束行号
     * @return int
     */
    public function getEndLine()
    {
        return $this->function_instance->getEndLine();
    }

}