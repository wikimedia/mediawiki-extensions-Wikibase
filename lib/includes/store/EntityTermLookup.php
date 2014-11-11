<?php

namespace Wikibase\Lib\Store;

use OutOfBoundsException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\TermIndex;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class EntityTermLookup implements TermLookup {

	/**
	 * @var TermIndex
	 */
	private $termIndex;

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @param TermIndex $termIndex
	 * @param EntityLookup $entityLookup
	 */
	public function __construct( TermIndex $termIndex, EntityLookup $entityLookup ) {
		$this->termIndex = $termIndex;
		$this->entityLookup = $entityLookup;
	}

	/**
	 * @param EntityId $entityId
	 * @param string $languageCode
	 *
	 * @throws StorageException if entity does not exist
	 * @throws OutOfBoundsException
	 * @return string
	 */
	public function getLabel( EntityId $entityId, $languageCode ) {
		$labels = $this->getLabels( $entityId );
		return $this->filterByLanguage( $labels, $languageCode );
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @throws StorageException if entity does not exist
	 * @return string[]
	 */
	public function getLabels( EntityId $entityId ) {
		return $this->getTermsOfType( $entityId, 'label' );
	}

	/**
	 * @param EntityId $entityId
	 * @param string $languageCode
	 *
	 * @throws StorageException if entity does not exist
	 * @throws OutOfBoundsException
	 * @return string
	 */
	public function getDescription( EntityId $entityId, $languageCode ) {
		$descriptions = $this->getTermsOfType( $entityId, 'description' );
		return $this->filterByLanguage( $descriptions, $languageCode );
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @throws StorageException if entity does not exist
	 * @return string[]
	 */
	public function getDescriptions( EntityId $entityId ) {
		return $this->getTermsOfType( $entityId, 'description' );
	}

	/**
	 * @param EntityId $entityId
	 * @param string $termType
	 *
	 * @throws StorageException if entity does not exist.
	 * @return string[]
	 */
	private function getTermsOfType( EntityId $entityId, $termType ) {
		$wikibaseTerms = $this->termIndex->getTermsOfEntity( $entityId );

		if ( $wikibaseTerms === array() ) {
			if ( !$this->entityLookup->hasEntity( $entityId ) ) {
				throw new StorageException( 'Entity not found for '
					. $entityId->getSerialization() );
			}
		}

		return $this->convertTermsToTermTypeArray( $wikibaseTerms, $termType );
	}

	/**
	 * @param string[] $terms
	 * @param string $languageCode
	 *
	 * @throws OutOfBoundsException
	 * @return string
	 */
	private function filterByLanguage( array $terms, $languageCode ) {
		if ( array_key_exists( $languageCode, $terms ) ) {
			return $terms[$languageCode];
		}

		throw new OutOfBoundsException( 'Term not found for ' . $languageCode );
	}

	/**
	 * @param \Wikibase\Term[] $wikibaseTerms
	 * @param string $termType
	 *
	 * @return string[]
	 */
	private function convertTermsToTermTypeArray( array $wikibaseTerms, $termType ) {
		$terms = array();

		foreach( $wikibaseTerms as $wikibaseTerm ) {
			if ( $wikibaseTerm->getType() === $termType ) {
				$languageCode = $wikibaseTerm->getLanguage();
				$terms[$languageCode] = $wikibaseTerm->getText();
			}
		}

		return $terms;
	}

}
