<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+

namespace vSwoole\library\common;


use Throwable;

class Exception extends \Exception
{
    //错误代码
    const ERROR = 1;
    //异常代码
    const Exception = 0;

    /**
     * Exception constructor.
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * 注册异常错误自动捕获事件
     */
    public static function register()
    {
        error_reporting(E_ALL);
        set_error_handler([__CLASS__, 'swooleError']);
        set_exception_handler([__CLASS__, 'swooleException']);
        register_shutdown_function([__CLASS__, 'swooleShutdown']);
    }

    /**
     * 捕获错误
     * @param int $errorCode
     * @param string $errorMsg
     * @param string $errorFile
     * @param int $errorLine
     */
    public static function swooleError(int $errorCode = 0, string $errorMsg = '', string $errorFile = '', int $errorLine = 0)
    {
        $error = new self($errorMsg, $errorCode);
        $error->file = $errorFile;
        $error->line = $errorLine;
        self::reportError($error);
    }

    /**
     * 捕获异常
     * @param \Exception $exception
     */
    public static function swooleException(Throwable $exception)
    {
        if ($exception instanceof \Exception) {
            self::reportException($exception);
        } else {
            self::reportError($exception);
        }
    }

    /**
     * 捕获脚本结束
     */
    public static function swooleShutdown()
    {
        if (!is_null($error = error_get_last())) {
            $error = new self($error['message'], $error['type']);
            $error->file = $error['file'];
            $error->line = $error['line'];
            VSWOOLE_IS_CLI ? self::reportException($error) : self::reportError($error);
        }
    }

    /**
     * 判断捕获的错误是否为致命错误
     * @param $errorCode
     * @return bool
     */
    protected static function isFatal($errorCode)
    {
        return in_array($errorCode, [E_COMPILE_ERROR, E_CORE_ERROR, E_ERROR, E_PARSE, E_USER_ERROR]);
    }

    /**
     * 获取错误级别
     * @param int $errorCode
     * @return mixed|string
     */
    protected static function getErrorGrade(int $errorCode)
    {
        $error_grade = [
            E_ERROR             => 'E_ERROR',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_WARNING           => 'E_WARNING',
            E_PARSE             => 'E_PARSE',
            E_NOTICE            => 'E_NOTICE',
            E_STRICT            => 'E_STRICT',
            E_DEPRECATED        => 'E_DEPRECATED',
            E_CORE_ERROR        => 'E_CORE_ERROR',
            E_CORE_WARNING      => 'E_CORE_WARNING',
            E_COMPILE_ERROR     => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING   => 'E_COMPILE_WARNING',
            E_USER_ERROR        => 'E_USER_ERROR',
            E_USER_WARNING      => 'E_USER_WARNING',
            E_USER_NOTICE       => 'E_USER_NOTICE',
            E_USER_DEPRECATED   => 'E_USER_DEPRECATED',
            E_ALL               => 'E_ALL'
        ];

        return array_key_exists($errorCode, $error_grade) ? $error_grade[$errorCode] : 'E_UNKONW';
    }

    /**
     * 根据错误级别记录错误日志
     * @param Throwable $e
     */
    public static function logException(Throwable $e)
    {
        if (defined('VSWOOLE_IS_LOG') && VSWOOLE_IS_LOG) {
            if (self::isFatal($e->getCode())) {
                Log::write(self::getException($e));
            }
        }
    }

    /**
     * 报告异常
     * @param Throwable $exception
     */
    public static function reportException(Throwable $exception)
    {
        self::logException($exception);

        if (defined('VSWOOLE_IS_DEBUG') && VSWOOLE_IS_DEBUG) {
            echo self::parseException($exception, self::Exception);
        } else {
            echo self::defaultException();
        }
    }

    /**
     * 报告错误
     * @param Throwable $error
     */
    public static function reportError(Throwable $error)
    {
        self::logException($error);

        if (defined('VSWOOLE_IS_DEBUG') && VSWOOLE_IS_DEBUG) {
            die(self::parseException($error, self::ERROR));
        } else {
            die(self::defaultException());
        }
    }

    /**
     * 获取异常或错误信息
     * @param \Exception $e
     * @return string
     */
    public static function getException(Throwable $e)
    {
        $type = get_class($e);
        $message = $e->getMessage();
        $file = $e->getFile();
        $line = $e->getLine();
        $code = $e->getCode();
        $trace = $e->getTraceAsString();
        $exception_string = $type . ': ' . $message . PHP_EOL;
        $exception_string .= 'In ' . $file . ': ' . $line . PHP_EOL;
        $exception_string .= 'Exception Code: ' . self::getErrorGrade($code) . '[' . $code . ']' . PHP_EOL;
        $exception_string .= 'Exception Trace: ' . PHP_EOL;
        $exception_string .= $trace . PHP_EOL;

        return $exception_string;
    }

    /**
     * 异常或错误分析
     * @param Throwable $e
     * @param int $status
     * @return string
     */
    private static function parseException(Throwable $e, int $status = self::Exception)
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
        if (VSWOOLE_IS_CLI) {
            $exception_string = PHP_EOL;
            $exception_string .= $type . ': ' . $message . PHP_EOL;
            $exception_string .= 'In ' . $file . ': ' . $line . PHP_EOL;
            $exception_string .= 'Exception Code: ' . self::getErrorGrade($code) . '[' . $code . ']' . PHP_EOL;
            $exception_string .= 'Exception Trace: ' . PHP_EOL;
            $exception_string .= $trace . PHP_EOL . PHP_EOL;
        } else {
            $status = $status == self::Exception ? '异常' : '错误';
            $errorCodeMsg = self::getErrorGrade($code);
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
                        <td style="font-size: 0.9rem;border:1px solid #cad9ea;padding:0 1em 0;">{$errorCodeMsg}[{$code}]</td>
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
        if (VSWOOLE_IS_CLI) {
            $default_exception = '可能发生了一些错误，╮(╯﹏╰）╭';
        } else {
            $default_exception = <<<EOT
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
        return $default_exception . PHP_EOL;
    }

}