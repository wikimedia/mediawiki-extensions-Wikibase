<?php

namespace Wikibase\Lib\Store;

use OutOfBoundsException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Term;
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
	 * @param TermIndex $termIndex
	 */
	public function __construct( TermIndex $termIndex ) {
		$this->termIndex = $termIndex;
	}

	/**
	 * @see TermLookup::getLabel
	 *
	 * @param EntityId $entityId
	 * @param string $languageCode
	 *
	 * @throws OutOfBoundsException
	 * @return string
	 */
	public function getLabel( EntityId $entityId, $languageCode ) {
		$labels = $this->getLabels( $entityId );
		return $this->filterByLanguage( $labels, $languageCode );
	}

	/**
	 * @see TermLookup::getLabels
	 *
	 * @param EntityId $entityId
	 *
	 * @return string[]
	 */
	public function getLabels( EntityId $entityId ) {
		return $this->getTermsOfType( $entityId, 'label' );
	}

	/**
	 * @see TermLookup::getDescription
	 *
	 * @param EntityId $entityId
	 * @param string $languageCode
	 *
	 * @throws OutOfBoundsException
	 * @return string
	 */
	public function getDescription( EntityId $entityId, $languageCode ) {
		$descriptions = $this->getTermsOfType( $entityId, 'description' );
		return $this->filterByLanguage( $descriptions, $languageCode );
	}

	/**
	 * @see TermLookup::getDescriptions
	 *
	 * @param EntityId $entityId
	 *
	 * @return string[]
	 */
	public function getDescriptions( EntityId $entityId ) {
		return $this->getTermsOfType( $entityId, 'description' );
	}

	/**
	 * @param EntityId $entityId
	 * @param string $termType
	 *
	 * @throws OutOfBoundsException if entity does not exist.
	 * @return string[]
	 */
	private function getTermsOfType( EntityId $entityId, $termType ) {
		$wikibaseTerms = $this->termIndex->getTermsOfEntity( $entityId );

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
	 * @param Term[] $wikibaseTerms
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
