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

namespace CodeIgniter\Database\Live;

use CodeIgniter\Database\BasePreparedQuery;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Database\Query;
use CodeIgniter\Database\ResultInterface;
use CodeIgniter\Exceptions\BadMethodCallException;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\Database\Seeds\CITestSeeder;

/**
 * @internal
 */
#[Group('DatabaseLive')]
final class PreparedQueryTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $seed                   = CITestSeeder::class;
    private ?BasePreparedQuery $query = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->query = null;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        try {
            $this->query->close();
        } catch (BadMethodCallException) {
            $this->query = null;
        }
    }

    public function testPrepareReturnsPreparedQuery(): void
    {
        $this->query = $this->db->prepare(static fn ($db) => $db->table('user')->insert([
            'name'    => 'a',
            'email'   => 'b@example.com',
            'country' => 'JP',
        ]));

        $this->assertInstanceOf(BasePreparedQuery::class, $this->query);

        $ec  = $this->db->escapeChar;
        $pre = $this->db->DBPrefix;

        $placeholders = '?, ?, ?';

        if ($this->db->DBDriver === 'Postgre') {
            $placeholders = '$1, $2, $3';
        }

        if ($this->db->DBDriver === 'SQLSRV') {
            $database = $this->db->getDatabase();
            $expected = "INSERT INTO {$ec}{$database}{$ec}.{$ec}{$this->db->schema}{$ec}.{$ec}{$pre}user{$ec} ({$ec}name{$ec},{$ec}email{$ec},{$ec}country{$ec}) VALUES ({$placeholders})";
        } else {
            $expected = "INSERT INTO {$ec}{$pre}user{$ec} ({$ec}name{$ec}, {$ec}email{$ec}, {$ec}country{$ec}) VALUES ({$placeholders})";
        }

        $this->assertSame($expected, $this->query->getQueryString());
    }

    public function testPrepareReturnsManualPreparedQuery(): void
    {
        $this->query = $this->db->prepare(static function ($db) {
            $sql = "INSERT INTO {$db->protectIdentifiers($db->DBPrefix . 'user')} ("
                . $db->protectIdentifiers('name') . ', '
                . $db->protectIdentifiers('email') . ', '
                . $db->protectIdentifiers('country')
                . ') VALUES (?, ?, ?)';

            return (new Query($db))->setQuery($sql);
        });

        $this->assertInstanceOf(BasePreparedQuery::class, $this->query);

        $ec  = $this->db->escapeChar;
        $pre = $this->db->DBPrefix;

        $placeholders = '?, ?, ?';

        if ($this->db->DBDriver === 'Postgre') {
            $placeholders = '$1, $2, $3';
        }

        $expected = "INSERT INTO {$ec}{$pre}user{$ec} ({$ec}name{$ec}, {$ec}email{$ec}, {$ec}country{$ec}) VALUES ({$placeholders})";

        $this->assertSame($expected, $this->query->getQueryString());
    }

    public function testExecuteRunsQueryAndReturnsTrue(): void
    {
        $this->query = $this->db->prepare(static fn ($db) => $db->table('user')->insert([
            'name'    => 'a',
            'email'   => 'b@example.com',
            'country' => 'x',
        ]));

        $this->assertTrue($this->query->execute('foo', 'foo@example.com', 'US'));
        $this->assertTrue($this->query->execute('bar', 'bar@example.com', 'GB'));

        $this->dontSeeInDatabase($this->db->DBPrefix . 'user', ['name' => 'a', 'email' => 'b@example.com']);
        $this->seeInDatabase($this->db->DBPrefix . 'user', ['name' => 'foo', 'email' => 'foo@example.com']);
        $this->seeInDatabase($this->db->DBPrefix . 'user', ['name' => 'bar', 'email' => 'bar@example.com']);
    }

    public function testExecuteRunsQueryManualAndReturnsTrue(): void
    {
        $this->query = $this->db->prepare(static function ($db) {
            $sql = "INSERT INTO {$db->protectIdentifiers($db->DBPrefix . 'user')} ("
                . $db->protectIdentifiers('name') . ', '
                . $db->protectIdentifiers('email') . ', '
                . $db->protectIdentifiers('country')
                . ') VALUES (?, ?, ?)';

            return (new Query($db))->setQuery($sql);
        });

        $this->assertTrue($this->query->execute('foo', 'foo@example.com', 'US'));
        $this->assertTrue($this->query->execute('bar', 'bar@example.com', 'GB'));

        $this->seeInDatabase($this->db->DBPrefix . 'user', ['name' => 'foo', 'email' => 'foo@example.com']);
        $this->seeInDatabase($this->db->DBPrefix . 'user', ['name' => 'bar', 'email' => 'bar@example.com']);
    }

    public function testExecuteRunsQueryAndReturnsFalse(): void
    {
        $this->query = $this->db->prepare(static fn ($db) => $db->table('without_auto_increment')->insert([
            'key'   => 'a',
            'value' => 'b',
        ]));

        $this->disableDBDebug();

        $this->assertTrue($this->query->execute('foo1', 'bar'));
        $this->assertFalse($this->query->execute('foo1', 'baz'));

        $this->enableDBDebug();

        $this->seeInDatabase($this->db->DBPrefix . 'without_auto_increment', ['key' => 'foo1', 'value' => 'bar']);
        $this->dontSeeInDatabase($this->db->DBPrefix . 'without_auto_increment', ['key' => 'foo1', 'value' => 'baz']);
    }

    public function testExecuteRunsQueryManualAndReturnsFalse(): void
    {
        $this->query = $this->db->prepare(static function ($db) {
            $sql = "INSERT INTO {$db->protectIdentifiers($db->DBPrefix . 'without_auto_increment')} ("
                . $db->protectIdentifiers('key') . ', '
                . $db->protectIdentifiers('value')
                . ') VALUES (?, ?)';

            return (new Query($db))->setQuery($sql);
        });

        $this->disableDBDebug();

        $this->assertTrue($this->query->execute('foo1', 'bar'));
        $this->assertFalse($this->query->execute('foo1', 'baz'));

        $this->enableDBDebug();

        $this->seeInDatabase($this->db->DBPrefix . 'without_auto_increment', ['key' => 'foo1', 'value' => 'bar']);
        $this->dontSeeInDatabase($this->db->DBPrefix . 'without_auto_increment', ['key' => 'foo1', 'value' => 'baz']);
    }

    public function testExecuteSelectQueryAndCheckTypeAndResult(): void
    {
        $this->query = $this->db->prepare(static fn ($db) => $db->table('user')->select('name, email, country')->where([
            'name' => 'foo',
        ])->get());

        $result = $this->query->execute('Derek Jones');

        $this->assertInstanceOf(ResultInterface::class, $result);

        $expectedRow = ['name' => 'Derek Jones', 'email' => 'derek@world.com', 'country' => 'US'];
        $this->assertSame($expectedRow, $result->getRowArray());
    }

    public function testExecuteSelectQueryManualAndCheckTypeAndResult(): void
    {
        $this->query = $this->db->prepare(static function ($db) {
            $sql = 'SELECT '
                . $db->protectIdentifiers('name') . ', '
                . $db->protectIdentifiers('email') . ', '
                . $db->protectIdentifiers('country') . ' '
                . "FROM {$db->protectIdentifiers($db->DBPrefix . 'user')}"
                . "WHERE {$db->protectIdentifiers('name')} = ?";

            return (new Query($db))->setQuery($sql);
        });

        $result = $this->query->execute('Derek Jones');

        $this->assertInstanceOf(ResultInterface::class, $result);

        $expectedRow = ['name' => 'Derek Jones', 'email' => 'derek@world.com', 'country' => 'US'];
        $this->assertSame($expectedRow, $result->getRowArray());
    }

    public function testExecuteRunsInvalidQuery(): void
    {
        $this->expectException(DatabaseException::class);

        // Not null `country` is missing
        $this->query = $this->db->prepare(static fn ($db) => $db->table('user')->insert([
            'name'  => 'a',
            'email' => 'b@example.com',
        ]));

        $this->query->execute('foo', 'foo@example.com', 'US');
    }

    public function testDeallocatePreparedQueryThenTryToExecute(): void
    {
        $this->query = $this->db->prepare(static fn ($db) => $db->table('user')->insert([
            'name'    => 'a',
            'email'   => 'b@example.com',
            'country' => 'x',
        ]));

        $this->query->close();

        // Try to execute a non-existing prepared statement
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('You must call prepare before trying to execute a prepared statement.');

        $this->query->execute('bar', 'bar@example.com', 'GB');
    }

    public function testDeallocatePreparedQueryThenTryToClose(): void
    {
        $this->query = $this->db->prepare(
            static fn ($db) => $db->table('user')->insert([
                'name'    => 'a',
                'email'   => 'b@example.com',
                'country' => 'x',
            ])
        );

        $this->query->close();

        // Try to close a non-existing prepared statement
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage(
            'Cannot call close on a non-existing prepared statement.'
        );

        $this->query->close();
    }

    public function testInsertBinaryData(): void
    {
        $params = [];
        if ($this->db->DBDriver === 'SQLSRV') {
            $params = [0 => SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY)];
        }

        $this->query = $this->db->prepare(static fn ($db) => $db->table('type_test')->insert([
            'type_blob' => 'binary',
        ]), $params);

        $fileContent = file_get_contents(TESTPATH . '_support/Images/EXIFsamples/landscape_0.jpg');
        $this->assertTrue($this->query->execute($fileContent));

        $id = $this->db->DBDriver === 'SQLSRV'
            // It seems like INSERT for a prepared statement is run in the
            // separate execution context even though it's part of the same session
            ? (int) ($this->db->query('SELECT @@IDENTITY AS insert_id')->getRow()->insert_id ?? 0)
            : $this->db->insertID();
        $builder = $this->db->table('type_test');

        if ($this->db->DBDriver === 'Postgre') {
            $file = $builder->select("ENCODE(type_blob, 'base64') AS type_blob")->where('id', $id)->get()->getRow();
            $file = base64_decode($file->type_blob, true);
        } elseif ($this->db->DBDriver === 'OCI8') {
            $file = $builder->select('type_blob')->where('id', $id)->get()->getRow();
            $file = $file->type_blob->load();
        } else {
            $file = $builder->select('type_blob')->where('id', $id)->get()->getRow();
            $file = $file->type_blob;
        }

        $this->assertSame(strlen($fileContent), strlen($file));
    }

    public function testHandleTransStatusMarksTransactionFailedDuringTransaction(): void
    {
        $this->db->transStart();

        // Verify we're in a transaction
        $this->assertSame(1, $this->db->transDepth);

        // Prepare a query that will fail (duplicate key)
        $this->query = $this->db->prepare(static fn ($db) => $db->table('without_auto_increment')->insert([
            'key'   => 'a',
            'value' => 'b',
        ]));

        $this->disableDBDebug();

        $this->assertTrue($this->query->execute('test_key', 'test_value'));
        $this->assertTrue($this->db->transStatus());

        $this->seeInDatabase($this->db->DBPrefix . 'without_auto_increment', [
            'key'   => 'test_key',
            'value' => 'test_value'
        ]);

        $this->assertFalse($this->query->execute('test_key', 'different_value'));
        $this->assertFalse($this->db->transStatus());

        $this->enableDBDebug();

        // Complete the transaction - should rollback due to failed status
        $this->assertFalse($this->db->transComplete());

        // Verify the first insert was rolled back
        $this->dontSeeInDatabase($this->db->DBPrefix . 'without_auto_increment', [
            'key'   => 'test_key',
            'value' => 'test_value'
        ]);

        $this->db->resetTransStatus();
    }
}
