<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\FederatedProperties;

use BadMethodCallException;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Lib\Store\EntityTermLookupBase;

/**
 * A {@link PrefetchingTermLookup} for federated properties
 *
 * Prefetches from GenericActionApiClient(API) and stores them in $terms (current process only).
 * Looks up terms from $terms.
 *
 * @license GPL-2.0-or-later
 */
class ApiPrefetchingTermLookup extends EntityTermLookupBase implements PrefetchingTermLookup {

	/**
	 * @var array[] entity numeric id -> terms array
	 */
	private $terms = [];

	/**
	 * @var bool[] entity ID, term type, language -> true for prefetched terms
	 * example: [ "P1|label|en" => true ]
	 */
	private $termKeys = [];

	/**
	 * @var ApiEntityLookup
	 */
	private $apiEntityLookup;

	private const SUPPORTED_TERM_TYPES = [ TermTypes::TYPE_LABEL, TermTypes::TYPE_DESCRIPTION ];

	/**
	 * @param ApiEntityLookup $apiEntityLookup
	 */
	public function __construct( ApiEntityLookup $apiEntityLookup ) {
		$this->apiEntityLookup = $apiEntityLookup;
	}

	/**
	 * @param EntityId $entityId
	 * @param string $termType
	 * @param array $languageCodes
	 * @return array|string[]
	 */
	protected function getTermsOfType( EntityId $entityId, $termType, array $languageCodes ): array {
		$this->prefetchTerms( [ $entityId ], [ $termType ], $languageCodes );

		$result = [];
		foreach ( $languageCodes as $languageCode ) {
			$term = $this->getPrefetchedTerm( $entityId, $termType, $languageCode );
			if ( $term !== false ) {
				$result[$languageCode] = $term;
			}
		}
		return $result;
	}

	/**
	 * Loads a set of terms into the buffer.
	 * The source from which to fetch would typically be supplied to the buffer's constructor.
	 *
	 * @param EntityId[] $entityIds
	 * @param string[] $termTypes The desired term types.
	 * @param string[] $languageCodes The desired languages.
	 */
	public function prefetchTerms( array $entityIds, array $termTypes, array $languageCodes ): void {
		$this->validateTermTypes( $termTypes );

		$entityIdsToFetch = $this->getEntityIdsToFetch( $entityIds, $termTypes, $languageCodes );

		if ( empty( $entityIdsToFetch ) ) {
			return;
		}

		$this->apiEntityLookup->fetchEntities( $entityIdsToFetch );
		foreach ( $entityIdsToFetch as $entityId ) {
			$this->terms[ $entityId->getSerialization() ] = array_replace_recursive(
				$this->terms, $this->apiEntityLookup->getResultPartForId( $entityId )
			);
		}
		$this->setKeys( $entityIds, $termTypes, $languageCodes );
	}

	private function getEntityIdsToFetch( array $entityIds, array $termTypes, array $languageCodes ): array {
		/** @var EntityId[] serialization -> EntityId */
		$entityIdsToFetch = [];

		foreach ( $entityIds as $entityId ) {
			if ( isset( $entityIdsToFetch[$entityId->getSerialization()] ) ) {
				continue;
			}

			if ( !array_key_exists( $entityId->getSerialization(), $this->terms ) ) {
				$entityIdsToFetch[$entityId->getSerialization()] = $entityId;
				continue;
			}

			$isPrefetched = $this->isPrefetched( $entityId, $termTypes, $languageCodes );
			if ( !$isPrefetched ) {
				$entityIdsToFetch[$entityId->getSerialization()] = $entityId;
			}
		}
		return $entityIdsToFetch;
	}

	/**
	 * Returns a term that was previously loaded by prefetchTerms.
	 *
	 * @param EntityId $entityId
	 * @param string $termType
	 * @param string $languageCode
	 *
	 * @return string|false|null The term, or false of that term is known to not exist, or null if the term was not prefetched.
	 */
	public function getPrefetchedTerm( EntityId $entityId, $termType, $languageCode ) {
		$key = $this->getKey( $entityId, $termType, $languageCode );
		if ( !isset( $this->termKeys[$key] ) ) {
			return null;
		}
		$termType = implode( "|", $this->translateTermTypesToApiProps( [ $termType ] ) );

		// return false if entityId has been covered by prefetchTerms but term does not exist
		return $this->terms[$entityId->getSerialization()][$termType][$languageCode]['value'] ?? false;
	}

	private function getKey( EntityId $entityId, string $termType, string $languageCode ): string {
		return $this->getKeyString( $entityId->getSerialization(), $termType, $languageCode );
	}

	private function getKeyString( string $entityId, string $termType, string $languageCode ): string {
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

	private function isPrefetched( EntityId $entityId, array $termTypes, array $languageCodes ): bool {
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

	private function translateTermTypesToApiProps( array $termTypes ): array {
		$termTypeMapping = [
			TermTypes::TYPE_ALIAS => 'aliases',
			TermTypes::TYPE_DESCRIPTION => 'descriptions',
			TermTypes::TYPE_LABEL => 'labels',
		];

		$translation = [];
		foreach ( $termTypes as $termType ) {
			array_push( $translation, $termTypeMapping[ $termType ] );
		}

		return $translation;
	}

	private function validateTermTypes( array $termTypes ): void {
		foreach ( $termTypes as $termType ) {
			if ( !in_array( $termType, self::SUPPORTED_TERM_TYPES ) ) {
				throw new BadMethodCallException( "term type $termType is not supported by " . __CLASS__ );
			}
		}
	}

	/**
	 * Not implemented
	 * @throws BadMethodCallException always
	 */
	public function getPrefetchedAliases( EntityId $entityId, $languageCode ) {
		throw new BadMethodCallException( 'Cannot get Aliases. Only labels' );
	}
}
