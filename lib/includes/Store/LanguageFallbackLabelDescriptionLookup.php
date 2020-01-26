<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookupException;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Services\Lookup\TermLookupException;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\LanguageFallbackChain;

/**
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 */
class LanguageFallbackLabelDescriptionLookup implements FallbackLabelDescriptionLookup {

	/**
	 * @var TermLookup
	 */
	private $termLookup;

	/**
	 * @var LanguageFallbackChain|callable
	 */
	private $languageFallbackChain;

	/**
	 * @param TermLookup $termLookup
	 * @param LanguageFallbackChain|callable $languageFallbackChain
	 */
	public function __construct(
		TermLookup $termLookup,
		$languageFallbackChain
	) {
		if (
			!$languageFallbackChain instanceof LanguageFallbackChain &&
			!is_callable( $languageFallbackChain )
		) {
			throw new \InvalidArgumentException( 'Should be callable of LanguageFallbackChain' );
		}
		$this->termLookup = $termLookup;
		$this->languageFallbackChain = $languageFallbackChain;
	}

	private function getLanguageFallbackChain() {
		if ( !$this->languageFallbackChain instanceof LanguageFallbackChain ) {
			$this->languageFallbackChain = call_user_func( $this->languageFallbackChain );
		}
		return $this->languageFallbackChain;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @throws LabelDescriptionLookupException
	 * @return TermFallback|null
	 */
	public function getLabel( EntityId $entityId ) {
		$fetchLanguages = $this->getLanguageFallbackChain()->getFetchLanguageCodes();

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
		$fetchLanguages = $this->getLanguageFallbackChain()->getFetchLanguageCodes();

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
		$extractedData = $this->getLanguageFallbackChain()->extractPreferredValue( $terms );

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
