<?php

declare(strict_types=1);

/**
 * This file is part of CodeIgniter 4 framework.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace CodeIgniter\Test\Mock;

use CodeIgniter\CodeIgniter;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\BaseResult;
use CodeIgniter\Database\Query;
use CodeIgniter\Database\TableName;
use stdClass;

/**
 * @extends BaseConnection<object|resource, object|resource>
 */
class MockConnection extends BaseConnection
{
    /**
     * @var array{
     *   connect?: object|resource|false|list<object|resource|false>,
     *   execute?: object|resource|false,
     * }
     */
    protected $returnValues = [];

    /**
     * Database schema for Postgre and SQLSRV
     *
     * @var string
     */
    protected $schema;

    /**
     * @var string
     */
    public $database;

    /**
     * @var Query
     */
    public $lastQuery;

    /**
     * @param false|list<false|object|resource>|object|resource $return
     *
     * @return $this
     */
    public function shouldReturn(string $method, $return)
    {
        $this->returnValues[$method] = $return;

        return $this;
    }

    /**
     * Orchestrates a query against the database. Queries must use
     * Database\Statement objects to store the query and build it.
     * This method works with the cache.
     *
     * Should automatically handle different connections for read/write
     * queries if needed.
     *
     * @param mixed $binds
     *
     * @return BaseResult<object|resource, object|resource>|bool|Query
     *
     * @todo BC set $queryClass default as null in 4.1
     */
    public function query(string $sql, $binds = null, bool $setEscapeFlags = true, string $queryClass = '')
    {
        /** @var class-string<Query> $queryClass */
        $queryClass = str_replace('Connection', 'Query', static::class);

        $query = new $queryClass($this);

        $query->setQuery($sql, $binds, $setEscapeFlags);

        if ($this->swapPre !== '' && $this->DBPrefix !== '') {
            $query->swapPrefix($this->DBPrefix, $this->swapPre);
        }

        $startTime = microtime(true);

        $this->lastQuery = $query;

        $this->resultID = $this->simpleQuery($query->getQuery());

        if ($this->resultID === false) {
            $query->setDuration($startTime, $startTime);

            // @todo deal with errors
            return false;
        }

        $query->setDuration($startTime);

        // resultID is not false, so it must be successful
        if ($query->isWriteType()) {
            return true;
        }

        // query is not write-type, so it must be read-type query; return QueryResult
        /** @var class-string<BaseResult> $resultClass */
        $resultClass = str_replace('Connection', 'Result', static::class);

        return new $resultClass($this->connID, $this->resultID);
    }

    /**
     * Connect to the database.
     *
     * @return false|object|resource
     */
    public function connect(bool $persistent = false)
    {
        $return = $this->returnValues['connect'] ?? true;

        if (is_array($return)) {
            // By removing the top item here, we can
            // get a different value for, say, testing failover connections.
            $return = array_shift($this->returnValues['connect']);
        }

        return $return;
    }

    /**
     * Keep or establish the connection if no queries have been sent for
     * a length of time exceeding the server's idle timeout.
     */
    public function reconnect(): bool
    {
        return true;
    }

    /**
     * Select a specific database table to use.
     *
     * @return bool
     */
    public function setDatabase(string $databaseName)
    {
        $this->database = $databaseName;

        return true;
    }

    /**
     * Returns a string containing the version of the database being used.
     */
    public function getVersion(): string
    {
        return CodeIgniter::CI_VERSION;
    }

    /**
     * Executes the query against the database.
     *
     * @return false|object|resource
     */
    protected function execute(string $sql)
    {
        return $this->returnValues['execute'];
    }

    /**
     * Returns the total number of rows affected by this query.
     */
    public function affectedRows(): int
    {
        return 1;
    }

    /**
     * Returns the last error code and message.
     *
     * @return array{code: int, message: string}
     */
    public function error(): array
    {
        return [
            'code'    => 0,
            'message' => '',
        ];
    }

    public function insertID(): int
    {
        return $this->connID->insert_id;
    }

    /**
     * Generates the SQL for listing tables in a platform-dependent manner.
     *
     * @param string|null $tableName If $tableName is provided will return only this table if exists.
     */
    protected function _listTables(bool $constrainByPrefix = false, ?string $tableName = null): string
    {
        return '';
    }

    /**
     * Generates a platform-specific query string so that the column names can be fetched.
     *
     * @param string|TableName $table
     */
    protected function _listColumns($table = ''): string
    {
        return '';
    }

    /**
     * @return list<stdClass>
     */
    protected function _fieldData(string $table): array
    {
        return [];
    }

    /**
     * @return array<string, stdClass>
     */
    protected function _indexData(string $table): array
    {
        return [];
    }

    /**
     * @return array<string, stdClass>
     */
    protected function _foreignKeyData(string $table): array
    {
        return [];
    }

    /**
     * Close the connection.
     *
     * @return void
     */
    protected function _close()
    {
    }

    /**
     * Begin Transaction
     */
    protected function _transBegin(): bool
    {
        return true;
    }

    /**
     * Commit Transaction
     */
    protected function _transCommit(): bool
    {
        return true;
    }

    /**
     * Rollback Transaction
     */
    protected function _transRollback(): bool
    {
        return true;
    }
}
