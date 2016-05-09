<?php

namespace Wikibase\Lib\Store;

use DBAccessBase;
use ResultWrapper;
use Wikibase\ChunkAccess;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\EntityChange;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikimedia\Assert\Assert;

/**
 * Allows accessing changes stored in a database.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Marius Hoch
 */
class EntityChangeLookup extends DBAccessBase implements ChunkAccess {

	/**
	 * Flag to indicate that we need to query a master database.
	 */
	const FROM_MASTER = 'master';

	const FROM_SLAVE = 'slave';

	/**
	 * @var EntityChangeFactory
	 */
	private $entityChangeFactory;

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @param EntityChangeFactory $entityChangeFactory
	 * @param EntityIdParser $entityIdParser
	 * @param string|bool $wiki The target wiki's name. This must be an ID
	 * that LBFactory can understand.
	 */
	public function __construct(
		EntityChangeFactory $entityChangeFactory,
		EntityIdParser $entityIdParser,
		$wiki = false
	) {
		parent::__construct( $wiki );
		$this->entityChangeFactory = $entityChangeFactory;
		$this->entityIdParser = $entityIdParser;
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

		/* @var EntityChange $rec */
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
			array( 'change_id >= ' . (int)$start ),
			array(
				'ORDER BY' => 'change_id ASC',
				'LIMIT' => $size
			),
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
			array( 'change_id' => $ids ),
			array(),
			__METHOD__
		);
	}

	/**
	 * @param int $revisionId
	 * @param string $mode One of the self::FROM_... constants.
	 *
	 * @return EntityChange|null
	 */
	public function loadByRevisionId( $revisionId, $mode = self::FROM_SLAVE ) {
		Assert::parameterType( 'integer', $revisionId, '$revisionId' );

		$change = $this->loadChanges(
			array( 'change_revision_id' => $revisionId ),
			array(
				'LIMIT' => 1
			),
			__METHOD__,
			$mode === self::FROM_MASTER ? DB_MASTER : DB_SLAVE
		);

		if ( isset( $change[0] ) ) {
			return $change[0];
		} else {
			return null;
		}
	}

	/**
	 * @param array $where
	 * @param array $options
	 * @param string $method
	 * @param int $mode (DB_SLAVE or DB_MASTER)
	 *
	 * @return EntityChange[]
	 */
	private function loadChanges( array $where, array $options, $method, $mode = DB_SLAVE ) {
		$dbr = $this->getConnection( $mode );

		$rows = $dbr->select(
			'wb_changes',
			array(
				'change_id', 'change_type', 'change_time', 'change_object_id',
				'change_revision_id', 'change_user_id', 'change_info'
			),
			$where,
			$method,
			$options
		);

		return $this->changesFromRows( $rows );
	}

	private function changesFromRows( ResultWrapper $rows ) {
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
