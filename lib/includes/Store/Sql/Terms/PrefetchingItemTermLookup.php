<?php

declare( strict_types = 1 );
namespace Wikibase\Lib\Store\Sql\Terms;

use InvalidArgumentException;
use MediaWiki\MediaWikiServices;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Lib\Store\EntityTermLookupBase;

/**
 * A {@link PrefetchingTermLookup} that only supports items,
 * using the new, normalized schema (starting at wbt_item_ids).
 *
 * Prefetches from DatabaseTermInLangIdsResolver(DB) and stores them in $terms (current process only).
 * Looks up terms from $terms.
 *
 * Very similar if not basically the same as {@link PrefetchingPropertyTermLookup}
 *
 * @license GPL-2.0-or-later
 */
class PrefetchingItemTermLookup extends EntityTermLookupBase implements PrefetchingTermLookup {

	/** @var DatabaseTermInLangIdsResolver */
	private $termInLangIdsResolver;

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

	public function prefetchTerms(
		array $entityIds,
		array $termTypes,
		array $languageCodes
	) {
		/** @var ItemId[] numeric ID -> ItemId */
		$itemIdsToFetch = [];
		foreach ( $entityIds as $entityId ) {
			if ( !( $entityId instanceof ItemId ) ) {
				throw new InvalidArgumentException(
					'Not an ItemId: ' . $entityId->getSerialization() );
			}
			if ( isset( $itemIdsToFetch[$entityId->getNumericId()] ) ) {
				continue;
			}
			if ( !array_key_exists( $entityId->getNumericId(), $this->terms ) ) {
				$itemIdsToFetch[$entityId->getNumericId()] = $entityId;
				continue;
			}
			$isPrefetched = $this->isPrefetched( $entityId, $termTypes, $languageCodes );
			if ( !$isPrefetched ) {
				$itemIdsToFetch[$entityId->getNumericId()] = $entityId;
			}
		}

		if ( $itemIdsToFetch === [] ) {
			return;
		}

		MediaWikiServices::getInstance()->getStatsdDataFactory()->increment(
			'wikibase.repo.term_store.PrefetchingItemTermLookup_prefetchTerms'
		);

		// Fetch up to 20 (as suggested by the DBA) entities each time:
		// https://phabricator.wikimedia.org/T246159#5919892
		// Also deduplicating to reduce the network and sorting to utilize indexes
		ksort( $itemIdsToFetch );
		$itemIdBatches = array_chunk( array_unique( array_keys( $itemIdsToFetch ) ), 20 );

		foreach ( $itemIdBatches as $itemIdBatch ) {
			$result = $this->termInLangIdsResolver->resolveTermsViaJoin(
				'wbt_item_terms',
				'wbit_term_in_lang_id',
				'wbit_item_id',
				[ 'wbit_item_id' => $itemIdBatch ],
				$termTypes,
				$languageCodes
			);
			$this->terms = array_replace_recursive( $this->terms, $result );
		}

		$this->setKeys( $entityIds, $termTypes, $languageCodes );
	}

	public function getPrefetchedTerm( EntityId $entityId, $termType, $languageCode ) {
		$serialization = $entityId->getSerialization();
		if ( !( $entityId instanceof ItemId ) ) {
			throw new InvalidArgumentException( 'Not an ItemId: ' . $serialization );
		}
		$key = $this->getKey( $entityId, $termType, $languageCode );
		if ( !isset( $this->termKeys[$key] ) ) {
			return null;
		}
		return $this->terms[$entityId->getNumericId()][$termType][$languageCode][0] ?? false;
	}

	public function getPrefetchedAliases( EntityId $entityId, $languageCode ) {
		$serialization = $entityId->getSerialization();
		if ( !( $entityId instanceof ItemId ) ) {
			throw new InvalidArgumentException( 'Not an ItemId: ' . $serialization );
		}
		$key = $this->getKey( $entityId, TermTypes::TYPE_ALIAS, $languageCode );
		if ( !isset( $this->termKeys[$key] ) ) {
			return null;
		}
		return $this->terms[$entityId->getNumericId()][TermTypes::TYPE_ALIAS][$languageCode] ?? false;
	}

	private function getKey(
		ItemId $entityId,
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
		ItemId $entityId,
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
