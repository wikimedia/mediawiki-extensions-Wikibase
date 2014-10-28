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
	 * @param TermIndex $termIndex
	 */
	public function __construct( TermIndex $termIndex ) {
		$this->termIndex = $termIndex;
	}

	/**
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
	 * @param EntityId $entityId
	 *
	 * @return string[]
	 */
	public function getLabels( EntityId $entityId ) {
		return $this->getTermsOfType( $entityId, 'label' );
	}

	/**
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
	 * @return string[]
	 */
	private function getTermsOfType( EntityId $entityId, $termType ) {
		$wikibaseTerms = $this->termIndex->getTermsOfEntity( $entityId );
		return $this->convertTermsToTermTypeArray( $wikibaseTerms, $termType );
	}

	/**
	 * @param string[] $labels
	 * @param string $languageCode
	 *
	 * @throws OutOfBoundsException
	 * @return string
	 */
	private function filterByLanguage( array $labels, $languageCode ) {
		if ( array_key_exists( $languageCode, $labels ) ) {
			return $labels[$languageCode];
		}

		throw new OutOfBoundsException( 'Label not found for ' . $languageCode );
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
