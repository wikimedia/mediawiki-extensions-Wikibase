<?php

namespace Wikibase\Lib\Store\Sql\Terms;

use MediaWiki\Storage\NameTableAccessException;
use MediaWiki\Storage\NameTableStore;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use WANObjectCache;
use Wikibase\Lib\Rdbms\RepoDomainDb;

/**
 * An acquirer and resolver for term type IDs implemented using a NameTableStore for wbt_type.
 *
 * @see @ref docs_storage_terms
 * @license GPL-2.0-or-later
 */
class DatabaseTypeIdsStore implements TypeIdsAcquirer, TypeIdsResolver, TypeIdsLookup {

	/** @var NameTableStore */
	private $nameTableStore;

	public function __construct(
		RepoDomainDb $db,
		WANObjectCache $cache,
		LoggerInterface $logger = null
	) {
		$this->nameTableStore = new NameTableStore(
			$db->loadBalancer(),
			$cache,
			$logger ?: new NullLogger(),
			'wbt_type',
			'wby_id',
			'wby_name',
			null,
			$db->domain()
		);
	}

	public function acquireTypeIds( array $types ): array {
		$typeIds = [];
		foreach ( $types as $typeName ) {
			$typeIds[$typeName] = $this->nameTableStore->acquireId( $typeName );
		}
		return $typeIds;
	}

	public function resolveTypeIds( array $typeIds ): array {
		$typeNames = [];
		foreach ( $typeIds as $typeId ) {
			$typeNames[$typeId] = $this->nameTableStore->getName( $typeId );
		}
		return $typeNames;
	}

	/**
	 * {@inheritdoc}
	 * Unknown types will be associated with null in the value
	 */
	public function lookupTypeIds( array $types ): array {
		$typeIds = [];

		foreach ( $types as $type ) {
			try {
				$typeIds[$type] = $this->nameTableStore->getId( $type );
			} catch ( NameTableAccessException $ex ) {
				$typeIds[$type] = null;
			}
		}

		return $typeIds;
	}

}
