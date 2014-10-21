<?php

namespace Wikibase\Lib;

use DataValues\Geo\Formatters\GeoCoordinateFormatter;
use DataValues\Geo\Formatters\GlobeCoordinateFormatter;
use InvalidArgumentException;
use Language;
use RuntimeException;
use ValueFormatters\DecimalFormatter;
use ValueFormatters\FormatterOptions;
use ValueFormatters\QuantityFormatter;
use ValueFormatters\ValueFormatter;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Lib\Store\EntityTitleLookup;

/**
 * Defines the formatters for DataValues supported by Wikibase.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class WikibaseValueFormatterBuilders {

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var Language
	 */
	private $defaultLanguage;

	/**
	 * @var EntityTitleLookup|null
	 */
	private $entityTitleLookup;

	/**
	 * This determines which value is formatted how by providing a formatter mapping
	 * for each format.
	 *
	 * Each formatter mapping maps prefixed format IDs to formatter spec, using the
	 * prefix "PT:" for property data type based mappings, and "VT:" for data value type mappings.
	 * The spec can either be a ValueFormatter object, a class name, or a callable builder.
	 * If a class name is given, the constructor is called with a single argument, the
	 * FormatterOptions. If a builder is used, this WikibaseValueFormatterBuilders will be provided
	 * as an additional parameter to the builder.
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
			'VT:globecoordinate' => array( 'Wikibase\Lib\WikibaseValueFormatterBuilders', 'newGlobeCoordinateFormatter' ),
			'VT:quantity' =>  array( 'Wikibase\Lib\WikibaseValueFormatterBuilders', 'newQuantityFormatter' ),
			'VT:time' => 'Wikibase\Lib\MwTimeIsoFormatter',
			'VT:wikibase-entityid' => array( 'Wikibase\Lib\WikibaseValueFormatterBuilders', 'newEntityIdFormatter' ),
			'VT:bad' => 'Wikibase\Lib\UnDeserializableValueFormatter',
			'VT:monolingualtext' => 'Wikibase\Formatters\MonolingualTextFormatter',
		),

		// Formatters to use for wiki text output.
		// Falls back to plain text formatters (plus escaping).
		SnakFormatter::FORMAT_WIKI => array(
			'PT:url' => 'ValueFormatters\StringFormatter', // no escaping!
			//'PT:wikibase-item' => 'Wikibase\Lib\LocalItemLinkFormatter', // TODO
			//'VT:monolingualtext' => 'Wikibase\Formatters\MonolingualWikitextFormatter', // TODO
		),

		// Formatters to use for HTML display.
		// Falls back to plain text formatters (plus escaping).
		SnakFormatter::FORMAT_HTML => array(
			'PT:url' => 'Wikibase\Lib\HtmlUrlFormatter',
			'PT:commonsMedia' => 'Wikibase\Lib\CommonsLinkFormatter',
			'PT:wikibase-item' =>  array( 'Wikibase\Lib\WikibaseValueFormatterBuilders', 'newEntityIdHtmlLinkFormatter' ),
			'VT:time' => array( 'Wikibase\Lib\WikibaseValueFormatterBuilders', 'newHtmlTimeFormatter' ),
			'VT:monolingualtext' => 'Wikibase\Formatters\MonolingualHtmlFormatter',
		),

		// Formatters to use for HTML widgets.
		// Falls back to HTML display formatters.
		SnakFormatter::FORMAT_HTML_WIDGET => array(
		),

		// Formatters to use for HTML in diffs.
		// Falls back to HTML display formatters.
		SnakFormatter::FORMAT_HTML_DIFF => array(
			'PT:quantity' => 'Wikibase\Lib\QuantityDetailsFormatter',
			'PT:time' => 'Wikibase\Lib\TimeDetailsFormatter',
			'PT:globe-coordinate' => 'Wikibase\Lib\GlobeCoordinateDetailsFormatter',
		),
	);

	public function __construct(
		EntityLookup $entityLookup,
		Language $defaultLanguage,
		EntityTitleLookup $entityTitleLookup = null
	) {
		$this->entityLookup = $entityLookup;
		$this->defaultLanguage = $defaultLanguage;
		$this->entityTitleLookup = $entityTitleLookup;
	}

	/**
	 * Sets the ValueFormatter to use to render values of the given type in the given format.
	 *
	 * @param string $format See SnakFormatter::FORMAT_XXX
	 * @param string $type A data value type (prefixed with `VT:`) or a property data type (prefixed with `PT:`).
	 * @param ValueFormatter|null $formatter
	 */
	public function setValueFormatter( $format, $type, ValueFormatter $formatter = null ) {
		$this->setValueFormatterSpec( $format, $type, $formatter );
	}

	/**
	 * Sets the value formatter class to use to render values of the given type in the given format.
	 * The class will be instantiated on demand, by calling the constructor with a
	 * FormatterOptions object as the only parameter.
	 *
	 * @param string $format See SnakFormatter::FORMAT_XXX
	 * @param string $type A data value type (prefixed with `VT:`) or a property data type (prefixed with `PT:`).
	 * @param null|string $formatterClass
	 *
	 * @throws InvalidArgumentException
	 */
	public function setValueFormatterClass( $format, $type, $formatterClass = null ) {
		if ( $formatterClass !== null ) {
			if ( !is_string( $formatterClass ) ) {
				throw new InvalidArgumentException( '$formatterClass must be a non-empty string' );
			}

			if ( !class_exists( $formatterClass ) ) {
				throw new InvalidArgumentException( 'Unknown $formatterClass: ' . $formatterClass );
			}
		}

		$this->setValueFormatterSpec( $format, $type, $formatterClass );
	}

	/**
	 * Sets the ValueFormatter to use to render values of the given type in the given format.
	 * The builder will be called on demand, with a FormatterOptions object as the first
	 * and this WikibaseFormatterBuilder instance as the second parameter.
	 *
	 * @param string $format See SnakFormatter::FORMAT_XXX
	 * @param string $type A data value type (prefixed with `VT:`) or a property data type (prefixed with `PT:`).
	 * @param null|callable $formatterBuilder
	 *
	 * @throws InvalidArgumentException
	 */
	public function setValueFormatterBuilder( $format, $type, $formatterBuilder = null ) {
		if ( $formatterBuilder !== null ) {
			if ( is_string( $formatterBuilder ) ) {
				throw new InvalidArgumentException( '$formatterBuilder must not be a string' );
			}

			if ( !is_callable( $formatterBuilder ) ) {
				throw new InvalidArgumentException( '$formatterBuilder must be a callable' );
			}
		}

		$this->setValueFormatterSpec( $format, $type, $formatterBuilder);
	}

	/**
	 * Sets the formatter spec used to determine or create formatters for the given type and format.
	 *
	 * @param string $format See SnakFormatter::FORMAT_XXX
	 * @param string $type A data value type (prefixed with `VT:`) or a property data type (prefixed with `PT:`).
	 * @param string|callable|ValueFormatter|null $spec
	 *
	 * @throws InvalidArgumentException
	 */
	private function setValueFormatterSpec( $format, $type, $spec ) {
		if ( !is_string( $format ) || $format === '' ) {
			throw new InvalidArgumentException( '$format must be a non-empty string' );
		}

		if ( !is_string( $type ) || $type === '' ) {
			throw new InvalidArgumentException( '$type must be a non-empty string' );
		}

		if ( !preg_match( '/^(VT|PT):/', $type ) ) {
			throw new InvalidArgumentException( '$type must start with `VT:` (for data value types) or `PT:` (for property data types)' );
		}

		if ( $spec === null ) {
			unset( $this->valueFormatterSpecs[$format][$type] );
		} else {
			$this->valueFormatterSpecs[$format][$type] = $spec;
		}
	}

	/**
	 * @return array DataType builder specs
	 */
	public function getValueFormatterBuildersForFormats() {
		$buildDispatchingValueFormatter = array( $this, 'buildDispatchingValueFormatter' );

		$types = array(
			SnakFormatter::FORMAT_WIKI => $buildDispatchingValueFormatter,
			SnakFormatter::FORMAT_PLAIN => $buildDispatchingValueFormatter,
			SnakFormatter::FORMAT_HTML => $buildDispatchingValueFormatter,
			SnakFormatter::FORMAT_HTML_WIDGET => $buildDispatchingValueFormatter,
		);

		return $types;
	}

	/**
	 * Initializes the options keys ValueFormatter::OPT_LANG and 'languages' if
	 * they are not yet set.
	 *
	 * @param FormatterOptions $options The options to modify.
	 *
	 * @throws InvalidArgumentException
	 * @todo  : Sort out how the desired language is specified. We have two language options,
	 *        each accepting different ways of specifying the language. That's not good.
	 */
	public function applyLanguageDefaults( $options ) {
		$languageFallbackChainFactory = new LanguageFallbackChainFactory();

		if ( !$options->hasOption( ValueFormatter::OPT_LANG ) ) {
			$options->setOption( ValueFormatter::OPT_LANG, $this->defaultLanguage->getCode() );
		}

		$lang = $options->getOption( ValueFormatter::OPT_LANG );
		if ( !is_string( $lang ) ) {
			throw new InvalidArgumentException( 'The value of OPT_LANG must be a language code. For a fallback chain, use the `languages` option.' );
		}

		if ( !$options->hasOption( 'languages' ) ) {
			$fallbackMode = (
				LanguageFallbackChainFactory::FALLBACK_VARIANTS
				| LanguageFallbackChainFactory::FALLBACK_OTHERS
				| LanguageFallbackChainFactory::FALLBACK_SELF );

			$options->setOption( 'languages', $languageFallbackChainFactory->newFromLanguageCode( $lang, $fallbackMode ) );
		}

		if ( !( $options->getOption( 'languages' ) instanceof LanguageFallbackChain ) ) {
			throw new InvalidArgumentException( 'The value of the `languages` option must be an instance of LanguageFallbackChain.' );
		}
	}

	/**
	 * Returns a DispatchingSnakFormatter for the given format, that will dispatch based on
	 * the data value type or property data type.
	 *
	 * @param OutputFormatValueFormatterFactory $factory (unused)
	 * @param string $format
	 * @param FormatterOptions $options
	 *
	 * @throws InvalidArgumentException
	 * @return DispatchingValueFormatter
	 */
	public function buildDispatchingValueFormatter( OutputFormatValueFormatterFactory $factory, $format, FormatterOptions $options ) {
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
			case SnakFormatter::FORMAT_HTML_DIFF:
				$formatters = $this->getDiffFormatters( $options );
				break;
			default:
				throw new InvalidArgumentException( 'Unsupported format: ' . $format );
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
		return $this->buildDefinedFormatters(
			SnakFormatter::FORMAT_PLAIN,
			$options,
			$skip
		);
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
		$wikiFormatters = $this->buildDefinedFormatters(
			SnakFormatter::FORMAT_WIKI,
			$options,
			$skip
		);
		$plainFormatters = $this->getPlainTextFormatters(
			$options, array_merge( $skip, array_keys( $wikiFormatters ) )
		);

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
		$htmlFormatters = $this->buildDefinedFormatters(
			SnakFormatter::FORMAT_HTML,
			$options,
			$skip
		);
		$plainFormatters = $this->getPlainTextFormatters(
			$options,
			array_merge( $skip, array_keys( $htmlFormatters ) )
		);

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
		$widgetFormatters = $this->buildDefinedFormatters(
			SnakFormatter::FORMAT_HTML_WIDGET,
			$options,
			$skip
		);
		$htmlFormatters = $this->getHtmlFormatters(
			$options,
			array_merge( $skip, array_keys( $widgetFormatters ) )
		);

		$widgetFormatters = array_merge(
			$widgetFormatters,
			$htmlFormatters
		);

		return $widgetFormatters;
	}


	/**
	 * Returns a full set of formatters for generating HTML for use in diffs.
	 * If there are formatters defined for HTML that are not defined for diffs,
	 * the HTML formatters are used.
	 *
	 * @param FormatterOptions $options
	 * @param string[] $skip A list of types to be skipped. Useful when the caller already has
	 *        formatters for some types.
	 *
	 * @return ValueFormatter[] A map from prefixed type IDs to ValueFormatter instances.
	 */
	public function getDiffFormatters( FormatterOptions $options, array $skip = array() ) {
		$diffFormatters = $this->buildDefinedFormatters(
			SnakFormatter::FORMAT_HTML_DIFF,
			$options,
			$skip
		);
		$htmlFormatters = $this->getHtmlFormatters(
			$options,
			array_merge( $skip, array_keys( $diffFormatters ) )
		);

		$diffFormatters = array_merge(
			$diffFormatters,
			$htmlFormatters
		);

		return $diffFormatters;
	}

	/**
	 * Instantiates the formatters defined for the given format in
	 * WikibaseValueFormatterBuilders::$valueFormatterSpecs.
	 *
	 * @see WikibaseValueFormatterBuilders::$valueFormatterSpecs
	 *
	 * @param string $format
	 * @param FormatterOptions $options
	 * @param string[] $skip A list of types to be skipped (using the 'VT:' prefix for data value
	 *        types, or the 'PT:' prefix for property data types). Useful when the caller already
	 *        has formatters for some types.
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
	 * Instantiates a Formatter from a class name or by calling a builder.
	 *
	 * @param string|callable|ValueFormatter $spec A class name, or a callable builder,
	 *        or a ValueFormatter object.
	 * @param FormatterOptions $options
	 *
	 * @throws RuntimeException
	 * @return mixed
	 */
	protected function newFromSpec( $spec, FormatterOptions $options ) {
		if ( $spec instanceof ValueFormatter ) {
			$obj = $spec;
		} elseif ( is_string( $spec ) ) {
			$obj = new $spec( $options );
		} else {
			$obj = call_user_func( $spec, $options, $this );
		}

		if ( !( $obj instanceof ValueFormatter ) ) {
			throw new RuntimeException(
				'Formatter does not implement the ValueFormatter interface: ' . get_class( $obj )
			);
		}

		return $obj;
	}

	/**
	 * Builder callback for use in WikibaseValueFormatterBuilders::$valueFormatterSpecs.
	 * Used to inject services into the EntityIdLabelFormatter.
	 *
	 * @param FormatterOptions $options
	 * @param WikibaseValueFormatterBuilders $builders
	 *
	 * @return EntityIdLabelFormatter
	 */
	protected static function newEntityIdFormatter(
		FormatterOptions $options,
		WikibaseValueFormatterBuilders $builders
	) {
		return new EntityIdLabelFormatter( $options, $builders->entityLookup );
	}

	/**
	 * Builder callback for use in WikibaseValueFormatterBuilders::$valueFormatterSpecs.
	 * Used to inject services into the EntityIdHtmlLinkFormatter.
	 *
	 * @param FormatterOptions $options
	 * @param WikibaseValueFormatterBuilders $builders
	 *
	 * @return EntityIdHtmlLinkFormatter
	 */
	protected static function newEntityIdHtmlLinkFormatter(
		FormatterOptions $options,
		WikibaseValueFormatterBuilders $builders
	) {
		return new EntityIdHtmlLinkFormatter(
			$options,
			$builders->entityLookup,
			$builders->entityTitleLookup
		);
	}

	/**
	 * Builder callback for use in WikibaseValueFormatterBuilders::$valueFormatterSpecs.
	 * Used to inject a formatter into the HtmlTimeFormatter.
	 *
	 * @param FormatterOptions $options
	 * @param WikibaseValueFormatterBuilders $builders
	 *
	 * @return HtmlTimeFormatter
	 */
	protected static function newHtmlTimeFormatter(
		FormatterOptions $options,
		WikibaseValueFormatterBuilders $builders
	) {
		return new HtmlTimeFormatter( $options, new MwTimeIsoFormatter( $options ) );
	}

	/**
	 * Builder callback for use in WikibaseValueFormatterBuilders::$valueFormatterSpecs.
	 * Used to compose the QuantityFormatter.
	 *
	 * @param FormatterOptions $options
	 * @param WikibaseValueFormatterBuilders $builders
	 *
	 * @return QuantityFormatter
	 */
	protected static function newQuantityFormatter(
		FormatterOptions $options,
		WikibaseValueFormatterBuilders $builders
	) {
		//TODO: use a builder for this DecimalFormatter
		$language = Language::factory( $options->getOption( ValueFormatter::OPT_LANG ) );
		$localizer = new MediaWikiNumberLocalizer( $language );
		$decimalFormatter = new DecimalFormatter( $options, $localizer );
		return new QuantityFormatter( $decimalFormatter, $options );
	}

	/**
	 * Builder callback for use in WikibaseValueFormatterBuilders::$valueFormatterSpecs.
	 * Used to compose the GlobeCoordinateFormatter.
	 *
	 * @param FormatterOptions $options
	 * @param WikibaseValueFormatterBuilders $builders
	 *
	 * @return GlobeCoordinateFormatter
	 */
	protected static function newGlobeCoordinateFormatter(
		FormatterOptions $options,
		WikibaseValueFormatterBuilders $builders
	) {
		$options->setOption( GeoCoordinateFormatter::OPT_FORMAT, GeoCoordinateFormatter::TYPE_DMS );
		$options->setOption( GeoCoordinateFormatter::OPT_SPACING_LEVEL, array(
			GeoCoordinateFormatter::OPT_SPACE_LATLONG
		) );
		$options->setOption( GeoCoordinateFormatter::OPT_DIRECTIONAL, true );
		return new GlobeCoordinateFormatter( $options );
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
