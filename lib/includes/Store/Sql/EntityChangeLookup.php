<?php

namespace Wikibase\Lib\Store\Sql;

use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\Changes\EntityChange;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikimedia\Assert\Assert;
use Wikimedia\Rdbms\IDatabase;

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

		$dbr = $this->db->connections()->getReadConnection();
		return $this->newEntityChangeSelectQueryBuilder( $dbr )
			->where( [ 'change_id' => $ids ] )
			->caller( __METHOD__ )
			->fetchChanges();
	}

	/**
	 * @param string $entityId
	 *
	 * @return EntityChange[]
	 */
	public function loadByEntityIdFromPrimary( string $entityId ): array {
		$dbw = $this->db->connections()->getWriteConnection();
		return $this->newEntityChangeSelectQueryBuilder( $dbw )
			->where( [ 'change_object_id' => $entityId ] )
			->caller( __METHOD__ )
			->fetchChanges();
	}

	/**
	 * @param string $thisTimeOrOlder maximum timestamp of changes to returns (TS_MW format)
	 * @param int $batchSize maximum number of changes to return
	 * @param int $offset skip this many changes
	 *
	 * @return EntityChange[]
	 */
	public function loadChangesBefore( string $thisTimeOrOlder, int $batchSize, int $offset ): array {
		$dbr = $this->db->connections()->getReadConnection();
		return $this->newEntityChangeSelectQueryBuilder( $dbr )
			->where( [ 'change_time <= ' . $dbr->addQuotes( $dbr->timestamp( $thisTimeOrOlder ) ) ] )
			->limit( $batchSize )
			->offset( $offset )
			->caller( __METHOD__ )
			->fetchChanges();
	}

	private function newEntityChangeSelectQueryBuilder( IDatabase $db ): EntityChangeSelectQueryBuilder {
		return new EntityChangeSelectQueryBuilder(
			$db,
			$this->entityIdParser,
			$this->entityChangeFactory
		);
	}

}
