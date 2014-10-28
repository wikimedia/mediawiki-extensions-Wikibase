<?php

namespace Wikibase\Lib\Store;

use OutOfBoundsException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\LanguageFallbackChain;
use Wikibase\TermIndex;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class LabelLookup {

	/**
	 * @var TermLookup
	 */
	private $termLookup;

	/**
	 * @var string
	 */
	private $languageCode;

	/**
	 * @param TermLookup $termLookup
	 * @param string $languageCode
	 */
	public function __construct( TermLookup $termLookup, $languageCode ) {
		$this->termLookup = $termLookup;
		$this->languageCode = $languageCode;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @throws OutOfBoundsException
	 * @return string
	 */
	public function getLabel( EntityId $entityId ) {
		$labels = $this->getLabels( $entityId );

		return $this->filterByLanguage( $labels, $this->languageCode );
	}

	/**
	 * @param EntityId $entityId
	 * @param LanguageFallbackChain $languageFallbackChain
	 *
	 * @return string|false
	 */
	public function getLabelForFallbackChain(
		EntityId $entityId,
		LanguageFallbackChain $languageFallbackChain
	) {
		$labels = $this->getLabels( $entityId );
		$extractedData = $languageFallbackChain->extractPreferredValue( $labels );

		return $extractedData ? $extractedData['value'] : false;
	}

	private function getLabels( EntityId $entityId ) {
		$wikibaseTerms = $this->termLookup->getTermsOfEntity( $entityId );
		return $this->convertTermsToLabelArray( $wikibaseTerms );
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
	 * @param Wikibase\Term[] $wikibaseTerms
	 *
	 * @return string[]
	 */
	private function convertTermsToLabelArray( array $wikibaseTerms ) {
		$terms = array();

		foreach( $wikibaseTerms as $wikibaseTerm ) {
			if ( $wikibaseTerm->getType() === 'label' ) {
				$languageCode = $wikibaseTerm->getLanguage();
				$terms[$languageCode] = $wikibaseTerm->getText();
			}
		}

		return $terms;
	}

}
