<?php

declare( strict_types = 1 );
namespace Wikibase\Lib\Store\Sql\Terms;

use InvalidArgumentException;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Int32EntityId;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Lib\Store\EntityTermLookupBase;
use Wikibase\Lib\Store\Sql\Terms\Util\StatsdMonitoring;

/**
 * Base class for a {@link PrefetchingTermLookup} that only supports a single entity type,
 * using the new, normalized schema (starting at wbt_item_terms/wbt_property_terms).
 *
 * Prefetches from DatabaseTermInLangIdsResolver(DB) and stores them in $terms (current process only).
 * Looks up terms from $terms.
 *
 * @license GPL-2.0-or-later
 */
abstract class PrefetchingEntityTermLookupBase extends EntityTermLookupBase implements PrefetchingTermLookup {

	use NormalizedTermStorageMappingTrait;
	use StatsdMonitoring;

	/** @var string a subclass of {@link Int32EntityId} */
	protected $entityIdClass;

	/** @var string usually the class name (without namespace) */
	protected $statsPrefix;

	/** @var DatabaseTermInLangIdsResolver */
	protected $termInLangIdsResolver;

	/** @var array[] entity numeric id -> terms array */
	private $terms = [];

	/** @var bool[] entity ID,  term type, language -> true for prefetched terms
	 * example "Q1|label|en" -> true
	 */
	private $termKeys = [];

	/**
	 * @param DatabaseTermInLangIdsResolver $termInlangIdsResolver
	 */
	public function __construct(
		DatabaseTermInLangIdsResolver $termInlangIdsResolver
	) {
		$this->termInLangIdsResolver = $termInlangIdsResolver;
	}

	public function prefetchTerms(
		array $entityIds,
		array $termTypes,
		array $languageCodes
	) {
		/** @var EntityId[] numeric ID -> EntityId */
		$idsToFetch = [];
		foreach ( $entityIds as $entityId ) {
			if ( !( $entityId instanceof $this->entityIdClass ) ) {
				throw new InvalidArgumentException(
					"Not an {$this->entityIdClass}: {$entityId->getSerialization()}" );
			}
			if ( isset( $idsToFetch[$entityId->getNumericId()] ) ) {
				continue;
			}
			if ( !array_key_exists( $entityId->getNumericId(), $this->terms ) ) {
				$idsToFetch[$entityId->getNumericId()] = $entityId;
				continue;
			}
			$isPrefetched = $this->isPrefetched( $entityId, $termTypes, $languageCodes );
			if ( !$isPrefetched ) {
				$idsToFetch[$entityId->getNumericId()] = $entityId;
			}
		}

		if ( $idsToFetch === [] ) {
			return;
		}

		$this->incrementForQuery( $this->statsPrefix . '_prefetchTerms' );

		// Fetch up to 20 (as suggested by the DBA) entities each time:
		// https://phabricator.wikimedia.org/T246159#5919892
		// Also deduplicating to reduce the network and sorting to utilize indexes
		ksort( $idsToFetch );
		$idBatches = array_chunk( array_unique( array_keys( $idsToFetch ) ), 20 );

		foreach ( $idBatches as $idBatch ) {
			$result = $this->resolveTerms( $idBatch, $termTypes, $languageCodes );
			$this->terms = array_replace_recursive( $this->terms, $result );
		}

		$this->setKeys( $entityIds, $termTypes, $languageCodes );
	}

	private function resolveTerms( array $ids, array $termTypes, array $languageCodes ): array {
		return $this->termInLangIdsResolver->resolveTermsViaJoin(
			$this->getMapping()->getTableName(),
			$this->getMapping()->getTermInLangIdColumn(),
			$this->getMapping()->getEntityIdColumn(),
			[ $this->getMapping()->getEntityIdColumn() => $ids ],
			$termTypes,
			$languageCodes
		);
	}

	public function getPrefetchedTerm( EntityId $entityId, $termType, $languageCode ) {
		if ( !( $entityId instanceof $this->entityIdClass ) ) {
			throw new InvalidArgumentException( "Not an {$this->entityIdClass}: " . $entityId->getSerialization() );
		}

		$key = $this->getKey( $entityId, $termType, $languageCode );
		if ( !isset( $this->termKeys[$key] ) ) {
			return null;
		}

		return $this->getFromBuffer( $entityId, $termType, $languageCode )[0] ?? false;
	}

	public function getPrefetchedAliases( EntityId $entityId, $languageCode ) {
		if ( !( $entityId instanceof $this->entityIdClass ) ) {
			throw new InvalidArgumentException( "Not a {$this->entityIdClass}: " . $entityId->getSerialization() );
		}

		$key = $this->getKey( $entityId, TermTypes::TYPE_ALIAS, $languageCode );
		if ( !isset( $this->termKeys[$key] ) ) {
			return null;
		}

		return $this->getFromBuffer( $entityId, TermTypes::TYPE_ALIAS, $languageCode ) ?? false;
	}

	private function getFromBuffer( EntityId $entityId, $termType, $languageCode ) {
		if ( !( $entityId instanceof Int32EntityId ) ) {
			throw new InvalidArgumentException( "Not an Int32EntityId: " . $entityId->getSerialization() );
		}
		return $this->terms[$entityId->getNumericId()][$termType][$languageCode] ?? null;
	}

	protected function getTermsOfType( EntityId $entityId, $termType, array $languageCodes ) {
		$this->prefetchTerms( [ $entityId ], [ $termType ], $languageCodes );

		$ret = [];
		foreach ( $languageCodes as $languageCode ) {
			$term = $this->getPrefetchedTerm( $entityId, $termType, $languageCode );
			if ( $term !== false ) {
				$ret[$languageCode] = $term;
			}
		}
		return $ret;
	}

	private function getKey(
		EntityId $entityId,
		string $termType,
		string $languageCode
	): string {
		return $this->getKeyString( $entityId->getSerialization(), $termType, $languageCode );
	}

	private function getKeyString(
		string $entityId,
		string $termType,
		string $languageCode
	): string {
		return $entityId . '|' . $termType . '|' . $languageCode;
	}

	private function setKeys( array $entityIds, array $termTypes, array $languageCodes ): void {
		foreach ( $entityIds as $entityId ) {
			foreach ( $termTypes as $termType ) {
				foreach ( $languageCodes as $languageCode ) {
					$key = $this->getKey( $entityId, $termType, $languageCode );
					$this->termKeys[$key] = true;
				}
			}
		}
	}

	private function isPrefetched(
		EntityId $entityId,
		array $termTypes,
		array $languageCodes
	): bool {
		foreach ( $termTypes as $termType ) {
			foreach ( $languageCodes as $languageCode ) {
				$key = $this->getKey( $entityId, $termType, $languageCode );
				if ( !isset( $this->termKeys[$key] ) ) {
					return false;
				}
			}
		}
		return true;
	}

}
