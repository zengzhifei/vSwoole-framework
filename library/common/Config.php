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
    public static function loadConfig(string $config_file = 'config', bool $is_force = false)
    {
        $config_file = $config_file == '' ? '*' : $config_file;
        if ($is_force) {
            $config_common = [];
            if (defined('VSWOOLE_CONFIG_PATH') && is_dir(VSWOOLE_CONFIG_PATH)) {
                $config_files_common = glob(VSWOOLE_CONFIG_PATH . $config_file . VSWOOLE_CONFIG_EXT);
                foreach ($config_files_common as $configFile) {
                    $configInfo = pathinfo($configFile);
                    $config = require $configFile;
                    is_array($config) && $config_common[$configInfo['filename']] = $config;
                }
            }
            $config_lib = [];
            if (defined('VSWOOLE_LIB_CONF_PATH') && is_dir(VSWOOLE_LIB_CONF_PATH)) {
                $config_files_lib = glob(VSWOOLE_LIB_CONF_PATH . $config_file . VSWOOLE_CONFIG_EXT);
                foreach ($config_files_lib as $configFile) {
                    $configInfo = pathinfo($configFile);
                    $config = require $configFile;
                    is_array($config) && $config_lib[$configInfo['filename']] = $config;
                }
            }
            foreach ($config_common as $key => $config) {
                if (array_key_exists($key, $config_lib)) {
                    self::$_configs[$key] = array_merge($config_lib[$key], $config_common[$key]);
                } else {
                    self::$_configs[$key] = $config;
                }
            }
            foreach ($config_lib as $key => $config) {
                if (!array_key_exists($key, $config_common)) {
                    self::$_configs[$key] = $config;
                }
            }
        } else {
            if (!isset(self::$_configs[$config_file])) {
                $config_common = [];
                if (defined('VSWOOLE_CONFIG_PATH') && is_dir(VSWOOLE_CONFIG_PATH)) {
                    $config_files_common = glob(VSWOOLE_CONFIG_PATH . $config_file . VSWOOLE_CONFIG_EXT);
                    foreach ($config_files_common as $configFile) {
                        $configInfo = pathinfo($configFile);
                        $config = require $configFile;
                        is_array($config) && $config_common[$configInfo['filename']] = $config;
                    }
                }
                $config_lib = [];
                if (defined('VSWOOLE_LIB_CONF_PATH') && is_dir(VSWOOLE_LIB_CONF_PATH)) {
                    $config_files_lib = glob(VSWOOLE_LIB_CONF_PATH . $config_file . VSWOOLE_CONFIG_EXT);
                    foreach ($config_files_lib as $configFile) {
                        $configInfo = pathinfo($configFile);
                        $config = require $configFile;
                        is_array($config) && $config_lib[$configInfo['filename']] = $config;
                    }
                }
                foreach ($config_common as $key => $config) {
                    if (array_key_exists($key, $config_lib)) {
                        self::$_configs[$key] = isset(self::$_configs[$key]) ? self::$_configs[$key] : array_merge($config_lib[$key], $config_common[$key]);
                    } else {
                        self::$_configs[$key] = isset(self::$_configs[$key]) ? self::$_configs[$key] : $config;
                    }
                }
                foreach ($config_lib as $key => $config) {
                    if (!array_key_exists($key, $config_common)) {
                        self::$_configs[$key] = isset(self::$_configs[$key]) ? self::$_configs[$key] : $config;
                    }
                }
            }
        }

        self::$config_file = $config_file;
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
                            $config = null;
                            break;
                        }
                    }
                    return $config;
                }
            } else {
                return null;
            }
        } else {
            return self::$config_file == '*' ? self::$_configs : (isset(self::$_configs[self::$config_file]) ? self::$_configs[self::$config_file] : null);
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