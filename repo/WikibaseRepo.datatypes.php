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
 * ValidatorsBuilders.
 *
 * @see ValidatorsBuilders
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */

use DataValues\Geo\Parsers\GlobeCoordinateParser;
use ValueParsers\NullParser;
use ValueParsers\QuantityParser;
use ValueParsers\ValueParser;
use Wikibase\Lib\EntityIdValueParser;
use Wikibase\Lib\Parsers\TimeParserFactory;
use Wikibase\Parsers\MonolingualTextParser;
use Wikibase\Repo\WikibaseRepo;

return call_user_func( function() {
	// NOTE: 'validator-factory-callback' callbacks act as glue between the high level interface
	// DataValueValidatorFactory and the low level factory for validators for well known data types,
	// the ValidatorBuilders class.
	// ValidatorBuilders should be used *only* here, program logic should use a
	// DataValueValidatorFactory as returned by WikibaseRepo::getDataTypeValidatorFactory().

	$newEntityIdParser = function( ValueParsers\ParserOptions $options ) {
		$repo = WikibaseRepo::getDefaultInstance();
		return new EntityIdValueParser( $repo->getEntityIdParser() );
	};

	$newNullParser = function( ValueParsers\ParserOptions $options ) {
		return new NullParser();
	};

	return array(
		'commonsMedia' => array(
			'validator-factory-callback' => function () {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				return $factory->buildStringValidators();
			},
			'parser-factory-callback' => $newNullParser, //TODO: use StringParser

		),
		'globe-coordinate' => array(
			'validator-factory-callback' => function () {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				return $factory->buildCoordinateValidators();
			},
			'parser-factory-callback' => function( ValueParsers\ParserOptions $options ) {
				return new GlobeCoordinateParser( $options );
			}
		),
		'monolingualtext' => array(
			'validator-factory-callback' => function () {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				return $factory->buildMonolingualTextValidators();
			},
			'parser-factory-callback' => function( ValueParsers\ParserOptions $options ) {
				return new MonolingualTextParser( $options );
			}
		),
		'quantity' => array(
			'validator-factory-callback' => function () {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				return $factory->buildQuantityValidators();
			},
			'parser-factory-callback' => function( ValueParsers\ParserOptions $options ) {
				$language = Language::factory( $options->getOption( ValueParser::OPT_LANG ) );
				$unlocalizer = new Wikibase\Lib\MediaWikiNumberUnlocalizer( $language);
				return new QuantityParser( $options, $unlocalizer );
			},
		),
		'string' => array(
			'validator-factory-callback' => function () {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				return $factory->buildStringValidators();
			},
			'parser-factory-callback' => $newNullParser, //TODO: use StringParser
		),
		'time' => array(
			'validator-factory-callback' => function () {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				return $factory->buildTimeValidators();
			},
			'parser-factory-callback' => function( ValueParsers\ParserOptions $options ) {
				$factory = new TimeParserFactory( $options );
				return $factory->getTimeParser();
			},
		),
		'url' => array(
			'validator-factory-callback' => function () {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				return $factory->buildUrlValidators();
			},
			'parser-factory-callback' => $newNullParser, //TODO: use StringParser
		),
		'wikibase-item' => array(
			'validator-factory-callback' => function () {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				return $factory->buildItemValidators();
			},
			'parser-factory-callback' => $newEntityIdParser,
		),
		'wikibase-property' => array(
			'validator-factory-callback' => function () {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				return $factory->buildPropertyValidators();
			},
			'parser-factory-callback' => $newEntityIdParser,
		),
	);

});
