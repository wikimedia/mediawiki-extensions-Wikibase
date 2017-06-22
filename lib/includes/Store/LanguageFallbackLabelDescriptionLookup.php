<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookupException;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Services\Lookup\TermLookupException;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\LanguageFallbackChain;

/**
 * @license GPL-2.0+
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
	 * @throws LabelDescriptionLookupException
	 * @return TermFallback|null
	 */
	public function getLabel( EntityId $entityId ) {
		$fetchLanguages = $this->languageFallbackChain->getFetchLanguageCodes();

		try {
			$labels = $this->termLookup->getLabels( $entityId, $fetchLanguages );
		} catch ( TermLookupException $ex ) {
			throw new LabelDescriptionLookupException( $entityId, $ex->getMessage(), $ex );
		}

		return $this->getTermFallback( $labels, $fetchLanguages );
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @throws LabelDescriptionLookupException
	 * @return TermFallback|null
	 */
	public function getDescription( EntityId $entityId ) {
		$fetchLanguages = $this->languageFallbackChain->getFetchLanguageCodes();

		try {
			$descriptions = $this->termLookup->getDescriptions( $entityId, $fetchLanguages );
		} catch ( TermLookupException $ex ) {
			throw new LabelDescriptionLookupException( $entityId, $ex->getMessage(), $ex );
		}

		return $this->getTermFallback( $descriptions, $fetchLanguages );
	}

	/**
	 * @param string[] $terms
	 * @param string[] $fetchLanguages
	 *
	 * @return TermFallback|null
	 */
	private function getTermFallback( array $terms, array $fetchLanguages ) {
		$extractedData = $this->languageFallbackChain->extractPreferredValue( $terms );

		if ( $extractedData === null ) {
			return null;
		}

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
