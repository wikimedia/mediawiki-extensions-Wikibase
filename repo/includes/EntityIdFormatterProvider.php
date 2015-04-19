<?php

namespace Wikibase\Repo;

use Language;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikibase\Lib\Store\TermLookup;
use Wikibase\Store\TermBuffer;
use Wikibase\View\EntityIdFormatterFactory;

/**
 * Class to provide an EntityIdFormatter which does automatic prefetching
 * of labels, applies a language fallback and returns the correct formatter
 * for a given output format.
 *
 * @license GPL 2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class EntityIdFormatterProvider {

	/**
	 * @var LanguageFallbackChainFactory
	 */
	private $languageFallbackChainFactory;

	/**
	 * @var TermLookup
	 */
	private $termLookup;

	/**
	 * @var TermBuffer
	 */
	private $termBuffer;

	public function __construct(
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		TermLookup $termLookup,
		TermBuffer $termBuffer
	) {
		$this->languageFallbackChainFactory = $languageFallbackChainFactory;
		$this->termLookup = $termLookup;
		$this->termBuffer = $termBuffer;
	}

	/**
	 * Returns a formatter from the provided formatter factory for the given list
	 * of entity ids with a language fallback chain applied for the given language.
	 *
	 * @param Language $language
	 * @param EntityId[] $entityIds
	 * @param EntityIdFormatterFactory $formatterFactory
	 *
	 * @return EntityIdFormatter
	 */
	public function newFormatter( Language $language, array $entityIds, EntityIdFormatterFactory $formatterFactory ) {
		$languageFallbackChain = $this->languageFallbackChainFactory->newFromLanguage(
			$language,
			LanguageFallbackChainFactory::FALLBACK_SELF
				| LanguageFallbackChainFactory::FALLBACK_VARIANTS
				| LanguageFallbackChainFactory::FALLBACK_OTHERS
		);

		$languages = $languageFallbackChain->getFetchLanguageCodes();

		$labelDescriptionLookup = new LanguageFallbackLabelDescriptionLookup(
			$this->termLookup,
			$languageFallbackChain
		);

		$this->termBuffer->prefetchTerms( $entityIds, array( 'label' ), $languages );

		return $formatterFactory->getEntityIdFormater( $labelDescriptionLookup );
	}

}

