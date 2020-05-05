<?php

declare( strict_types = 1 );
namespace Wikibase\Lib\Store\Sql\Terms;

use InvalidArgumentException;
use MediaWiki\MediaWikiServices;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Lib\Store\EntityTermLookupBase;

/**
 * A {@link PrefetchingTermLookup} that only supports properties,
 * using the new, normalized schema (starting at wbt_property_ids).
 *
 * Prefetches from DatabaseTermInLangIdsResolver(DB) and stores them in $terms (current process only).
 * Looks up terms from $terms.
 *
 * Very similar if not basically the same as {@link PrefetchingItemTermLookup}
 *
 * @license GPL-2.0-or-later
 */
class PrefetchingPropertyTermLookup extends EntityTermLookupBase implements PrefetchingTermLookup {

	/** @var DatabaseTermInLangIdsResolver */
	private $termInLangIdsResolver;

	/** @var array[] entity numeric id -> terms array */
	private $terms = [];

	/** @var bool[] entity ID, term type, language -> true for prefetched terms
	 * example "P1|label|en" -> true
	 */
	private $termKeys = [];

	/**
	 * @param DatabaseTermInLangIdsResolver $termInLangIdsResolver
	 */
	public function __construct(
		DatabaseTermInLangIdsResolver $termInLangIdsResolver
	) {
		$this->termInLangIdsResolver = $termInLangIdsResolver;
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

	public function prefetchTerms( array $entityIds, array $termTypes, array $languageCodes ) {
		/** @var PropertyId[] numeric ID -> PropertyId */
		$propertyIdsToFetch = [];
		foreach ( $entityIds as $entityId ) {
			if ( !( $entityId instanceof PropertyId ) ) {
				throw new InvalidArgumentException(
					'Not a PropertyId: ' . $entityId->getSerialization() );
			}
			if ( isset( $propertyIdsToFetch[$entityId->getNumericId()] ) ) {
				continue;
			}
			if ( !array_key_exists( $entityId->getNumericId(), $this->terms ) ) {
				$propertyIdsToFetch[$entityId->getNumericId()] = $entityId;
				continue;
			}
			$isPrefetched = $this->isPrefetched( $entityId, $termTypes, $languageCodes );
			if ( !$isPrefetched ) {
				$propertyIdsToFetch[$entityId->getNumericId()] = $entityId;
			}
		}

		if ( $propertyIdsToFetch === [] ) {
			return;
		}

		MediaWikiServices::getInstance()->getStatsdDataFactory()->increment(
			'wikibase.repo.term_store.PrefetchingPropertyTermLookup_prefetchTerms'
		);

		// Fetch up to 20 (as suggested by the DBA) entities each time:
		// https://phabricator.wikimedia.org/T246159#5919892
		// Also deduplicating to reduce the network and sorting to utilize indexes
		ksort( $propertyIdsToFetch );
		$propertyIdBatches = array_chunk( array_unique( array_keys( $propertyIdsToFetch ) ), 20 );

		foreach ( $propertyIdBatches as $propertyIdBatch ) {
			$result = $this->termInLangIdsResolver->resolveTermsViaJoin(
				'wbt_property_terms',
				'wbpt_term_in_lang_id',
				'wbpt_property_id',
				[ 'wbpt_property_id' => $propertyIdBatch ],
				$termTypes,
				$languageCodes
			);
			$this->terms = array_replace_recursive( $this->terms, $result );
		}
		$this->setKeys( $entityIds, $termTypes, $languageCodes );
	}

	public function getPrefetchedTerm( EntityId $entityId, $termType, $languageCode ) {
		$serialization = $entityId->getSerialization();
		if ( !( $entityId instanceof PropertyId ) ) {
			throw new InvalidArgumentException( 'Not a PropertyId: ' . $serialization );
		}
		$key = $this->getKey( $entityId, $termType, $languageCode );
		if ( !isset( $this->termKeys[$key] ) ) {
			return null;
		}
		return $this->terms[$entityId->getNumericId()][$termType][$languageCode][0] ?? false;
	}

	public function getPrefetchedAliases( EntityId $entityId, $languageCode ) {
		$serialization = $entityId->getSerialization();
		if ( !( $entityId instanceof PropertyId ) ) {
			throw new InvalidArgumentException( 'Not a PropertyId: ' . $serialization );
		}
		$key = $this->getKey( $entityId, TermTypes::TYPE_ALIAS, $languageCode );
		if ( !isset( $this->termKeys[$key] ) ) {
			return null;
		}
		return $this->terms[$entityId->getNumericId()][TermTypes::TYPE_ALIAS][$languageCode] ?? false;
	}

	private function getKey(
		PropertyId $entityId,
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
		PropertyId $entityId,
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
