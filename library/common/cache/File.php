<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+

namespace vSwoole\library\common\cache;


use vSwoole\library\common\exception\Exception;

class File
{
    /**
     * 设置文件缓存
     * @param string $key
     * @param null $value
     * @param string $prefix
     * @param int $expire
     * @return bool|int
     * @throws \ReflectionException
     */
    public static function set(string $key, $value = null, string $prefix = '', int $expire = 0)
    {
        try {
            $file_name = $prefix . md5($key) . VSWOOLE_CACHE_FILE_EXT;
            if (is_null($value)) {
                return @unlink(VSWOOLE_DATA_CACHE_PATH . $file_name);
            } else {
                $data = ['expire' => $expire == 0 ? 0 : time() + $expire, 'value' => $value];
                $content = '<?php' . PHP_EOL . '//' . serialize($data) . PHP_EOL;
                return file_put_contents(VSWOOLE_DATA_CACHE_PATH . $file_name, $content);
            }
        } catch (\Exception $e) {
            Exception::reportException($e);
        }
    }

    /**
     * 获取文件缓存
     * @param string $key
     * @param string $prefix
     * @return mixed|null
     * @throws \ReflectionException
     */
    public static function get(string $key, $prefix = '')
    {
        $file_name = $prefix . md5($key) . VSWOOLE_CACHE_FILE_EXT;
        $file = VSWOOLE_DATA_CACHE_PATH . $file_name;
        if (file_exists($file)) {
            $content = substr(file_get_contents($file), 8);
            $content = $content ? unserialize($content) : [];
            if ($content['expire'] != 0 && time() > $content['expire']) {
                self::set($key, null, $prefix);
                return null;
            } else {
                return isset($content['value']) ? $content['value'] : null;
            }
        } else {
            return null;
        }
    }
}