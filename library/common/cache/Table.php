<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+

namespace vSwoole\library\common\cache;


class Table
{
    /**
     * Table内存表实例
     * @var null|\swoole_table
     */
    protected $table_instance = null;

    /**
     * Table内存表字段可选值
     * @var array
     */
    protected static $column_type = [
        'int'    => \swoole_table::TYPE_INT,
        'string' => \swoole_table::TYPE_STRING,
        'float'  => \swoole_table::TYPE_FLOAT,
    ];

    /**
     * Table内存表字段
     * @var array
     */
    protected $table_column = [];


    /**
     * 实例化内存表
     * @param int $table_size
     */
    public function __construct(int $table_size = 1024)
    {
        $table_size = $table_size > 0 ? $table_size : 1024;
        $this->table_instance = new \swoole_table($table_size);
    }

    /**
     * 创建内存表并设置字段
     * @param array $columns
     */
    public function create(array $columns = [])
    {
        if (count($columns)) {
            foreach ($columns as $key => $column) {
                if (is_string($key) && is_array($column)) {
                    $column_key = $key;
                    $column_type = array_key_exists(strtolower($column[0]), self::$column_type) ? self::$column_type[strtolower($column[0])] : $column[0];
                    $column_length = isset($column[1]) ? $column[1] : null;
                    switch ($column_type) {
                        case self::$column_type['int']:
                            $column_length = in_array($column_length, [1, 2, 4, 8]) ? $column_length : 4;
                            break;
                        case self::$column_type['string']:
                            $column_length = is_int($column_length) ? $column_length : (is_null($column_length) ? 64 : intval($column_length));
                            break;
                        case self::$column_type['float']:
                            $column_length = 8;
                            break;
                    }
                    $this->table_column[$column_key] = [array_search($column_type, self::$column_type), $column_length];
                    $this->table_instance->column($column_key, $column_type, $column_length);
                }
            }
            count($this->table_column) && $this->table_instance->create();
        }
    }

    /**
     * 显示已设置的内存表结构
     * @return string
     */
    public function show()
    {
        $field_max_length = mb_strlen('Field', 'utf8');
        $type_max_length = mb_strlen('Type', 'utf8');
        $length_max_length = mb_strlen('Length', 'utf8');
        foreach ($this->table_column as $key => $column) {
            $field_max_length = mb_strlen($key, 'utf8') > $field_max_length ? mb_strlen($key, 'utf8') : $field_max_length;
            $type_max_length = mb_strlen($column[0], 'utf8') > $type_max_length ? mb_strlen($column[0], 'utf8') : $type_max_length;
            $length_max_length = mb_strlen($column[1], 'utf8') > $length_max_length ? mb_strlen($column[1], 'utf8') : $length_max_length;
        }
        $field_max_length = $field_max_length + 8;
        $type_max_length = $type_max_length + 8;
        $length_max_length = $length_max_length + 8;
        $columns = array_merge(['Field' => ['Type', 'Length']], $this->table_column);
        $table_show = '+' . str_repeat('-', $field_max_length + $type_max_length + $length_max_length + 2) . '+' . PHP_EOL;
        $table_flag = 0;
        foreach ($columns as $key => $column) {
            $field_blank = floor(($field_max_length - mb_strlen($key, 'utf8')) / 2);
            $type_blank = floor(($type_max_length - mb_strlen($column[0], 'utf8')) / 2);
            $length_blank = floor(($length_max_length - mb_strlen($column[1], 'utf8')) / 2);
            $table_show .= '|' . str_repeat(' ', $field_blank) . $key . str_repeat(' ', ($field_max_length - $field_blank - mb_strlen($key, 'utf8')));
            $table_show .= '|' . str_repeat(' ', $type_blank) . $column[0] . str_repeat(' ', ($type_max_length - $type_blank - mb_strlen($column[0], 'utf8')));
            $table_show .= '|' . str_repeat(' ', $length_blank) . $column[1] . str_repeat(' ', ($length_max_length - $length_blank - mb_strlen($column[1], 'utf8')));
            $table_show .= '|' . PHP_EOL;
            $table_flag++;
            if ($table_flag == 1 || $table_flag == count($columns)) {
                $table_show .= '+' . str_repeat('-', $field_max_length + $type_max_length + $length_max_length + 2) . '+' . PHP_EOL;
            }
        }
        return $table_show;
    }

    /**
     * 获取内存表物理大小
     * @return int
     */
    public function getTableSize()
    {
        return $this->table_instance->size;
    }

    /**
     * 获取内存表内存大小
     * @return mixed
     */
    public function getTableMemorySize()
    {
        return $this->table_instance->memorySize;
    }

    /**
     * 获取内存表所有数据
     * @return array
     */
    public function getAll()
    {
        $table_result = [];
        foreach ($this->table_instance as $key => $value) {
            $table_result[$key] = $value;
        }
        return count($table_result) ? $table_result : null;
    }

    /**
     * 删除内存表所有数据
     * @return int
     */
    public function deleteAll()
    {
        $delete_row = 0;
        foreach ($this->table_instance as $key => $value) {
            if ($this->table_instance->del($key)) {
                $delete_row++;
            }
        }
        return $delete_row;
    }

    /**
     * 获取内存表对象实例
     * @return null|\swoole_table
     */
    public function getTable()
    {
        return $this->table_instance;
    }

    /**
     * 魔术方法，执行原生方法
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        // TODO: Implement __call() method.
        return call_user_func_array([$this->table_instance, $name], $arguments);
    }
}