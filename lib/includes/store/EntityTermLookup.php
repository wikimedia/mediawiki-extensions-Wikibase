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
 * @author Daniel Kinzler
 */
class EntityTermLookup implements TermLookup, TermBuffer {

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
	 * @throws OutOfBoundsException if no such label was found
	 * @return string
	 */
	public function getLabel( EntityId $entityId, $languageCode ) {
		$labels = $this->getLabels( $entityId, array( $languageCode ) );

		if ( !isset( $labels[$languageCode] ) ) {
			throw new OutOfBoundsException( 'No label found for language ' . $languageCode );
		}

		return $labels[$languageCode];
	}

	/**
	 * @see TermLookup::getLabels
	 *
	 * @param EntityId $entityId
	 * @param string[]|null $languages
	 *
	 * @return string[]
	 */
	public function getLabels( EntityId $entityId, array $languages = null ) {
		return $this->getTermsOfType( $entityId, 'label', $languages );
	}

	/**
	 * @see TermLookup::getDescription
	 *
	 * @param EntityId $entityId
	 * @param string $languageCode
	 *
	 * @throws OutOfBoundsException if no such label was found
	 * @return string
	 */
	public function getDescription( EntityId $entityId, $languageCode ) {
		$descriptions = $this->getDescriptions( $entityId, array( $languageCode ) );

		if ( !isset( $descriptions[$languageCode] ) ) {
			throw new OutOfBoundsException( 'No description found for language ' . $languageCode );
		}

		return $descriptions[$languageCode];
	}

	/**
	 * @see TermLookup::getDescriptions
	 *
	 * @param EntityId $entityId
	 * @param string[]|null $languages
	 *
	 * @return string[]
	 */
	public function getDescriptions( EntityId $entityId, array $languages = null ) {
		return $this->getTermsOfType( $entityId, 'description', $languages );
	}

	/**
	 * @param EntityId $entityId
	 * @param string $termType
	 * @param string[]|null $languages
	 *
	 * @throws OutOfBoundsException if entity does not exist.
	 * @return string[]
	 */
	private function getTermsOfType( EntityId $entityId, $termType, array $languages = null ) {
		$wikibaseTerms = $this->termIndex->getTermsOfEntity( $entityId, array( $termType ), $languages );

		return $this->convertTermsToMap( $wikibaseTerms, $termType );
	}

	/**
	 * @param Term[] $wikibaseTerms
	 *
	 * @return string[]
	 */
	private function convertTermsToMap( array $wikibaseTerms ) {
		$terms = array();

		foreach( $wikibaseTerms as $wikibaseTerm ) {
			$languageCode = $wikibaseTerm->getLanguage();
			$terms[$languageCode] = $wikibaseTerm->getText();
		}

		return $terms;
	}

}
