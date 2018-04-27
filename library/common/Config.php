<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+

namespace vSwoole\library\common;

class Config
{
    //配置
    private static $_configs = [];
    //单例
    private static $_instance = null;
    //查询文件
    public static $config_file;

    /**
     * 装载配置文件
     * @param string $config_file
     * @param bool $is_force
     * @return null|Config
     */
    public static function loadConfig(string $config_file = '*', bool $is_force = false)
    {
        if (defined('VSWOOLE_CONFIG_PATH') && is_dir(VSWOOLE_CONFIG_PATH)) {
            $config_file = $config_file == '' ? '*' : $config_file;
            if ($is_force) {
                $configFiles = glob(VSWOOLE_CONFIG_PATH . $config_file . VSWOOLE_CONFIG_EXT);
                foreach ($configFiles as $configFile) {
                    $configInfo = pathinfo($configFile);
                    self::$_configs[$configInfo['filename']] = require $configFile;
                }
            } else {
                if (!isset(self::$_configs[$config_file])) {
                    $configFiles = glob(VSWOOLE_CONFIG_PATH . $config_file . VSWOOLE_CONFIG_EXT);
                    foreach ($configFiles as $configFile) {
                        $configInfo = pathinfo($configFile);
                        self::$_configs[$configInfo['filename']] = isset(self::$_configs[$configInfo['filename']]) ? self::$_configs[$configInfo['filename']] : require $configFile;
                    }
                }
            }
            self::$config_file = $config_file;
        }

        self::$_instance = is_null(self::$_instance) ? new self() : self::$_instance;
        return self::$_instance;
    }

    /**
     * 获取配置
     * @param string|null $configKey
     * @return array|mixed|null
     */
    public function get(string $configKey = null)
    {
        if (!is_null($configKey)) {
            if (!empty(self::$_configs)) {
                if (false === stripos($configKey, '.')) {
                    if (self::$config_file == '*') {
                        return isset(self::$_configs[$configKey]) ? self::$_configs[$configKey] : null;
                    } else {
                        return isset(self::$_configs[self::$config_file][$configKey]) ? self::$_configs[self::$config_file][$configKey] : null;
                    }
                } else {
                    $configKeys = explode('.', $configKey);
                    $config = self::$config_file == '*' ? self::$_configs : self::$_configs[self::$config_file];
                    foreach ($configKeys as $configKey) {
                        if (isset($config[$configKey])) {
                            $config = $config[$configKey];
                        } else {
                            break;
                        }
                    }
                    return $config;
                }
            } else {
                return null;
            }
        } else {
            return self::$config_file == '*' ? self::$_configs : self::$_configs[self::$config_file];
        }
    }

    /**
     * 获取缓存的配置
     * @return array
     */
    public static function getCacheConfig()
    {
        return self::$_configs;
    }

    /**
     * 缓存所有配置文件
     */
    public static function cacheConfig()
    {
        self::loadConfig('*', true);
    }
}