<?php

namespace Wikibase\Lib\Tests\Store\Sql\Terms\Util;

use PHPUnit\Framework\TestCase;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikibase\Lib\Store\Sql\Terms\Util\ReplicaMasterAwareRecordIdsAcquirer;
use Wikimedia\Rdbms\DatabaseSqlite;
use Wikimedia\Rdbms\IDatabase;

/**
 * @covers \Wikibase\Lib\Store\Sql\Terms\Util\ReplicaMasterAwareRecordIdsAcquirer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ReplicaMasterAwareRecordIdsAcquirerTest extends TestCase {

	private const TABLE_DDL_FILE_PATH = __DIR__ . '/ReplicaMasterAwareRecordIdsAcquirerTest_tableDDL.sql';
	private const TABLE_NAME = 'replica_master_aware_record_ids_acquirer_test';
	private const ID_COLUMN = 'id';

	/**
	 * @var IDatabase
	 */
	private $dbMaster;

	/**
	 * @var IDatabase
	 */
	private $dbReplica;

	protected function setUp(): void {
		$this->dbMaster = DatabaseSqlite::newStandaloneInstance( ':memory:' );
		$this->dbMaster->sourceFile( self::TABLE_DDL_FILE_PATH );

		$this->dbReplica = DatabaseSqlite::newStandaloneInstance( ':memory:' );
		$this->dbReplica->sourceFile( self::TABLE_DDL_FILE_PATH );
	}

	public function testWhenAllRecordsExistInReplica() {
		$records = $this->getTestRecords();

		$this->dbReplica->insert(
			self::TABLE_NAME,
			$records
		);
		$this->assertSameRecordsInDb( $records, $this->dbReplica );

		$idsAcquirer = $this->getTestSubjectInstance();
		$acquiredRecordsWithIds = $idsAcquirer->acquireIds( $records );

		$this->assertNoRecordsInDb( $records, $this->dbMaster );
		$this->assertSameRecordsInDb( $acquiredRecordsWithIds, $this->dbReplica );
	}

	public function testWhenAllRecordsExistInMaster() {
		$records = $this->getTestRecords();

		$this->dbMaster->insert(
			self::TABLE_NAME,
			$records
		);
		$this->assertSameRecordsInDb( $records, $this->dbMaster );

		$idsAcquirer = $this->getTestSubjectInstance();
		$acquiredRecordsWithIds = $idsAcquirer->acquireIds( $records );

		$this->assertNoRecordsInDb( $records, $this->dbReplica );
		$this->assertSameRecordsInDb( $acquiredRecordsWithIds, $this->dbMaster );
	}

	public function testWhenAllRecordsDoNotExistInReplicaOrMaster() {
		$records = $this->getTestRecordsWithDuplicate();

		$idsAcquirer = $this->getTestSubjectInstance();
		$acquiredRecordsWithIds = $idsAcquirer->acquireIds( $records );

		$this->assertNoRecordsInDb( $records, $this->dbReplica );
		$this->assertSameRecordsInDb( $acquiredRecordsWithIds, $this->dbMaster );
	}

	public function testWhenSomeRecordsDoNotExistInReplicaButExistInMaster() {
		$records = $this->getTestRecordsWithDuplicate();

		$recordsInReplica = [ $records[0], $records[1] ];
		$recordsInMaster = [ $records[2] ];

		$this->dbReplica->insert(
			self::TABLE_NAME,
			$recordsInReplica
		);
		$this->assertSameRecordsInDb( $recordsInReplica, $this->dbReplica );

		$this->dbMaster->insert(
			self::TABLE_NAME,
			$recordsInMaster
		);
		$this->assertSameRecordsInDb( $recordsInMaster, $this->dbMaster );

		$idsAcquirer = $this->getTestSubjectInstance();
		$acquiredRecordsWithIds = $idsAcquirer->acquireIds( $records );

		$this->assertSame(
			count( $acquiredRecordsWithIds ),
			count( array_unique( $records, SORT_REGULAR ) )
		);
		$this->assertSameRecordsInDb( [ $records[3] ], $this->dbMaster );
		$this->assertNoRecordsInDb( $recordsInReplica, $this->dbMaster );
		$this->assertNoRecordsInDb( $recordsInMaster, $this->dbReplica );
	}

	public function testWhenIgnoringReplica() {
		$records = $this->getTestRecords();

		$this->dbReplica->insert(
			self::TABLE_NAME,
			$records
		);
		$this->assertSameRecordsInDb( $records, $this->dbReplica );

		$idsAcquirer = $this->getTestSubjectInstance(
			ReplicaMasterAwareRecordIdsAcquirer::FLAG_IGNORE_REPLICA );
		$acquiredRecordsWithIds = $idsAcquirer->acquireIds( $records );

		$this->assertSame(
			count( $acquiredRecordsWithIds ),
			count( $records )
		);
		$this->assertSameRecordsInDb( $records, $this->dbMaster );
	}

	private function assertNoRecordsInDb( array $records, IDatabase $db ) {
		$recordsInDbCount = $db->selectRowCount(
			self::TABLE_NAME,
			'*',
			$this->recordsToSelectConditions( $records, $db )
		);

		$this->assertSame( 0, $recordsInDbCount );
	}

	private function assertSameRecordsInDb( array $records, IDatabase $db ) {
		$recordsInDbCount = $db->selectRowCount(
			self::TABLE_NAME,
			'*',
			$this->recordsToSelectConditions( $records, $db )
		);

		$this->assertCount( $recordsInDbCount, $records );
	}

	private function recordsToSelectConditions( array $records, IDatabase $db ) {
		$conditionsPairs = [];
		foreach ( $records as $record ) {
			$conditionPairs[] = $db->makeList( $record, IDatabase::LIST_AND );
		}

		return $db->makeList( $conditionPairs, IDatabase::LIST_OR );
	}

	private function getTestSubjectInstance( $flags = 0x0 ) {
		$loadBalancer = new FakeLoadBalancer( [
			'dbr' => $this->dbReplica,
			'dbw' => $this->dbMaster,
		] );
		$lbFactory = new FakeLBFactory( [ 'lb' => $loadBalancer ] );

		return new ReplicaMasterAwareRecordIdsAcquirer(
			new RepoDomainDb( $lbFactory, $lbFactory->getLocalDomainID() ),
			self::TABLE_NAME,
			self::ID_COLUMN,
			null,
			$flags
		);
	}

	private function getTestRecords() {
		return [
			[ 'column_value' => 'valueA1', 'column_id' => '1' ],
			[ 'column_value' => 'valueA2', 'column_id' => '2' ],
			[ 'column_value' => 'valueA3', 'column_id' => '3' ],
			[ 'column_value' => 'valueA4', 'column_id' => '4' ],
		];
	}

	private function getTestRecordsWithDuplicate() {
		return [
			[ 'column_value' => 'valueA1', 'column_id' => '1' ],
			[ 'column_value' => 'valueA2', 'column_id' => '2' ],
			[ 'column_value' => 'valueA3', 'column_id' => '3' ],
			[ 'column_value' => 'valueA3', 'column_id' => '3' ],
			[ 'column_value' => 'valueA4', 'column_id' => '4' ],
		];
	}

}
