<?php

namespace Wikibase\Client\DataAccess;

use InvalidArgumentException;
use Language;
use ValueFormatters\FormatterOptions;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\Client\Usage\UsageTrackingLanguageFallbackLabelDescriptionLookup;
use Wikibase\Client\Usage\UsageTrackingSnakFormatter;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Formatters\BinaryOptionDispatchingSnakFormatter;
use Wikibase\Lib\EscapingSnakFormatter;
use Wikibase\Lib\FormatterLabelDescriptionLookupFactory;
use Wikibase\Lib\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;

/**
 * A factory for SnakFormatters in a client context, to be reused in different methods that "access
 * repository data" from a client (typically parser functions and Lua scripts).
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Marius Hoch
 */
class DataAccessSnakFormatterFactory {

	/**
	 * @var LanguageFallbackChainFactory
	 */
	private $languageFallbackChainFactory;

	/**
	 * @var OutputFormatSnakFormatterFactory
	 */
	private $snakFormatterFactory;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $propertyDataTypeLookup;

	/**
	 * @var EntityIdParser
	 */
	private $repoItemUriParser;

	/**
	 * @var LanguageFallbackLabelDescriptionLookupFactory
	 */
	private $languageFallbackLabelDescriptionLookupFactory;

	/**
	 * @var bool
	 */
	private $trackUsagesInAllLanguages;

	public function __construct(
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		OutputFormatSnakFormatterFactory $snakFormatterFactory,
		PropertyDataTypeLookup $propertyDataTypeLookup,
		EntityIdParser $repoItemUriParser,
		LanguageFallbackLabelDescriptionLookupFactory $languageFallbackLabelDescriptionLookupFactory,
		$trackUsagesInAllLanguages = false
	) {
		if ( !is_bool( $trackUsagesInAllLanguages ) ) {
			throw new InvalidArgumentException( '$trackUsagesInAllLanguages must be a bool' );
		}

		$this->languageFallbackChainFactory = $languageFallbackChainFactory;
		$this->snakFormatterFactory = $snakFormatterFactory;
		$this->propertyDataTypeLookup = $propertyDataTypeLookup;
		$this->repoItemUriParser = $repoItemUriParser;
		$this->languageFallbackLabelDescriptionLookupFactory = $languageFallbackLabelDescriptionLookupFactory;
		$this->trackUsagesInAllLanguages = $trackUsagesInAllLanguages;
	}

	/**
	 * This returns a SnakFormatter that will return either "rich" wikitext, or wikitext escaped
	 * plain text. The only exception are URLs, these are not escaped but plain text.
	 *
	 * @param Language $language
	 * @param UsageAccumulator $usageAccumulator
	 * @param string $type Either "escaped-plaintext" or "rich-wikitext".
	 *
	 * @return SnakFormatter
	 */
	public function newWikitextSnakFormatter(
		Language $language,
		UsageAccumulator $usageAccumulator,
		$type = 'escaped-plaintext'
	) {
		$fallbackChain = $this->languageFallbackChainFactory->newFromLanguage(
			$language,
			LanguageFallbackChainFactory::FALLBACK_ALL
		);

		$options = new FormatterOptions( [
			FormatterLabelDescriptionLookupFactory::OPT_LANGUAGE_FALLBACK_CHAIN => $fallbackChain,
			SnakFormatter::OPT_LANG => $language->getCode(),
		] );

		if ( $type === 'rich-wikitext' ) {
			$snakFormatter = $this->getRichWikitextSnakFormatterForOptions( $options );
		} else {
			$snakFormatter = $this->getPlainTextSnakFormatterForOptions( $options );
		}

		return new UsageTrackingSnakFormatter(
			$snakFormatter,
			$usageAccumulator,
			$this->repoItemUriParser,
			new UsageTrackingLanguageFallbackLabelDescriptionLookup(
				$this->languageFallbackLabelDescriptionLookupFactory->newLabelDescriptionLookup( $language ),
				$usageAccumulator,
				$fallbackChain,
				$this->trackUsagesInAllLanguages
			)
		);
	}

	/**
	 * @param FormatterOptions $options
	 * @return BinaryOptionDispatchingSnakFormatter
	 */
	private function getRichWikitextSnakFormatterForOptions( FormatterOptions $options ) {
		$snakFormatter = $this->snakFormatterFactory->getSnakFormatter(
			SnakFormatter::FORMAT_WIKI,
			$options
		);

		return new EscapingSnakFormatter(
			SnakFormatter::FORMAT_WIKI,
			$snakFormatter,
			function( $str ) {
				return $str === '' ? '' : "<span>$str</span>";
			}
		);
	}

	/**
	 * Our output format is basically wikitext escaped plain text, except
	 * for URLs, these are not wikitext escaped.
	 *
	 * @param FormatterOptions $options
	 * @return BinaryOptionDispatchingSnakFormatter
	 */
	private function getPlainTextSnakFormatterForOptions( FormatterOptions $options ) {
		$plainTextSnakFormatter = $this->snakFormatterFactory->getSnakFormatter(
			SnakFormatter::FORMAT_PLAIN,
			$options
		);

		$escapingSnakFormatter = new EscapingSnakFormatter(
			SnakFormatter::FORMAT_PLAIN,
			$plainTextSnakFormatter,
			'wfEscapeWikiText'
		);

		return new BinaryOptionDispatchingSnakFormatter(
			SnakFormatter::FORMAT_PLAIN,
			$this->propertyDataTypeLookup,
			$plainTextSnakFormatter,
			$escapingSnakFormatter,
			[ 'url' ]
		);
	}

}
