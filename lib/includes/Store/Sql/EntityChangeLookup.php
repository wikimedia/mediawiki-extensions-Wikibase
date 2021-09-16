<?php

namespace Wikibase\Lib\Store\Sql;

use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\Changes\EntityChange;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikibase\Lib\Store\ChunkAccess;
use Wikimedia\Assert\Assert;
use Wikimedia\Rdbms\IResultWrapper;

/**
 * Allows accessing changes stored in a database.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class EntityChangeLookup implements ChunkAccess {

	/**
	 * @var EntityChangeFactory
	 */
	private $entityChangeFactory;

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/** @var RepoDomainDb */
	private $db;

	/**
	 * @param EntityChangeFactory $entityChangeFactory
	 * @param EntityIdParser $entityIdParser
	 * @param RepoDomainDb $db
	 */
	public function __construct(
		EntityChangeFactory $entityChangeFactory,
		EntityIdParser $entityIdParser,
		RepoDomainDb $db
	) {
		$this->entityChangeFactory = $entityChangeFactory;
		$this->entityIdParser = $entityIdParser;
		$this->db = $db;
	}

	/**
	 * Returns the sequential ID of the given EntityChange.
	 *
	 * @param EntityChange $rec
	 *
	 * @return int
	 */
	public function getRecordId( $rec ) {
		Assert::parameterType( EntityChange::class, $rec, '$rec' );

		return $rec->getId();
	}

	/**
	 * @param int $start
	 * @param int $size
	 *
	 * @return EntityChange[]
	 */
	public function loadChunk( $start, $size ) {
		Assert::parameterType( 'integer', $start, '$start' );
		Assert::parameterType( 'integer', $size, '$size' );

		return $this->loadChanges(
			[ 'change_id >= ' . (int)$start ],
			[
				'ORDER BY' => 'change_id ASC',
				'LIMIT' => $size
			],
			__METHOD__
		);
	}

	/**
	 * @param int[] $ids
	 *
	 * @return EntityChange[]
	 */
	public function loadByChangeIds( array $ids ) {
		Assert::parameterElementType( 'integer', $ids, '$ids' );

		return $this->loadChanges(
			[ 'change_id' => $ids ],
			[],
			__METHOD__
		);
	}

	/**
	 * @param string $entityId
	 *
	 * @return EntityChange[]
	 */
	public function loadByEntityIdFromPrimary( string $entityId ): array {
		return $this->loadChanges( [ 'change_object_id' => $entityId ], [], __METHOD__, DB_PRIMARY );
	}

	/**
	 * @param array $where
	 * @param array $options
	 * @param string $method
	 * @param int $mode (DB_REPLICA or DB_PRIMARY)
	 *
	 * @return EntityChange[]
	 */
	private function loadChanges( array $where, array $options, $method, $mode = DB_REPLICA ) {
		if ( $mode === DB_REPLICA ) {
			$db = $this->db->connections()->getReadConnectionRef();
		} else {
			$db = $this->db->connections()->getWriteConnectionRef();
		}

		$rows = $db->select(
			'wb_changes',
			[
				'change_id', 'change_type', 'change_time', 'change_object_id',
				'change_revision_id', 'change_user_id', 'change_info'
			],
			$where,
			$method,
			$options
		);

		return $this->changesFromRows( $rows );
	}

	/**
	 * @param IResultWrapper $rows
	 *
	 * @return EntityChange[]
	 */
	private function changesFromRows( IResultWrapper $rows ) {
		$changes = [];

		foreach ( $rows as $row ) {
			$data = [
				'id' => (int)$row->change_id,
				'time' => $row->change_time,
				'info' => $row->change_info,
				'user_id' => $row->change_user_id,
				'revision_id' => $row->change_revision_id,
			];
			$entityId = $this->entityIdParser->parse( $row->change_object_id );
			$changes[] = $this->entityChangeFactory->newForChangeType( $row->change_type, $entityId, $data );
		}

		return $changes;
	}

}
