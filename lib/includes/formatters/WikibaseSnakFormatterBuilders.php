<?php

namespace Wikibase\Lib;

use Language;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\Item;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\LanguageWithConversion;

/**
 * Defines the snak formatters supported by Wikibase.
 * This largely relies on WikibaseValueFormatterBase, adding some logic
 * for handling different types of Snaks.
 *
 * @since 0.5
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class WikibaseSnakFormatterBuilders {

	/**
	 * @var WikibaseValueFormatterBuilders
	 */
	protected $valueFormatterBuilders;

	/**
	 * @var PropertyDataTypeLookup
	 */
	protected $propertyDataTypeLookup;

	/**
	 * @param WikibaseValueFormatterBuilders $valueFormatterBuilders
			'VT:bad' => 'Wikibase\Lib\UnDeserializableValueFormatter'
	 * @param PropertyDataTypeLookup $propertyDataTypeLookup
	 */
	public function __construct(
		WikibaseValueFormatterBuilders $valueFormatterBuilders,
		PropertyDataTypeLookup $propertyDataTypeLookup
	) {
		$this->valueFormatterBuilders = $valueFormatterBuilders;
		$this->propertyDataTypeLookup = $propertyDataTypeLookup;
	}

	/**
	 * @return array DataType builder specs
	 */
	public function getSnakFormatterBuildersForFormats() {
		$buildDispatchingSnakFormatter = array( $this, 'buildDispatchingSnakFormatter' );

		$types = array(
			SnakFormatter::FORMAT_WIKI => $buildDispatchingSnakFormatter,
			SnakFormatter::FORMAT_PLAIN => $buildDispatchingSnakFormatter,
			SnakFormatter::FORMAT_HTML => $buildDispatchingSnakFormatter,
			SnakFormatter::FORMAT_HTML_WIDGET => $buildDispatchingSnakFormatter,
		);

		return $types;
	}

	/**
	 * Returns a DispatchingSnakFormatter for the given format, that will dispatch based on
	 * the snak type. The instance returned by this method will cover all standard snak types.
	 *
	 * @param OutputFormatSnakFormatterFactory $factory
	 * @param string               $format
	 * @param FormatterOptions     $options
	 *
	 * @return DispatchingSnakFormatter
	 */
	public function buildDispatchingSnakFormatter( OutputFormatSnakFormatterFactory $factory, $format, FormatterOptions $options ) {
		$this->valueFormatterBuilders->applyLanguageDefaults( $options );
		$lang = $options->getOption( ValueFormatter::OPT_LANG );

		$noValueSnakFormatter = new MessageSnakFormatter( 'novalue', $this->getMessage( 'wikibase-snakview-snaktypeselector-novalue', $lang ), $format );
		$someValueSnakFormatter = new MessageSnakFormatter( 'somevalue', $this->getMessage( 'wikibase-snakview-snaktypeselector-somevalue', $lang ), $format );

		$factory = new OutputFormatValueFormatterFactory( $this->valueFormatterBuilders->getValueFormatterBuildersForFormats() );
		$valueFormatter = $this->valueFormatterBuilders->buildDispatchingValueFormatter( $factory, $format, $options );
		$valueSnakFormatter = new PropertyValueSnakFormatter( $format, $valueFormatter, $this->propertyDataTypeLookup );

		$formatters = array(
			'novalue' => $noValueSnakFormatter,
			'somevalue' => $someValueSnakFormatter,
			'value' => $valueSnakFormatter,
		);

		return new DispatchingSnakFormatter( $format, $formatters );
	}

	/**
	 * @param string $key
	 * @param string $lang
	 *
	 * @return \Message
	 */
	private function getMessage( $key, $lang ) {
		$msg = wfMessage( $key );
		$msg = $msg->inLanguage( $lang );
		return $msg;
	}
}
