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
class WikibaseSnakFormatterBuilders {

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
	 * used, this WikibaseSnakFormatterBuilders will be provided as an additional parameter
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
		SnakFormatterFactory::FORMAT_PLAIN => array(
			'VT:string' => 'ValueFormatters\StringFormatter',
			'VT:globecoordinate' => 'ValueFormatters\GlobeCoordinateFormatter',
			'VT:time' => 'Wikibase\Lib\MwTimeIsoFormatter',
			'VT:wikibase-entityid' => array( 'Wikibase\Lib\WikibaseSnakFormatterBuilders', 'newEntityIdFormatter' ),
		),

		// Formatters to use for wiki text output.
		// Falls back to plain text formatters (plus escaping).
		SnakFormatterFactory::FORMAT_WIKI => array(
			'PT:url' => 'ValueFormatters\StringFormatter', // no escaping!
			//'PT:wikibase-item' => 'Wikibase\Lib\LocalItemLinkFormatter', // TODO
		),

		// Formatters to use for HTML display.
		// Falls back to plain text formatters (plus escaping).
		SnakFormatterFactory::FORMAT_HTML => array(
			//'PT:url' => 'Wikibase\Lib\LinkFormatter', // TODO
			//'PT:commonsMedia' => 'Wikibase\Lib\CommonsLinkFormatter', // TODO
			//'PT:wikibase-item' => 'Wikibase\Lib\ItemLinkFormatter', // TODO
		),

		// Formatters to use for HTML widgets.
		// Falls back to HTML display formatters.
		SnakFormatterFactory::FORMAT_HTML_WIDGET => array(
		),
	);

	/**
	 * @param EntityLookup           $lookup
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
	 * @return array DataType builder specs
	 */
	public function getSnakFormatterBuildersForFormats() {
		$buildDispatchingSnakFormatter = array( $this, 'buildDispatchingSnakFormatter' );

		$types = array(
			SnakFormatterFactory::FORMAT_WIKI => $buildDispatchingSnakFormatter,
			SnakFormatterFactory::FORMAT_PLAIN => $buildDispatchingSnakFormatter,
			SnakFormatterFactory::FORMAT_HTML => $buildDispatchingSnakFormatter,
			SnakFormatterFactory::FORMAT_HTML_WIDGET => $buildDispatchingSnakFormatter,
		);

		return $types;
	}

	/**
	 * Returns a DispatchingSnakFormatter for the given format, that will dispatch based on
	 * the snak type. The instance returned by this method will cover all standard snak types.
	 *
	 * @param SnakFormatterFactory $factory
	 * @param string               $format
	 * @param FormatterOptions     $options
	 *
	 * @return DispatchingSnakFormatter
	 */
	public function buildDispatchingSnakFormatter( SnakFormatterFactory $factory, $format, FormatterOptions $options ) {
		$this->initLanguageDefaults( $options );
		$lang = $options->getOption( ValueFormatter::OPT_LANG );

		$noValueSnakFormatter = new MessageSnakFormatter( wfMessage( 'wikibase-snakview-snaktypeselector-novalue')->inLanguage( $lang ), $format );
		$someValueSnakFormatter = new MessageSnakFormatter( wfMessage( 'wikibase-snakview-snaktypeselector-somevalue' )->inLanguage( $lang ), $format );
		$valueSnakFormatter = $this->buildValueSnakFormatter( $factory, $format, $options );

		$formatters = array(
			'novalue' => $noValueSnakFormatter,
			'somevalue' => $someValueSnakFormatter,
			'value' => $valueSnakFormatter,
		);

		return new DispatchingSnakFormatter( $format, $formatters );
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

	public function buildValueSnakFormatter( SnakFormatterFactory $factory, $format, FormatterOptions $options ) {
		switch ( $format ) {
			case SnakFormatterFactory::FORMAT_PLAIN:
				$formatters = $this->getPlainTextFormatters( $options );
				break;
			case SnakFormatterFactory::FORMAT_WIKI:
				$formatters = $this->getWikiTextFormatters( $options );
				break;
			case SnakFormatterFactory::FORMAT_HTML:
				$formatters = $this->getHtmlFormatters( $options );
				break;
			case SnakFormatterFactory::FORMAT_HTML_WIDGET:
				$formatters = $this->getWidgetFormatters( $options );
				break;
			default:
				throw new \InvalidArgumentException( 'Unsupported format: ' . $format );
		}

		return new PropertyValueSnakFormatter( $format, $formatters, $this->propertyDataTypeLookup );
	}

	public function getPlainTextFormatters( $options, array $skip = array() ) {
		$plainFormatters = $this->buildDefinedFormatters( SnakFormatterFactory::FORMAT_PLAIN, $options, $skip );
		return $plainFormatters;
	}

	public function getWikiTextFormatters( $options, array $skip = array() ) {
		$wikiFormatters = $this->buildDefinedFormatters( SnakFormatterFactory::FORMAT_WIKI, $options );
		$plainFormatters = $this->getPlainTextFormatters( $options, array_merge( $skip, array_keys( $wikiFormatters ) ) );

		$wikiFormatters = array_merge(
			$wikiFormatters,
			$this->makeEscapingFormatters( $plainFormatters, 'wfEscapeWikiText' )
		);

		return $wikiFormatters;
	}

	public function getHtmlFormatters( $options, array $skip = array() ) {
		$htmlFormatters = $this->buildDefinedFormatters( SnakFormatterFactory::FORMAT_HTML, $options );
		$plainFormatters = $this->getPlainTextFormatters( $options, array_merge( $skip, array_keys( $htmlFormatters ) ) );

		$htmlFormatters = array_merge(
			$htmlFormatters,
			$this->makeEscapingFormatters( $plainFormatters, 'htmlspecialchars' )
		);

		return $htmlFormatters;
	}

	public function getWidgetFormatters( $options, array $skip = array() ) {
		$widgetFormatters = $this->buildDefinedFormatters( SnakFormatterFactory::FORMAT_HTML_WIDGET, $options );
		$htmlFormatters = $this->getHtmlFormatters( $options, array_merge( $skip, array_keys( $widgetFormatters ) ) );

		$widgetFormatters = array_merge(
			$widgetFormatters,
			$htmlFormatters
		);

		return $widgetFormatters;
	}

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

	protected function newFromSpec( $spec, $options ) {
		if ( is_string( $spec ) ) {
			$obj = new $spec( $options );
		} else {
			$obj = call_user_func( $spec, $options, $this );
		}

		return $obj;
	}

	protected static function newEntityIdFormatter( $options, $builders ) {
		return new EntityIdLabelFormatter( $options, $builders->entityLookup );
	}

	public function makeEscapingFormatters( array $formatters, $escape ) {
		$escapingFormatters = array();

		foreach ( $formatters as $key => $formatter ) {
			$escapingFormatters[$key] = new EscapingValueFormatter( $formatter, $escape );
		}

		return $escapingFormatters;
	}
}
