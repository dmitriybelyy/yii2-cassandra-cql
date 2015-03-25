<?php

namespace beliy\cassandra;

require_once('autoload.php');

use \yii\base\Component;
use phpcassa\Connection\ConnectionPool;
use phpcassa\Schema\DataType;
use cassandra\Compression;
use cassandra\ConsistencyLevel;

/**
 * Cassandra Wrapper for Yii 2.
 * @author Dima Beliy <dmitriy.belyy@gmail.com>
 * @version 1.0.alpha
 * @package beliy\cassandra
 */
class Connection extends Component
{
    protected $pool = null;
    public $keyspace = null;
    public $servers = null;

    /**
     * Establish connection to cassandra cluster
     * @return \phpcassa\Connection\ConnectionWrapper
     */
    private function getRaw()
    {
        if ($this->pool === null) {
            $this->pool = new ConnectionPool($this->keyspace, $this->servers);
        }
        return $this->pool->get();
    }

    /**
     * Execute cql3 query.
     * @param $query
     * @param int $compression
     * @param int $consistency
     * @return object
     */
    public function cql3Query($query, $compression=Compression::NONE, $consistency=ConsistencyLevel::ONE)
    {
        $raw = $this->getRaw();
        $cqlResult = $raw->client->execute_cql3_query($query, $compression, $consistency);
        $this->pool->return_connection($raw);

        return $cqlResult;
    }

    /**
     * Retrieving the correct integer value.
     * Eliminates the problem of incorrect binary to different data types in cassandra including integer types.
     * @param $cqlResult
     * @return array|null
     * @link http://stackoverflow.com/questions/16139362/cassandra-is-not-retrieving-the-correct-integer-value
     */
    public function cqlGetRows($cqlResult)
    {
        if ($cqlResult->type == 1) {
            $rows = array();
            foreach ($cqlResult->rows as $rowIndex => $cqlRow) {
                $cols = array();
                foreach ($cqlRow->columns as $colIndex => $column) {
                    $type = DataType::get_type_for($cqlResult->schema->value_types[$column->name]);
                    $cols[$column->name] = $type->unpack($column->value);
                }
                $rows[] = $cols;
            }
            return $rows;
        } else {
            return null;
        }
    }

    /**
     * Perform garbage collection
     */
    public function __destruct()
    {
        if($this->pool !== null) {
            $this->pool->close();
        }
    }

}