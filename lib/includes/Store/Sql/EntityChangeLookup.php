<?php

namespace Wikibase\Lib\Store\Sql;

use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\Changes\EntityChange;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikimedia\Assert\Assert;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\IResultWrapper;
use Wikimedia\Timestamp\ConvertibleTimestamp;

/**
 * Allows accessing changes stored in a database.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class EntityChangeLookup {

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
	 * @param int[] $ids
	 *
	 * @return EntityChange[]
	 */
	public function loadByChangeIds( array $ids ) {
		Assert::parameterElementType( 'integer', $ids, '$ids' );

		return $this->loadChanges(
			[ 'change_id' => $ids ],
			[],
			__METHOD__,
			$this->db->connections()->getReadConnectionRef()
		);
	}

	/**
	 * @param string $entityId
	 *
	 * @return EntityChange[]
	 */
	public function loadByEntityIdFromPrimary( string $entityId ): array {
		return $this->loadChanges(
			[ 'change_object_id' => $entityId ],
			[],
			__METHOD__,
			$this->db->connections()->getWriteConnectionRef()
		);
	}

	/**
	 * @param string $thisTimeOrOlder maximum timestamp of changes to returns (TS_MW format)
	 * @param int $batchSize maximum number of changes to return
	 * @param int $offset skip this many changes
	 *
	 * @return EntityChange[]
	 */
	public function loadChangesBefore( string $thisTimeOrOlder, int $batchSize, int $offset ): array {
		$dbr = $this->db->connections()->getReadConnectionRef();
		return $this->loadChanges(
			[ 'change_time <= ' . $dbr->addQuotes( $dbr->timestamp( $thisTimeOrOlder ) ) ],
			[ 'LIMIT' => $batchSize, 'OFFSET' => $offset ],
			__METHOD__,
			$dbr
		);
	}

	/**
	 * @param array $where
	 * @param array $options
	 * @param string $method
	 * @param IDatabase $dbr
	 *
	 * @return EntityChange[]
	 */
	private function loadChanges( array $where, array $options, $method, IDatabase $dbr ) {
		$rows = $dbr->select(
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
				'time' => ConvertibleTimestamp::convert( TS_MW, $row->change_time ),
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
