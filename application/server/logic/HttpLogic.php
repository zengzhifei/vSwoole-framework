<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |                  
// +----------------------------------------------------------------------+

namespace vSwoole\application\server\logic;


use think;
use vSwoole\library\common\exception\Exception;

class HttpLogic
{
    /**
     * 设置对象
     * HttpLogic constructor.
     * @param \swoole_http_server $server
     */
    public function __construct(\swoole_http_server $server)
    {
        $GLOBALS['server'] = $server;

        //载入第三方框架
        //$this->loadFrameWork();
    }

    /**
     * 引入第三方框架
     */
    protected function loadFrameWork()
    {
        define('APP_PATH', VSWOOLE_ROOT . '../tp5/application/');
        require VSWOOLE_ROOT . '../tp5/thinkphp/base.php';
    }

    /**
     * 请求装换
     * @param \swoole_http_request $request
     */
    protected function conversionRequest(\swoole_http_request $request)
    {
        $_SERVER = isset($request->server) ? $request->server : [];
        $_GET = isset($request->get) ? $request->get : [];
        $_POST = isset($request->post) ? $request->post : [];
        $_COOKIE = isset($request->cookie) ? $request->cookie : [];
        $_FILES = isset($request->files) ? $request->files : [];
        $_REQUEST = isset($request->header) ? $request->header : [];
    }

    /**
     * 请求执行
     * @param \swoole_http_request $request
     * @param \swoole_http_response $response
     * @throws \ReflectionException
     */
    public function execute(\swoole_http_request $request, \swoole_http_response $response)
    {
        if ($request->server['request_uri'] == '/favicon.ico') {
            $response->status(404);
            $response->end();
            return;
        } else {
            $this->conversionRequest($request);
        }

        ob_start();
        try {
            think\Container::get('app', [defined('APP_PATH') ? APP_PATH : ''])
                ->run()
                ->send();
        } catch (\Exception $e) {
            Exception::reportException($e);
        }
        $res = ob_get_contents();
        ob_clean();

        $response->header('Content-Type', 'text/html;charset=utf-8');
        $response->end($res);
    }
}