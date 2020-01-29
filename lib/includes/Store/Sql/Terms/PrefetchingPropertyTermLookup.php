<?php

namespace Wikibase\Lib\Store\Sql\Terms;

use InvalidArgumentException;
use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\EntityTermLookupBase;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * A {@link PrefetchingTermLookup} that only supports properties,
 * using the new, normalized schema (starting at wbt_property_ids).
 *
 * @license GPL-2.0-or-later
 */
class PrefetchingPropertyTermLookup extends EntityTermLookupBase implements PrefetchingTermLookup {

	/** @var ILoadBalancer */
	private $loadBalancer;

	/** @var TermIdsResolver */
	private $termIdsResolver;

	/** @var IDatabase|null */
	private $dbr;

	/** @var bool|string */
	private $databaseDomain;

	/** @var array[] entity id serialization -> terms array */
	private $terms = [];

	/** @var bool[] entity ID, term type, language -> true for prefetched terms
	 * example "P1|label|en" -> true
	 */
	private $termKeys = [];

	/**
	 * PrefetchingPropertyTermLookup constructor.
	 * @param ILoadBalancer $loadBalancer
	 * @param TermIdsResolver $termIdsResolver
	 * @param bool|string $databaseDomain
	 */
	public function __construct(
		ILoadBalancer $loadBalancer,
		TermIdsResolver $termIdsResolver,
		$databaseDomain = false
	) {
		$this->loadBalancer = $loadBalancer;
		$this->termIdsResolver = $termIdsResolver;
		$this->databaseDomain = $databaseDomain;
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
			if ( !array_key_exists( $entityId->getSerialization(), $this->terms ) ) {
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
		$res = $this->getDbr()->select(
			'wbt_property_terms',
			[ 'wbpt_property_id', 'wbpt_term_in_lang_id' ],
			[ 'wbpt_property_id' => array_keys( $propertyIdsToFetch ) ],
			__METHOD__
		);
		/** @var int[] serialization -> term IDs */
		$groups = [];
		foreach ( $res as $row ) {
			$propertyId = $propertyIdsToFetch[$row->wbpt_property_id];
			$groups[$propertyId->getSerialization()][] = $row->wbpt_term_in_lang_id;
		}

		$result = $this->termIdsResolver->resolveGroupedTermIds( $groups, $termTypes, $languageCodes );
		$this->setKeys( $entityIds, $termTypes, $languageCodes );
		$this->terms = array_merge_recursive( $this->terms, $result );
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
		return $this->terms[$serialization][$termType][$languageCode][0] ?? false;
	}

	private function getDbr(): IDatabase {
		if ( $this->dbr === null ) {
			$this->dbr = $this->loadBalancer->getConnection( ILoadBalancer::DB_REPLICA, [], $this->databaseDomain );
		}
		return $this->dbr;
	}

	public function getPrefetchedAliases( EntityId $entityId, $languageCode ) {
		$serialization = $entityId->getSerialization();
		if ( !( $entityId instanceof PropertyId ) ) {
			throw new InvalidArgumentException( 'Not a PropertyId: ' . $serialization );
		}
		$key = $this->getKey( $entityId, 'alias', $languageCode );
		if ( !isset( $this->termKeys[$key] ) ) {
			return null;
		}
		return $this->terms[$serialization]['alias'][$languageCode] ?? false;
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
