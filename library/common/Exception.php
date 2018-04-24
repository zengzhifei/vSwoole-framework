<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+

namespace vSwoole\library\common;

class Exception extends \Exception
{
    //错误代码
    const ERROR = 1;
    //异常代码
    const Exception = 0;

    public static function register()
    {
        error_reporting(E_ALL);
        set_error_handler([__CLASS__, 'appError']);
        set_exception_handler([__CLASS__, 'appException']);
        register_shutdown_function([__CLASS__, 'appShutdown']);
    }

    /**
     * 报告异常
     * @param \Exception $e
     */
    public static function reportException(\Exception $e)
    {
        if (defined('IS_DEBUG') && IS_DEBUG) {
            echo self::parseException($e, self::Exception);
        } else {
            echo self::defaultException();
        }
    }

    /**
     * 报告错误
     * @param \Exception $e
     */
    public static function reportError(\Exception $e)
    {
        if (defined('IS_DEBUG') && IS_DEBUG) {
            exit(self::parseException($e, self::ERROR));
        } else {
            exit(self::defaultException());
        }
    }

    /**
     * 获取异常或错误信息
     * @param \Exception $e
     * @return string
     */
    public static function getException(\Exception $e)
    {
        $type = get_class($e);
        $message = $e->getMessage();
        $file = $e->getFile();
        $line = $e->getLine();
        $code = $e->getCode();
        $trace = $e->getTraceAsString();
        $exception_string = $type . ': ' . $message . PHP_EOL;
        $exception_string .= 'In ' . $file . ': ' . $line . PHP_EOL;
        $exception_string .= 'Exception Code: ' . $code . PHP_EOL;
        $exception_string .= 'Exception Trace: ' . PHP_EOL;
        $exception_string .= $trace . PHP_EOL;

        return $exception_string;
    }

    /**
     * 异常或错误分析
     * @param \Exception $e
     * @param int $status
     * @return string
     */
    private static function parseException(\Exception $e, int $status = self::Exception)
    {
        $type = get_class($e);
        $message = $e->getMessage();
        $file = $e->getFile();
        $line = $e->getLine();
        $code = $e->getCode();
        $trace = $e->getTraceAsString();
        return self::formatException($status, $type, $message, $file, $line, $code, $trace);
    }

    /**
     * 异常或错误信息格式化
     * @param int $status
     * @param string $type
     * @param string $message
     * @param string $file
     * @param int $line
     * @param $code
     * @param string $trace
     * @return string
     */
    private static function formatException(int $status = self::Exception, string $type, string $message, string $file, int $line, $code, string $trace)
    {
        if (php_sapi_name() === 'cli') {
            $exception_string = $type . ': ' . $message . PHP_EOL;
            $exception_string .= 'In ' . $file . ': ' . $line . PHP_EOL;
            $exception_string .= 'Exception Code: ' . $code . PHP_EOL;
            $exception_string .= 'Exception Trace: ' . PHP_EOL;
            $exception_string .= $trace . PHP_EOL . PHP_EOL;
        } else {
            $status = $status == self::Exception ? '异常' : '错误';
            $exception_string = <<<EOT
                <table width="50%" style="margin:50px auto;empty-cells: show;border-collapse: collapse;border:1px solid #cad9ea;color:#666;text-align: center;">
                    <tr>
                        <th colspan="2" style="font-size: 1.5rem;background-repeat:repeat-x;height:30px;background-color:#f5fafe;">vSwoole</th>
                    </tr>
                    <tr>
                        <td width="20%" style="font-size: 0.9rem;border:1px solid #cad9ea;height:30px;padding:0 1em 0;">{$status}类型</td>
                        <td style="font-size: 0.9rem;border:1px solid #cad9ea;padding:0 1em 0;">{$type}</td>
                    </tr>
                    <tr>
                        <td width="20%" style="font-size: 0.9rem;border:1px solid #cad9ea;height:30px;padding:0 1em 0;">{$status}信息</td>
                        <td style="font-size: 0.9rem;border:1px solid #cad9ea;padding:0 1em 0;">{$message}</td>
                    </tr>
                    <tr>
                        <td width="20%" style="font-size: 0.9rem;border:1px solid #cad9ea;height:30px;padding:0 1em 0;">{$status}文件</td>
                        <td style="font-size: 0.9rem;border:1px solid #cad9ea;padding:0 1em 0;">{$file}</td>
                    </tr>
                    <tr>
                        <td width="20%" style="font-size: 0.9rem;border:1px solid #cad9ea;height:30px;padding:0 1em 0;">{$status}位置</td>
                        <td style="font-size: 0.9rem;border:1px solid #cad9ea;padding:0 1em 0;">{$line}</td>
                    </tr>
                    <tr>
                        <td width="20%" style="font-size: 0.9rem;border:1px solid #cad9ea;height:30px;padding:0 1em 0;">{$status}代码</td>
                        <td style="font-size: 0.9rem;border:1px solid #cad9ea;padding:0 1em 0;">{$code}</td>
                    </tr>
                    <tr>
                        <td width="20%" style="font-size: 0.9rem;border:1px solid #cad9ea;height:30px;padding:0 1em 0;">{$status}追踪</td>
                        <td style="font-size: 0.9rem;border:1px solid #cad9ea;padding:0 1em 0;">{$trace}</td>
                    </tr>
                </table> 
EOT;
        }
        return $exception_string;
    }

    /**
     * 默认异常或错误信息模板
     * @return string
     */
    private static function defaultException()
    {
        return <<<EOT
            <table width="50%" style="margin:50px auto;empty-cells: show;border-collapse: collapse;border:1px solid #cad9ea;color:#666;text-align: center;">
                    <tr>
                        <th colspan="2" style="font-size: 1.5rem;background-repeat:repeat-x;height:30px;background-color:#f5fafe;">vSwoole</th>
                    </tr>
                    <tr>
                        <td style="font-size: 0.9rem;border:1px solid #cad9ea;height:30px;padding:0 1em 0;">可能发生了一些错误，╮(╯﹏╰）╭</td>                       
                    </tr>
                </table> 
EOT;
    }

}