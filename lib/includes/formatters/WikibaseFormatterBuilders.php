<?php

namespace Wikibase\Lib;

use Language;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use ValueFormatters\ValueFormatterFactory;
use Wikibase\Client\WikibaseClient;
use Wikibase\EntityLookup;
use Wikibase\Item;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\LanguageWithConversion;
use Wikibase\Repo\WikibaseRepo;

/**
 * Defines the snak and value formatters supported by Wikibase.
 *
 * @since 0.5
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class WikibaseFormatterBuilders {

	/**
	 * @var EntityLookup
	 */
	protected $entityLookup;

	/**
	 * @var PropertyDataTypeLookup
	 */
	protected $propertyDataTypeLookup;

	/**
	 * @var Language
	 */
	protected $defaultLanguage;

	/**
	 * This determines which value is formatted how by providing a formatter mapping
	 * for each format.
	 *
	 * Each formatter mapping maps prefixed format IDs to formatter spec, using the
	 * prefix "PT:" for property data type based mappings, and "VT:" for data value type mappings.
	 * The spec can either be a class name or a callable builder. If a class name is given, the
	 * constructor is called with a single argument, the FormatterOptions. If a builder is
	 * used, this WikibaseFormatterBuilders will be provided as an additional parameter
	 * to the builder.
	 *
	 * When determining the formatter for a given format, data type and value type, two
	 * levels of fallback are applied:
	 *
	 * * Formats fall back on each other (using appropriate escaping): Wikitext falls back to
	 *   plain text, HTML falls back to plain text, and HTML-Widgets fall back to simple HTML.
	 *
	 * * If no formatter is defined for a property data type (using the PT prefix),
	 *   the value's type is used to find an appropriate formatter (with the VT prefix).
	 *
	 * @var callable[][]
	 */
	protected $valueFormatterSpecs = array(

		// formatters to use for plain text output
		SnakFormatter::FORMAT_PLAIN => array(
			'VT:string' => 'ValueFormatters\StringFormatter',
			'VT:globecoordinate' => 'ValueFormatters\GlobeCoordinateFormatter',
			'VT:time' => 'Wikibase\Lib\MwTimeIsoFormatter',
			'VT:wikibase-entityid' => array( 'Wikibase\Lib\WikibaseFormatterBuilders', 'newEntityIdFormatter' ),
		),

		// Formatters to use for wiki text output.
		// Falls back to plain text formatters (plus escaping).
		SnakFormatter::FORMAT_WIKI => array(
			'PT:url' => 'ValueFormatters\StringFormatter', // no escaping!
			//'PT:wikibase-item' => 'Wikibase\Lib\LocalItemLinkFormatter', // TODO
		),

		// Formatters to use for HTML display.
		// Falls back to plain text formatters (plus escaping).
		SnakFormatter::FORMAT_HTML => array(
			//'PT:url' => 'Wikibase\Lib\LinkFormatter', // TODO
			//'PT:commonsMedia' => 'Wikibase\Lib\CommonsLinkFormatter', // TODO
			//'PT:wikibase-item' => 'Wikibase\Lib\ItemLinkFormatter', // TODO
		),

		// Formatters to use for HTML widgets.
		// Falls back to HTML display formatters.
		SnakFormatter::FORMAT_HTML_WIDGET => array(
		),
	);

	/**
	 * @param EntityLookup   $lookup
	 * @param PropertyDataTypeLookup $propertyDataTypeLookup
	 * @param Language               $defaultLanguage
	 */
	public function __construct(
		EntityLookup $lookup,
		PropertyDataTypeLookup $propertyDataTypeLookup,
		Language $defaultLanguage
	) {
		$this->propertyDataTypeLookup = $propertyDataTypeLookup;
		$this->entityLookup = $lookup;
		$this->defaultLanguage = $defaultLanguage;
	}

	/**
	 * @return array SnakFormatter builder specs
	 */
	public function getSnakFormatterBuildersForFormats() {
		$buildDispatchingSnakFormatter = array( $this, 'buildDispatchingSnakFormatter' );

		// the format is passed as a parameter to the builder,
		// so we can use the same builder for all formats
		$types = array(
			SnakFormatter::FORMAT_WIKI => $buildDispatchingSnakFormatter,
			SnakFormatter::FORMAT_PLAIN => $buildDispatchingSnakFormatter,
			SnakFormatter::FORMAT_HTML => $buildDispatchingSnakFormatter,
			SnakFormatter::FORMAT_HTML_WIDGET => $buildDispatchingSnakFormatter,
		);

		return $types;
	}

	/**
	 * @return array ValueFormatter builder specs
	 */
	public function getValueFormatterBuildersForFormats() {
		$buildDispatchingValueFormatter = array( $this, 'buildDispatchingValueFormatter' );

		// the format is passed as a parameter to the builder,
		// so we can use the same builder for all formats
		$types = array(
			SnakFormatter::FORMAT_WIKI => $buildDispatchingValueFormatter,
			SnakFormatter::FORMAT_PLAIN => $buildDispatchingValueFormatter,
			SnakFormatter::FORMAT_HTML => $buildDispatchingValueFormatter,
			SnakFormatter::FORMAT_HTML_WIDGET => $buildDispatchingValueFormatter,
		);

		return $types;
	}


	/**
	 * Wrapper for newDispatchingSnakFormatter() that exposes the signature required for builders
	 * in the constructor of OutputFormatSnakFormatterFactory.
	 *
	 * @param OutputFormatSnakFormatterFactory $factory (unused)
	 * @param string $format
	 * @param FormatterOptions $options
	 *
	 * @throws \InvalidArgumentException
	 * @return DispatchingValueFormatter
	 */
	public function buildDispatchingSnakFormatter( OutputFormatSnakFormatterFactory $factory, $format, FormatterOptions $options ) {
		return $this->newDispatchingSnakFormatter( $format, $options );
	}

	/**
	 * Returns a DispatchingSnakFormatter for the given format, that will dispatch based on
	 * the snak type. The instance returned by this method will cover all standard snak types.
	 *
	 * @param string               $format
	 * @param FormatterOptions     $options
	 *
	 * @return DispatchingSnakFormatter
	 */
	public function newDispatchingSnakFormatter( $format, FormatterOptions $options ) {
		$this->initLanguageDefaults( $options );
		$lang = $options->getOption( ValueFormatter::OPT_LANG );

		$noValueSnakFormatter = new MessageSnakFormatter( 'novalue', $this->getMessage( 'wikibase-snakview-snaktypeselector-novalue', $lang ), $format );
		$someValueSnakFormatter = new MessageSnakFormatter( 'somevalue', $this->getMessage( 'wikibase-snakview-snaktypeselector-somevalue', $lang ), $format );

		$valueFormatter = $this->newDispatchingValueFormatter( $format, $options );
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

	/**
	 * Initializes the options keys ValueFormatter::OPT_LANG and 'languages' if
	 * they are not yet set.
	 *
	 * @param FormatterOptions $options
	 *
	 * @throws \InvalidArgumentException
	 * @todo  : Sort out how the desired language is specified. We have two language options,
	 *        each accepting different ways of specifying the language. That's horrible.
	 */
	private function initLanguageDefaults( $options ) {
		$languageFallbackChainFactory = new LanguageFallbackChainFactory();

		if ( !$options->hasOption( ValueFormatter::OPT_LANG ) ) {
			$options->setOption( ValueFormatter::OPT_LANG, $this->defaultLanguage->getCode() );
		}

		$lang = $options->getOption( ValueFormatter::OPT_LANG );
		if ( !is_string( $lang ) ) {
			throw new \InvalidArgumentException( 'The value of OPT_LANG must be a language code. For a fallback chain, use the `languages` option.' );
		}

		if ( !$options->hasOption( 'languages' ) ) {
			$fallbackMode = (
				LanguageFallbackChainFactory::FALLBACK_VARIANTS
				| LanguageFallbackChainFactory::FALLBACK_OTHERS
				| LanguageFallbackChainFactory::FALLBACK_SELF );

			$options->setOption( 'languages', $languageFallbackChainFactory->newFromLanguageCode( $lang, $fallbackMode ) );
		}

		if ( !( $options->getOption( 'languages' ) instanceof LanguageFallbackChain ) ) {
			throw new \InvalidArgumentException( 'The value of the `languages` option must be an instance of LanguageFallbackChain.' );
		}
	}

	/**
	 * Wrapper for newDispatchingValueFormatter() that exposes the signature required for builders
	 * in the constructor of OutputFormatValueFormatterFactory.
	 *
	 * @param OutputFormatValueFormatterFactory $factory (unused)
	 * @param string $format
	 * @param FormatterOptions $options
	 *
	 * @throws \InvalidArgumentException
	 * @return DispatchingValueFormatter
	 */
	public function buildDispatchingValueFormatter( OutputFormatValueFormatterFactory $factory, $format, FormatterOptions $options ) {
		return $this->newDispatchingValueFormatter( $format, $options );
	}

	/**
	 * Returns a DispatchingSnakFormatter for the given format, that will dispatch based on
	 * the data value type or property data type.
	 *
	 * @param string $format
	 * @param FormatterOptions $options
	 *
	 * @throws \InvalidArgumentException
	 * @return DispatchingValueFormatter
	 */
	public function newDispatchingValueFormatter( $format, FormatterOptions $options ) {
		switch ( $format ) {
			case SnakFormatter::FORMAT_PLAIN:
				$formatters = $this->getPlainTextFormatters( $options );
				break;
			case SnakFormatter::FORMAT_WIKI:
				$formatters = $this->getWikiTextFormatters( $options );
				break;
			case SnakFormatter::FORMAT_HTML:
				$formatters = $this->getHtmlFormatters( $options );
				break;
			case SnakFormatter::FORMAT_HTML_WIDGET:
				$formatters = $this->getWidgetFormatters( $options );
				break;
			default:
				throw new \InvalidArgumentException( 'Unsupported format: ' . $format );
		}

		return new DispatchingValueFormatter( $formatters );
	}

	/**
	 * Returns a full set of formatters for generating plain text output.
	 *
	 * @param FormatterOptions $options
	 * @param string[] $skip A list of types to be skipped. Useful when the caller already has
	 *        formatters for some types.
	 *
	 * @return ValueFormatter[] A map from prefixed type IDs to ValueFormatter instances.
	 */
	public function getPlainTextFormatters( FormatterOptions $options, array $skip = array() ) {
		$plainFormatters = $this->buildDefinedFormatters( SnakFormatter::FORMAT_PLAIN, $options, $skip );
		return $plainFormatters;
	}

	/**
	 * Returns a full set of formatters for generating wikitext output.
	 * If there are formatters defined for plain text that are not defined for wikitext,
	 * the plain text formatters are used with the appropriate escaping applied.
	 *
	 * @param FormatterOptions $options
	 * @param string[] $skip A list of types to be skipped. Useful when the caller already has
	 *        formatters for some types.
	 *
	 * @return ValueFormatter[] A map from prefixed type IDs to ValueFormatter instances.
	 */
	public function getWikiTextFormatters( FormatterOptions $options, array $skip = array() ) {
		$wikiFormatters = $this->buildDefinedFormatters( SnakFormatter::FORMAT_WIKI, $options );
		$plainFormatters = $this->getPlainTextFormatters( $options, array_merge( $skip, array_keys( $wikiFormatters ) ) );

		$wikiFormatters = array_merge(
			$wikiFormatters,
			$this->makeEscapingFormatters( $plainFormatters, 'wfEscapeWikiText' )
		);

		return $wikiFormatters;
	}

	/**
	 * Returns a full set of formatters for generating HTML output.
	 * If there are formatters defined for plain text that are not defined for HTML,
	 * the plain text formatters are used with the appropriate escaping applied.
	 *
	 * @param FormatterOptions $options
	 * @param string[] $skip A list of types to be skipped. Useful when the caller already has
	 *        formatters for some types.
	 *
	 * @return ValueFormatter[] A map from prefixed type IDs to ValueFormatter instances.
	 */
	public function getHtmlFormatters( FormatterOptions $options, array $skip = array() ) {
		$htmlFormatters = $this->buildDefinedFormatters( SnakFormatter::FORMAT_HTML, $options );
		$plainFormatters = $this->getPlainTextFormatters( $options, array_merge( $skip, array_keys( $htmlFormatters ) ) );

		$htmlFormatters = array_merge(
			$htmlFormatters,
			$this->makeEscapingFormatters( $plainFormatters, 'htmlspecialchars' )
		);

		return $htmlFormatters;
	}


	/**
	 * Returns a full set of formatters for generating HTML widgets.
	 * If there are formatters defined for HTML that are not defined for widgets,
	 * the HTML formatters are used.
	 *
	 * @param FormatterOptions $options
	 * @param string[] $skip A list of types to be skipped. Useful when the caller already has
	 *        formatters for some types.
	 *
	 * @return ValueFormatter[] A map from prefixed type IDs to ValueFormatter instances.
	 */
	public function getWidgetFormatters( FormatterOptions $options, array $skip = array() ) {
		$widgetFormatters = $this->buildDefinedFormatters( SnakFormatter::FORMAT_HTML_WIDGET, $options );
		$htmlFormatters = $this->getHtmlFormatters( $options, array_merge( $skip, array_keys( $widgetFormatters ) ) );

		$widgetFormatters = array_merge(
			$widgetFormatters,
			$htmlFormatters
		);

		return $widgetFormatters;
	}

	/**
	 * Instantiates the formatters defined for the given format in
	 * WikibaseFormatterBuilders::$valueFormatterSpecs.
	 *
	 * @see WikibaseFormatterBuilders::$valueFormatterSpecs
	 *
	 * @param string $format
	 * @param FormatterOptions $options
	 * @param string[] $skip A list of types to be skipped (using the 'VT:' prefix for data value types,
	 *        or the 'PT:' prefix for property data types). Useful when the caller already has
	 *        formatters for some types.
	 *
	 * @return ValueFormatter[] A map from prefixed type IDs to ValueFormatter instances.
	 */
	protected function buildDefinedFormatters( $format, FormatterOptions $options, array $skip = array() ) {
		$formatters = array();

		if ( !isset( $this->valueFormatterSpecs[$format] ) ) {
			return array();
		}

		/* @var callable[] $specs */
		$specs = $this->valueFormatterSpecs[ $format ];

		foreach ( $specs as $type => $spec ) {
			if ( $skip && in_array( $type, $skip ) ) {
				continue;
			}

			$formatters[$type] = $this->newFromSpec( $spec, $options );
		}

		return $formatters;
	}

	/**
	 * Instantiates a Formatter from a classname or by calling a builder.
	 *
	 * @param string|callable $spec A class name, or a callable builder.
	 * @param FormatterOptions $options
	 *
	 * @throws \RuntimeException
	 * @return mixed
	 */
	protected function newFromSpec( $spec, FormatterOptions $options ) {
		if ( is_string( $spec ) ) {
			$obj = new $spec( $options );
		} else {
			$obj = call_user_func( $spec, $options, $this );
		}

		if ( !( $obj instanceof ValueFormatter ) ) {
			throw new \RuntimeException( 'Formatter does not implement the ValueFormatter interface: ' . get_class( $obj ) );
		}

		return $obj;
	}

	/**
	 * Builder callback for use in WikibaseFormatterBuilders::$valueFormatterSpecs.
	 * Used to inject services into the Formatter.
	 *
	 * @param FormatterOptions $options
	 * @param WikibaseFormatterBuilders $builders
	 *
	 * @return EntityIdLabelFormatter
	 */
	protected static function newEntityIdFormatter( FormatterOptions $options, $builders ) {
		return new EntityIdLabelFormatter( $options, $builders->entityLookup );
	}

	/**
	 * Wrap each entry in a list of formatters in an EscapingValueFormatter.
	 * This is useful to apply escaping to the output of a set of formatters,
	 * allowing them to be used for a different format.
	 *
	 * @param ValueFormatter[] $formatters
	 * @param string $escape The escape callback, e.g. 'htmlspecialchars' or 'wfEscapeWikitext'.
	 *
	 * @return array
	 */
	public function makeEscapingFormatters( array $formatters, $escape ) {
		$escapingFormatters = array();

		foreach ( $formatters as $key => $formatter ) {
			$escapingFormatters[$key] = new EscapingValueFormatter( $formatter, $escape );
		}

		return $escapingFormatters;
	}
}
