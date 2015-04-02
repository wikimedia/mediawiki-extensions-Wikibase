<?php

namespace Wikibase\Lib\Store;

use OutOfBoundsException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\LanguageFallbackChain;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 */
class LanguageFallbackLabelDescriptionLookup implements LabelDescriptionLookup {

	/**
	 * @var TermLookup
	 */
	private $termLookup;

	/**
	 * @var LanguageFallbackChain
	 */
	private $languageFallbackChain;

	/**
	 * @param TermLookup $termLookup
	 * @param LanguageFallbackChain $languageFallbackChain
	 */
	public function __construct(
		TermLookup $termLookup,
		LanguageFallbackChain $languageFallbackChain
	) {
		$this->termLookup = $termLookup;
		$this->languageFallbackChain = $languageFallbackChain;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @throws OutOfBoundsException
	 * @return Term
	 */
	public function getLabel( EntityId $entityId ) {
		$fetchLanguages = $this->languageFallbackChain->getFetchLanguageCodes();
		$labels = $this->termLookup->getLabels( $entityId, $fetchLanguages );
		$termFallback = $this->getTermFallback( $labels, $fetchLanguages );
		if ( $termFallback ) {
			return $termFallback;
		}

		throw new OutOfBoundsException( 'Label not found for fallback chain.' );
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @throws OutOfBoundsException
	 * @return Term
	 */
	public function getDescription( EntityId $entityId ) {
		$fetchLanguages = $this->languageFallbackChain->getFetchLanguageCodes();
		$descriptions = $this->termLookup->getDescriptions( $entityId, $fetchLanguages );
		$termFallback = $this->getTermFallback( $descriptions, $fetchLanguages );
		if ( $termFallback ) {
			return $termFallback;
		}

		throw new OutOfBoundsException( 'Description not found for fallback chain.' );
	}

	/**
	 * @param string[] $terms
	 * @param string[] $fetchLanguages
	 * @return TermFallback
	 */
	private function getTermFallback( array $terms, array $fetchLanguages ) {
		$extractedData = $this->languageFallbackChain->extractPreferredValue( $terms );

		if ( $extractedData ) {
			// $fetchLanguages are in order of preference
			$requestLanguage = reset( $fetchLanguages );

			// see extractPreferredValue for array keys
			return new TermFallback(
				$requestLanguage,
				$extractedData['value'],
				$extractedData['language'],
				$extractedData['source']
			);
		}
	}
}
