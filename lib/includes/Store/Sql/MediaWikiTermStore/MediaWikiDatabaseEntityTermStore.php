<?php

namespace Wikibase\Lib\Store\Sql\MediaWikiTermStore;

use IDatabase;
use Wikimedia\Rdbms\ILoadBalancer;

class MediaWikiDatabaseEntityTermStore implements EntityTermStoreSchemaAccess {

	const PREFIX_TABLE = 'wbt_';
	const TABLE_PROPERTY_TERMS = 'property_terms';
	const TABLE_ITEM_TERMS = 'item_terms';

	/**
	 * @var NormalizedTermStoreSchemaAccess
	 */
	private $normalizedTermStoreAccess;

	/**
	 * @var Database $dbMaster
	 */
	private $dbMaster;

	/**
	 * @var Database $dbReplica
	 */
	private $dbReplica;

	public function __construct(
		NormalizedTermStoreSchemaAccess $normalizedTermStoreAccess,
		IDatabase $dbMaster,
		IDatabase $dbReplica
	) {
		$this->normalizedTermStoreAccess = $normalizedTermStoreAccess;
		$this->dbMaster = $dbMaster;
		$this->dbReplica = $dbReplica;
	}


	/**
	 * @inheritDoc
	 */
	public function setTerms( EntityId $entityId, array $termsArray ) {
	}

	/**
	 * @inheritDoc
	 */
	public function unsetTerms( EntityId $entityId ) {
	}
}
