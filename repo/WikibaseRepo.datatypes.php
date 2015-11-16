<?php
/**
 * Definition of data types for use with Wikibase.
 * The array returned by the code below is supposed to be merged into $wgWBRepoDataTypes.
 * It defines the formatters used by the repo to display data values of different types.
 *
 * @note: Keep in sync with lib/WikibaseLib.datatypes.php
 *
 * @note: This is bootstrap code, it is executed for EVERY request. Avoid instantiating
 * objects or loading classes here!
 *
 * @note: 'validator-factory-callback' fields delegate to a global instance of
 * ValidatorsBuilders
 *
 * @note: 'formatter-factory-callback' fields delegate to a global instance of
 * WikibaseValueFormatterBuilders.
 *
 * @see ValidatorsBuilders
 * @see WikibaseValueFormatterBuilders
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */

use DataValues\Geo\Parsers\GlobeCoordinateParser;
use ValueFormatters\FormatterOptions;
use ValueFormatters\StringFormatter;
use ValueParsers\ParserOptions;
use ValueParsers\QuantityParser;
use ValueParsers\StringParser;
use ValueParsers\ValueParser;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\UnDeserializableValueFormatter;
use Wikibase\Repo\Parsers\EntityIdValueParser;
use Wikibase\Repo\Parsers\MediaWikiNumberUnlocalizer;
use Wikibase\Repo\Parsers\MonolingualTextParser;
use Wikibase\Repo\Parsers\TimeParserFactory;
use Wikibase\Repo\Parsers\WikibaseStringValueNormalizer;
use Wikibase\Repo\WikibaseRepo;

return call_user_func( function() {
	// NOTE: 'validator-factory-callback' callbacks act as glue between the high level interface
	// DataValueValidatorFactory and the low level factory for validators for well known data types,
	// the ValidatorBuilders class.
	// ValidatorBuilders should be used *only* here, program logic should use a
	// DataValueValidatorFactory as returned by WikibaseRepo::getDataTypeValidatorFactory().

	// NOTE: 'formatter-factory-callback' callbacks act as glue between the high level interface
	// OutputFormatValueFormatterFactory and the low level factory for validators for well
	// known data types, the WikibaseValueFormatterBuilders class.
	// WikibaseValueFormatterBuilders should be used *only* here, program logic should use a
	// OutputFormatValueFormatterFactory as returned by WikibaseRepo::getValueFormatterFactory().

	// NOTE: Factory callbacks are registered below by value type (using the prefix "VT:") or by
	// property data type (prefix "PT:").

	$newEntityIdParser = function( ParserOptions $options ) {
		$repo = WikibaseRepo::getDefaultInstance();
		return new EntityIdValueParser( $repo->getEntityIdParser() );
	};

	$newStringParser = function( ParserOptions $options ) {
		$repo = WikibaseRepo::getDefaultInstance();
		$normalizer = new WikibaseStringValueNormalizer( $repo->getStringNormalizer() );
		return new StringParser( $normalizer );
	};

	return array(
		'VT:bad' => array(
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				return $format === SnakFormatter::FORMAT_PLAIN ? new UnDeserializableValueFormatter( $options ) : null;
			}
		),
		'PT:commonsMedia' => array(
			'validator-factory-callback' => function() {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				return $factory->buildStringValidators();
			},
			'parser-factory-callback' => $newStringParser,
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				$factory = WikibaseRepo::getDefaultFormatterBuilders();
				return $factory->newCommonsMediaFormatter( $format, $options );
			},
		),
		'VT:globecoordinate' => array(
			'validator-factory-callback' => function() {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				return $factory->buildCoordinateValidators();
			},
			'parser-factory-callback' => function( ParserOptions $options ) {
				return new GlobeCoordinateParser( $options );
			},
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				$factory = WikibaseRepo::getDefaultFormatterBuilders();
				return $factory->newGlobeCoordinateFormatter( $format, $options );
			},
		),
		'VT:monolingualtext' => array(
			'validator-factory-callback' => function() {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				return $factory->buildMonolingualTextValidators();
			},
			'parser-factory-callback' => function( ParserOptions $options ) {
				return new MonolingualTextParser( $options );
			},
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				$factory = WikibaseRepo::getDefaultFormatterBuilders();
				return $factory->newMonolingualFormatter( $format, $options );
			},
		),
		'VT:quantity' => array(
			'validator-factory-callback' => function() {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				return $factory->buildQuantityValidators();
			},
			'parser-factory-callback' => function( ParserOptions $options ) {
				$language = Language::factory( $options->getOption( ValueParser::OPT_LANG ) );
				$unlocalizer = new MediaWikiNumberUnlocalizer( $language );
				return new QuantityParser( $options, $unlocalizer );
			},
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				$factory = WikibaseRepo::getDefaultFormatterBuilders();
				return $factory->newQuantityFormatter( $format, $options );
			},
		),
		'VT:string' => array(
			'validator-factory-callback' => function() {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				return $factory->buildStringValidators();
			},
			'parser-factory-callback' => $newStringParser,
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				return $format === SnakFormatter::FORMAT_PLAIN ? new StringFormatter( $options ) : null;
			},
		),
		'VT:time' => array(
			'validator-factory-callback' => function() {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				return $factory->buildTimeValidators();
			},
			'parser-factory-callback' => function( ParserOptions $options ) {
				$factory = new TimeParserFactory( $options );
				return $factory->getTimeParser();
			},
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				$factory = WikibaseRepo::getDefaultFormatterBuilders();
				return $factory->newTimeFormatter( $format, $options );
			},
		),
		'PT:url' => array(
			'validator-factory-callback' => function() {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				return $factory->buildUrlValidators();
			},
			'parser-factory-callback' => $newStringParser,
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				$factory = WikibaseRepo::getDefaultFormatterBuilders();
				return $factory->newUrlFormatter( $format, $options );
			},
		),
		'VT:wikibase-entityId' => array(
			'validator-factory-callback' => function() {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				return $factory->buildItemValidators();
			},
			'parser-factory-callback' => $newEntityIdParser,
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				$factory = WikibaseRepo::getDefaultFormatterBuilders();
				return $factory->newEntityIdFormatter( $format, $options );
			},
		),
	);

} );
