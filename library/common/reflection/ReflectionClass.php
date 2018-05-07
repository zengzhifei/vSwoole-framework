<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+

namespace vSwoole\library\common\reflection;


class ReflectionClass
{
    /**
     * 反射类实例
     * @var \ReflectionClass
     */
    protected $class_instance;

    /**
     * 实例化反射类
     * @param $class
     * @throws \ReflectionException
     */
    public function __construct($class)
    {
        $this->class_instance = new \ReflectionClass($class);
    }

    /**
     * 获取反射类文件名
     * @return string
     */
    public function getFileName()
    {
        return $this->class_instance->getFileName();
    }

    /**
     * 获取类源代码数组
     * @return array|bool
     */
    public function getSource()
    {
        return @file($this->class_instance->getFileName());
    }

    /**
     * 获取反射类源代码
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
     * 获取反射类开始行号
     * @return int
     */
    public function getStartLine()
    {
        return $this->class_instance->getStartLine();
    }

    /**
     * 获取反射类结束行号
     * @return int
     */
    public function getEndLine()
    {
        return $this->class_instance->getEndLine();
    }

}