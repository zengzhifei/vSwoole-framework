<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+

namespace vSwoole\library\common\exception;


use Throwable;
use vSwoole\library\common\Config;
use vSwoole\library\common\Log;
use vSwoole\library\common\reflection\ReflectionClass;
use vSwoole\library\common\reflection\ReflectionFile;

class Exception extends \Exception
{
    //错误代码
    const ERROR = 1;
    //异常代码
    const Exception = 0;

    /**
     * 继承异常基类
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __constructs(string $message = "", int $code = 0, Throwable $previous = null)
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
     * @throws \ReflectionException
     */
    public static function swooleError(int $errorCode = 0, string $errorMsg = '', string $errorFile = '', int $errorLine = 0)
    {
        $error = new self($errorMsg, $errorCode);
        $error->file = $errorFile;
        $error->line = $errorLine;
        self::reportException($error);
    }

    /**
     * 捕获异常
     * @param Throwable $exception
     * @throws \ReflectionException
     */
    public static function swooleException(Throwable $exception)
    {
        self::reportException($exception);
    }

    /**
     * 捕获脚本结束
     */
    public static function swooleShutdown()
    {
        if (!is_null($error = error_get_last())) {
            $e = new ErrorException($error['message'], $error['type']);
            $e->file = $error['file'];
            $e->line = $error['line'];
            self::reportError($e);
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
    protected static function logException(Throwable $e)
    {
        if (Config::loadConfig()->get('is_log')) {
            $grade = Config::loadConfig()->get('log_grade');
            if (is_int($grade) && (E_ALL == $grade || $e->getCode() == $grade)) {
                Log::save(self::getException($e));
            } else if (is_array($grade) && in_array($e->getCode(), $grade)) {
                Log::save(self::getException($e));
            }
        }
    }

    /**
     * 报告异常
     * @param Throwable $exception
     * @throws \ReflectionException
     */
    public static function reportException(Throwable $exception)
    {
        self::logException($exception);

        if (Config::loadConfig()->get('is_debug')) {
            echo self::parseException($exception, self::Exception);
        } else if (Config::loadConfig()->get('show_default_error')) {
            echo self::defaultException();
        }
    }

    /**
     * 报告错误
     * @param Throwable $error
     * @throws \ReflectionException
     */
    public static function reportError(Throwable $error)
    {
        self::logException($error);

        if (Config::loadConfig()->get('is_debug')) {
            die(self::parseException($error, self::ERROR));
        } else if (Config::loadConfig()->get('show_default_error')) {
            die(self::defaultException());
        }
    }

    /**
     * 获取异常或错误信息
     * @param Throwable $e
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
        $exception_string .= $trace;

        return $exception_string;
    }

    /**
     * 获取异常类类名
     * @param Throwable $e
     * @return mixed
     */
    private static function getExceptionClass(Throwable $e)
    {
        $trace = $e->getTrace();
        return isset($trace[0]) ? $trace[0]['class'] : null;
    }

    /**
     * 异常或错误分析
     * @param Throwable $e
     * @param int $status
     * @return string
     * @throws \ReflectionException
     */
    private static function parseException(Throwable $e, int $status = self::Exception)
    {
        $type = get_class($e);
        $message = $e->getMessage();
        $file = $e->getFile();
        $line = $e->getLine();
        $code = $e->getCode();
        if (is_null($className = self::getExceptionClass($e))) {
            $reflection = new ReflectionFile($file);
        } else {
            $reflection = new ReflectionClass($className);
            $fileName = $reflection->getFileName();
            $reflection = $fileName == $file ? $reflection : new ReflectionFile($file);
        }
        $trace = htmlspecialchars($reflection->getSourceCode($line - 10, $line + 10, true));

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
                <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.12.0/styles/default.min.css">
				<script src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.12.0/highlight.min.js"></script>
                <table style="min-width: 50%;margin: 50px auto;empty-cells: show;border-collapse: collapse;border:1px solid #cad9ea;color:#666;text-align: center;">
                    <tr>
                        <th colspan="2" style="font-size: 1.5rem;background-repeat:repeat-x;height:30px;background-color:#f5fafe;">vSwoole</th>
                    </tr>
                    <tr>
                        <td style="font-size: 0.9rem;border:1px solid #cad9ea;height:30px;padding:0 1em 0;">{$status}类型</td>
                        <td style="font-size: 0.9rem;border:1px solid #cad9ea;padding:0 1em 0;">{$type}</td>
                    </tr>
                    <tr>
                        <td style="font-size: 0.9rem;border:1px solid #cad9ea;height:30px;padding:0 1em 0;">{$status}信息</td>
                        <td style="font-size: 0.9rem;border:1px solid #cad9ea;padding:0 1em 0;">{$message}</td>
                    </tr>
                    <tr>
                        <td style="font-size: 0.9rem;border:1px solid #cad9ea;height:30px;padding:0 1em 0;">{$status}文件</td>
                        <td style="font-size: 0.9rem;border:1px solid #cad9ea;padding:0 1em 0;">{$file}</td>
                    </tr>
                    <tr>
                        <td style="font-size: 0.9rem;border:1px solid #cad9ea;height:30px;padding:0 1em 0;">{$status}行号</td>
                        <td style="font-size: 0.9rem;border:1px solid #cad9ea;padding:0 1em 0;">{$line}</td>
                    </tr>
                    <tr>
                        <td style="font-size: 0.9rem;border:1px solid #cad9ea;height:30px;padding:0 1em 0;">{$status}级别</td>
                        <td style="font-size: 0.9rem;border:1px solid #cad9ea;padding:0 1em 0;">{$errorCodeMsg}[{$code}]</td>
                    </tr>
                    <tr>
                        <td style="font-size: 0.9rem;border:1px solid #cad9ea;height:30px;padding:0 1em 0;">{$status}代码</td>
                        <td style="max-width: 800px;background:#F0F0F0;border:1px solid #cad9ea;padding:0 1em 0;text-align: left;"><pre><code class="php" style="font-size: 1.0rem;">{$trace}</code></pre></td>
                    </tr>
                </table> 
                <script>hljs.initHighlightingOnLoad();</script>
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