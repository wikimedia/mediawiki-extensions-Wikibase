<?php

namespace Wikibase\Lib;

use InvalidArgumentException;
use Language;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageFallbackChainFactory;
use Wikimedia\Assert\Assert;

/**
 * Factory for ValueFormatters, based on factory callbacks.
 *
 * This class provides a mapping between factory callbacks organized by data type
 * to ValueFormatters by target type. This reflects the fact that formatters for a single data type
 * are typically defined by code that has knowledge about that specific data type, while
 * ValueFormatters are typically used by code that doesn't know anything about specific data types,
 * but requires a specific output format.
 *
 * This class implements a fallback mechanism for target formats, that allows some target formats
 * to stand in for others (with escaping applies if necessary). E.g. if there is not HTML formatter
 * defined for a data type, the plain text formatter plus HTML escaping would be used.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class OutputFormatValueFormatterFactory {

	/**
	 * @var callable[]
	 */
	private $factoryFunctions;

	/**
	 * @var Language
	 */
	private $defaultLanguage;

	/**
	 * @var LanguageFallbackChainFactory
	 */
	private $languageFallbackChainFactory;

	/**
	 * @param callable[] $factoryFunctions An associative array mapping types to factory
	 * functions. Type names must use the "PT:" prefix for property types (data types),
	 * and "VT:" for value types, to be compatible with the convention used by
	 * DispatchingValueFormatter.
	 * The factory functions will be called with two parameters, the desired target
	 * type (see the SnakFormatter::FORMAT_XXX constants) and a FormatterOptions object.
	 * The factory function must return an instance of ValueFormatter suitable for the given target
	 * format, or null if no formatter for the requested target format is known.
	 *
	 * @param Language $defaultLanguage
	 * @param LanguageFallbackChainFactory $fallbackChainFactory
	 */
	public function __construct( array $factoryFunctions, Language $defaultLanguage, LanguageFallbackChainFactory $fallbackChainFactory ) {
		Assert::parameterElementType( 'callable', $factoryFunctions, '$factoryFunctions' );

		$this->factoryFunctions = $factoryFunctions;
		$this->defaultLanguage = $defaultLanguage;
		$this->languageFallbackChainFactory = $fallbackChainFactory;
	}

	/**
	 * @param string $type The data type or value type to register the formatter factory for.
	 *        Use the "PT:" prefix for data types and "VT:" for value types.
	 * @param callable|null $factoryFunction The factory method. Will be called with two parameters,
	 *        a string indicating the desired target format, and a FormatterOptions object. The
	 *        callback must return a ValueFormatter suitable for emitting the given output format,
	 *        or null.
	 */
	public function setFormatterFactoryCallback( $type, $factoryFunction ) {
		Assert::parameterType( 'string', $type, '$type' );
		Assert::parameterType( 'callable|null', $factoryFunction, '$factoryFunction' );

		if ( $factoryFunction === null ) {
			unset( $this->factoryFunctions[$type] );
		} else {
			$this->factoryFunctions[$type] = $factoryFunction;
		}
	}

	/**
	 * Initializes the options keys ValueFormatter::OPT_LANG and
	 * FormatterLabelDescriptionLookupFactory::OPT_LANGUAGE_FALLBACK_CHAIN if they are not yet set.
	 *
	 * @param FormatterOptions $options The options to modify.
	 *
	 * @throws InvalidArgumentException
	 * @todo  : Sort out how the desired language is specified. We have two language options,
	 *        each accepting different ways of specifying the language. That's not good.
	 * @todo: this shouldn't be public at all. Perhaps factor it out into a helper class.
	 */
	public function applyLanguageDefaults( FormatterOptions $options ) {
		if ( !$options->hasOption( ValueFormatter::OPT_LANG ) ) {
			$options->setOption( ValueFormatter::OPT_LANG, $this->defaultLanguage->getCode() );
		}

		$lang = $options->getOption( ValueFormatter::OPT_LANG );
		if ( !is_string( $lang ) ) {
			throw new InvalidArgumentException(
				'The value of OPT_LANG must be a language code. For a fallback chain, use OPT_LANGUAGE_FALLBACK_CHAIN.'
			);
		}

		if ( !$options->hasOption( FormatterLabelDescriptionLookupFactory::OPT_LANGUAGE_FALLBACK_CHAIN ) ) {
			$fallbackMode = (
				LanguageFallbackChainFactory::FALLBACK_VARIANTS
				| LanguageFallbackChainFactory::FALLBACK_OTHERS
				| LanguageFallbackChainFactory::FALLBACK_SELF );

			$options->setOption(
				FormatterLabelDescriptionLookupFactory::OPT_LANGUAGE_FALLBACK_CHAIN,
				$this->languageFallbackChainFactory->newFromLanguageCode( $lang, $fallbackMode )
			);
		}

		if ( !( $options->getOption( FormatterLabelDescriptionLookupFactory::OPT_LANGUAGE_FALLBACK_CHAIN ) instanceof LanguageFallbackChain ) ) {
			throw new InvalidArgumentException( 'The value of OPT_LANGUAGE_FALLBACK_CHAIN must be an instance of LanguageFallbackChain.' );
		}
	}

	/**
	 * Returns a ValueFormatter for the given format, suitable for formatting DataValues
	 * of any any type supported by the formatter factory functions supplied to the constructor.
	 *
	 * @param string $format
	 * @param FormatterOptions $options
	 *
	 * @throws InvalidArgumentException
	 * @return DispatchingValueFormatter
	 */
	public function getValueFormatter( $format, FormatterOptions $options ) {
		$this->applyLanguageDefaults( $options );

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
	private function getPlainTextFormatters( FormatterOptions $options, array $skip = array() ) {
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
	private function getWikiTextFormatters( FormatterOptions $options, array $skip = array() ) {
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
	private function getHtmlFormatters( FormatterOptions $options, array $skip = array() ) {
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
	private function getWidgetFormatters( FormatterOptions $options, array $skip = array() ) {
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
	private function getDiffFormatters( FormatterOptions $options, array $skip = array() ) {
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
	 * @param string[] $skip A list of types to be skipped. Useful when the caller already
	 *        has formatters for some types.
	 *
	 * @return ValueFormatter[] A map from prefixed type IDs to ValueFormatter instances.
	 */
	private function buildDefinedFormatters( $format, FormatterOptions $options, array $skip = array() ) {
		$formatters = array();

		foreach ( $this->factoryFunctions as $type => $func ) {
			if ( $skip && in_array( $type, $skip ) ) {
				continue;
			}

			$formatter = call_user_func( $func, $format, $options );

			if ( $formatter ) {
				$formatters[$type] = $formatter;
			}
		}

		return $formatters;
	}

	/**
	 * Wrap each entry in a list of formatters in an EscapingValueFormatter.
	 * This is useful to apply escaping to the output of a set of formatters,
	 * allowing them to be used for a different format.
	 *
	 * @param ValueFormatter[] $formatters
	 * @param string $escape The escape callback, e.g. 'htmlspecialchars' or 'wfEscapeWikitext'.
	 *
	 * @return ValueFormatter[]
	 */
	private function makeEscapingFormatters( array $formatters, $escape ) {
		$escapingFormatters = array();

		foreach ( $formatters as $key => $formatter ) {
			$escapingFormatters[$key] = new EscapingValueFormatter( $formatter, $escape );
		}

		return $escapingFormatters;
	}

}
