<?php

/**
* @brief      Orm Trait
* @author     -storm_author-
* @copyright  -storm_copyright-
* @package    IPS Social Suite
* @subpackage toolbox
* @since      5.1.3
* @version    -storm_version-
*/

namespace IPS\toolbox\Traits;

use InvalidArgumentException;
use IPS\Db;
use IPS\Patterns\ActiveRecordIterator;
use UnderflowException;
use function array_key_exists;
use function defined;
use function header;
use function implode;
use function json_decode;
use function json_encode;
use function mb_substr;
use function property_exists;
use function strlen;
use function explode;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) {
    header( ( $_SERVER[ 'SERVER_PROTOCOL' ] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

/**
* Orm Class
*/
trait Orm
{
    protected $jsonStore = [];

    /**
     * get all the rows in the table for this AR
     * @param array $config
     *
     * @param bool $count
     * @param bool $keepLimit
     * @return ActiveRecordIterator|int
     */
    public static function all(array $config = [], bool $count = false, bool $keepLimit = false)
    {
        if ($count === true) {
            $config['columns'] = 'COUNT(*)';
            if ($keepLimit) {
                unset($config['group'], $config['order']);
            } else {
                unset($config['limit'], $config['group'], $config['order']);
            }
        }
        $columns = static::$databaseTable . '.*';

        if (isset($config['columns'])) {
            $columns = [];
            foreach ($config['columns'] as $column) {
                $columns[] = static::$databaseTable . '.' . $column;
            }
            $columns = implode(',', $columns);
        }

        $sql = Db::i()->select(
            $columns,
            static::$databaseTable,
            $config['where'] ?? null,
            $config['order'] ?? null,
            $config['limit'] ?? null,
            $config['group'] ?? null,
            $config['having'] ?? null,
            $config['flags'] ?? 0
        );

        if (isset($config['key'])) {
            $sql->setKeyField($config['key']);
        }

        if (isset($config['join'])) {
            foreach ($config['join'] as $join) {
                $type = $join['type'] ?? 'LEFT';
                try {
                    $sql->join($join['table'], [$join['on']], $type, $join['using'] ?? false);
                } catch (InvalidArgumentException $e) {
                }
            }
        }

        if ($count === true) {
            try {
                return (int)$sql->first();
            } catch (UnderflowException $e) {
                return 0;
            }
        }

        return new ActiveRecordIterator($sql, static::class);
    }

    /**
     * get the raw data from the DB before any processing is done to it
     * @param bool $fresh
     * @param bool $prefix
     *
     * @return array
     */
    public function getData(bool $fresh = true, bool $prefix = true): array
    {
        if ($fresh === true) {
            $id = static::$databaseColumnId;

            return Db::i()->select(
                '*',
                static::$databaseTable,
                [
                    static::$databasePrefix . static::$databaseColumnId . ' = ?',
                    $this->{$id},
                ]
            )->first();
        }

        $data = $this->_data;

        if ($prefix === false) {
            $return = $data;
        } else {
            $return = [];
            foreach ($data as $k => $v) {
                $return[static::$databasePrefix . $k] = $v;
            }
        }

        return $return;
    }

    /**
     * get any data variable without getter magic done on it
     * @param string $key
     * @return mixed|null
     */
    public function raw(string $key)
    {
        return $this->_data[$key] ?? null;
    }

    /**
     * @param array $values
     * @param bool $prefix
     */
    public function processBitwise(array &$values, bool $prefix = true): void
    {
        foreach (static::$bitOptions as $bitOptions) {
            foreach ($bitOptions as $key => $bitOption) {
                foreach ($bitOption as $bit => $val) {
                    $k = $bit;
                    $ori = $bit;
                    if ($prefix === true && property_exists($this, 'formLangPrefix')) {
                        $k = static::$formLangPrefix . $bit;
                    }
                    if (array_key_exists($k, $values)) {
                        $this->{$key}[$bit] = $values[$k];
                        unset($values[$k]);
                    }

                    if ($prefix === true) {
                        $k = static::$databasePrefix . $ori;
                    }

                    if (array_key_exists($k, $values)) {
                        $this->{$key}[$bit] = $values[$k];
                        unset($values[$k]);
                    }
                }
            }
        }
    }

    /**
     * use by the forms class to set the values.
     * @param string $key
     *
     * @return bool|string|void
     */
    protected function stripPrefix(string $key)
    {
        return mb_substr($key, strlen(static::$formLangPrefix));
    }

    /**
     * use to set arrays to json for db storage
     * @param string $key
     * @param array $data
     */
    protected function setJson(string $key, array $data): void
    {
        unset($this->jsonStore[$key]);
        $this->_data[$key] = json_encode($data);
    }

    /**
     * use to convert json values to php arrays from db data
     * @param string $key
     * @return array
     */
    protected function getJson(string $key): array
    {
        if (!isset($this->jsonStore[$key]) && isset($this->_data[$key]) && $this->_data[$key]) {
            $this->jsonStore[$key] = json_decode($this->_data[$key], true) ?? [];
        }

        return $this->jsonStore[$key] ?? [];
    }

    /**
     * used to set arrays to comma delimited strings for database storage
     * @param string $key
     * @param array $data
     * @return void
     */
    protected function setImplode(string $key, array $data): void
    {
        unset($this->jsonStore[$key]);
        $this->_data[$key] = implode(',',$data);
    }

    /**
     * used to convert comma delimited strings to php arrays
     * @param $key
     * @return array
     */
    protected function getExplode($key): array
    {
        if (!isset($this->jsonStore[$key])) {
            $this->jsonStore[$key] = isset($this->_data['key']) ? explode(',',$this->_data[$key]) : [];
        }

        return $this->jsonStore[$key] ?? [];
    }

}