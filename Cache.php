<?php

namespace beliy\cassandra;

use Yii;
use yii\base\InvalidConfigException;

/**
 * Class Cache
 * @author Dima Beliy <dmitriy.belyy@gmail.com>
 * @version 1.0.alpha
 * @package beliy\cassandra
 */
class Cache extends \yii\caching\Cache
{
    /**
     * @var Connection|string|array the Cassandra [[Connection]] object or the application component ID.
     * do configure cassandra connection as an application component.
     * After the Cache object is created, if you want to change this property, you should only assign it
     * with a Cassandra [[Connection]] object.
     */
    public $cassandra = 'cassandra';

    /**
     * @var string prefix for table.
     */
    public $tablePrefix= '';

    /**
     * @var string table name.
     */
    public $tableName = 'cache';

    /**
     * Initializes the Cassandra Cache component.
     * This method will initialize the [[cassandra]] property to make sure it refers to a valid Connection connection.
     * @throws InvalidConfigException if [[cassandra]] is invalid.
     */
    public function init()
    {
        parent::init();
        if (is_string($this->cassandra)) {
            $this->cassandra = Yii::$app->get($this->cassandra);
        } elseif (is_array($this->cassandra)) {
            if (!isset($this->cassandra['class'])) {
                $this->cassandra['class'] = Connection::className();
            }
            $this->cassandra = Yii::createObject($this->cassandra);
        }
        if (!$this->cassandra instanceof Connection) {
            throw new InvalidConfigException("Cache::cassandra must be either a Cassandra connection instance or the application component ID of a Cassandra connection.");
        }
    }

    /**
     * Init cache table. You must run this manually once.
     */
    public  function createTable()
    {
        if ($this->cassandra instanceof Connection) {

            $cql = 'CREATE TABLE IF NOT EXISTS ' . $this->getTableFullName() . " (
                        key text PRIMARY KEY,
                        value blob
                    ) WITH COMPACT STORAGE AND
                    bloom_filter_fp_chance=0.001000 AND
                    caching='ALL' AND
                    comment='Used for cassandra caching at yii2' AND
                    dclocal_read_repair_chance=0.000000 AND
                    gc_grace_seconds=86400 AND
                    read_repair_chance=0.100000 AND
                    replicate_on_write='true' AND
                    populate_io_cache_on_flush='false' AND
                    compaction={'class': 'LeveledCompactionStrategy', 'sstable_size_in_mb': 256} AND
                    compression={'sstable_compression': 'DeflateCompressor'};";
            $this->cassandra->cql3Query($cql);
        }
    }

    /**
     * @inheritdoc
     */
    public function exists($key)
    {
        $key = $this->buildKey($key);
        $cql = 'SELECT value FROM ' . $this->getTableFullName() . " WHERE key='{$key}';";
        $res = $this->cassandra->cql3Query($cql);
        $rows =$this->cassandra->cqlGetRows($res);
        $row = !empty($rows) && $rows['0'] ? $rows['0'] : false;

        return (bool) isset($row['value']) && !empty($row['value']) ? true : false;
    }

    /**
     * @inheritdoc
     */
    protected function getValue($key)
    {
        $key = $this->buildKey($key);
        $cql = 'SELECT value FROM ' . $this->getTableFullName() . " WHERE key='{$key}';";
        $res = $this->cassandra->cql3Query($cql);
        $value = $this->cassandra->cqlGetRows($res);
        $value = isset($value['0']) ? $value['0'] : false ;

        return empty($value) && isset($value['value']) ? false : $value['value'];
    }

    /**
     * @inheritdoc
     */
    protected function getValues($keys)
    {
        $strKeys = "'". implode("','", $keys) ."'";
        $cql = 'SELECT * FROM ' . $this->getTableFullName() . ' WHERE key IN(' . $strKeys . ');';
        $res = $this->cassandra->cql3Query($cql);
        $response = $this->cassandra->cqlGetRows($res);

        $result = [];
        if(!empty($response) && is_array($response)) {
            foreach($response as $res) {
                $result[$res['key']] = $res['value'];
            }
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    protected function setValue($key, $value, $expire)
    {
        $cql = 'INSERT INTO ' . $this->getTableFullName() . " (key, value)
                VALUES('" . $key . "', asciiAsBlob('{$value}'))";

        if ($expire > 0) {
            $ttl = ' USING TTL ' . (int) $expire;
            $cql .= $ttl;
        }
        $this->cassandra->cql3Query($cql);

        return true;
    }

    /**
     * @TODO need normal implementation. Now override value.
     * @inheritdoc
     */
    protected function addValue($key, $value, $expire)
    {
        return $this->setValue($key, $value, $expire);
    }

    /**
     * @inheritdoc
     */
    protected function deleteValue($key)
    {
        $cql = 'DELETE FROM ' . $this->getTableFullName() . " WHERE key='" . $key . "'";
        $this->cassandra->cql3Query($cql);
        return true;
    }

    /**
     * @inheritdoc
     */
    protected function flushValues()
    {
        $this->cassandra->cql3Query('TRUNCATE ' . $this->getTableFullName());
        return true;
    }

    /**
     * Get full table name if you use prefix.
     * @return string
     */
    protected function getTableFullName()
    {
        $prefix = !empty($this->tablePrefix) && is_string($this->tablePrefix) ? $this->tablePrefix . '_' : '';
        return $prefix . $this->tableName;
    }

}