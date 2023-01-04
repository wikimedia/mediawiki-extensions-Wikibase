<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Formatters;

use InvalidArgumentException;
use Language;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\TermLanguageFallbackChain;
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
 * @license GPL-2.0-or-later
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
	public function __construct(
		array $factoryFunctions,
		Language $defaultLanguage,
		LanguageFallbackChainFactory $fallbackChainFactory
	) {
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
	public function setFormatterFactoryCallback( string $type, ?callable $factoryFunction ) {
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
	 * @todo this shouldn't be public at all. Perhaps factor it out into a helper class.
	 */
	public function applyLanguageDefaults( FormatterOptions $options ) {
		$options->defaultOption( ValueFormatter::OPT_LANG, $this->defaultLanguage->getCode() );

		$lang = $options->getOption( ValueFormatter::OPT_LANG );
		if ( !is_string( $lang ) ) {
			throw new InvalidArgumentException(
				'The value of OPT_LANG must be a language code. For a fallback chain, use OPT_LANGUAGE_FALLBACK_CHAIN.'
			);
		}

		$fallbackOption = FormatterLabelDescriptionLookupFactory::OPT_LANGUAGE_FALLBACK_CHAIN;
		if ( !$options->hasOption( $fallbackOption ) ) {
			$options->setOption(
				$fallbackOption,
				$this->languageFallbackChainFactory->newFromLanguageCode( $lang )
			);
		}

		if ( !( $options->getOption( $fallbackOption ) instanceof TermLanguageFallbackChain ) ) {
			throw new InvalidArgumentException( 'The value of OPT_LANGUAGE_FALLBACK_CHAIN must be '
				. 'an instance of TermLanguageFallbackChain.' );
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

		$formatters = $this->buildDefinedFormatters( $format, $options );

		return new DispatchingValueFormatter( $formatters );
	}

	/**
	 * Instantiates the formatters defined for the given format in
	 * WikibaseValueFormatterBuilders::$valueFormatterSpecs.
	 *
	 * @see WikibaseValueFormatterBuilders::$valueFormatterSpecs
	 *
	 * @param string $format One of the SnakFormatter::FORMAT_... constants.
	 * @param FormatterOptions $options
	 *
	 * @return callable[] A map from prefixed type IDs to ValueFormatter factories.
	 */
	private function buildDefinedFormatters( $format, FormatterOptions $options ) {
		$formatters = [];

		foreach ( $this->factoryFunctions as $type => $func ) {
			$formatters[$type] = fn() => $func( $format, $options );
		}

		return $formatters;
	}

}
