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
use Wikibase\Lib\Formatters\BinaryOptionDispatchingSnakFormatter;
use Wikibase\Lib\Formatters\EscapingSnakFormatter;
use Wikibase\Lib\Formatters\FormatterLabelDescriptionLookupFactory;
use Wikibase\Lib\Formatters\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookupFactory;

/**
 * A factory for SnakFormatters in a client context, to be reused in different methods that "access
 * repository data" from a client (typically parser functions and Lua scripts).
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Marius Hoch
 */
class DataAccessSnakFormatterFactory {

	/**
	 * A plain text format, escaped for wikitext.
	 *
	 * Values are formatted in simple, textual formats,
	 * and any wikitext syntax they might contain (e.g. in string values) is escaped.
	 * The only exception are URLs, which are not escaped.
	 */
	public const TYPE_ESCAPED_PLAINTEXT = 'escaped-plaintext';
	/**
	 * A rich wikitext format.
	 *
	 * Values may contain markup, such as hyperlinks or `<span>`s with language tags.
	 * Individual formatters still wikitext-escape their contents as needed (e.g. for strings).
	 * The result is always wrapped in an outer `<span>`.
	 */
	public const TYPE_RICH_WIKITEXT = 'rich-wikitext';

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
	 * @var FallbackLabelDescriptionLookupFactory
	 */
	private $fallbackLabelDescriptionLookupFactory;

	/**
	 * @var bool
	 */
	private $trackUsagesInAllLanguages;

	public function __construct(
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		OutputFormatSnakFormatterFactory $snakFormatterFactory,
		PropertyDataTypeLookup $propertyDataTypeLookup,
		EntityIdParser $repoItemUriParser,
		FallbackLabelDescriptionLookupFactory $fallbackLabelDescriptionLookupFactory,
		$trackUsagesInAllLanguages = false
	) {
		if ( !is_bool( $trackUsagesInAllLanguages ) ) {
			throw new InvalidArgumentException( '$trackUsagesInAllLanguages must be a bool' );
		}

		$this->languageFallbackChainFactory = $languageFallbackChainFactory;
		$this->snakFormatterFactory = $snakFormatterFactory;
		$this->propertyDataTypeLookup = $propertyDataTypeLookup;
		$this->repoItemUriParser = $repoItemUriParser;
		$this->fallbackLabelDescriptionLookupFactory = $fallbackLabelDescriptionLookupFactory;
		$this->trackUsagesInAllLanguages = $trackUsagesInAllLanguages;
	}

	/**
	 * This returns a SnakFormatter that will return either "rich" wikitext, or wikitext escaped
	 * plain text. The only exception are URLs, these are not escaped but plain text.
	 *
	 * @param Language $language
	 * @param UsageAccumulator $usageAccumulator
	 * @param string $type One of DataAccessSnakFormatterFactory::TYPE_*
	 *
	 * @return SnakFormatter
	 */
	public function newWikitextSnakFormatter(
		Language $language,
		UsageAccumulator $usageAccumulator,
		$type = self::TYPE_ESCAPED_PLAINTEXT
	) {
		$fallbackChain = $this->languageFallbackChainFactory->newFromLanguage( $language );

		$options = new FormatterOptions( [
			FormatterLabelDescriptionLookupFactory::OPT_LANGUAGE_FALLBACK_CHAIN => $fallbackChain,
			SnakFormatter::OPT_LANG => $language->getCode(),
		] );

		if ( $type === self::TYPE_RICH_WIKITEXT ) {
			$snakFormatter = $this->getRichWikitextSnakFormatterForOptions( $options );
		} else {
			$snakFormatter = $this->getPlainTextSnakFormatterForOptions( $options );
		}

		return new UsageTrackingSnakFormatter(
			$snakFormatter,
			$usageAccumulator,
			$this->repoItemUriParser,
			new UsageTrackingLanguageFallbackLabelDescriptionLookup(
				$this->fallbackLabelDescriptionLookupFactory->newLabelDescriptionLookup( $language ),
				$usageAccumulator,
				$fallbackChain,
				$this->trackUsagesInAllLanguages
			)
		);
	}

	/**
	 * @param FormatterOptions $options
	 * @return EscapingSnakFormatter
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
