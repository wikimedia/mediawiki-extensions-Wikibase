<?php
/**
 * Definition of data types for use with Wikibase.
 * The array returned by the code below is supposed to be merged into $wgWikibaseDataTypes.
 * It defines the formatters used by the repo to display data values of different types.
 *
 * @note: Keep in sync with lib/WikibaseLib.datatypes.php
 *
 * @note: This is bootstrap code, it is executed for EVERY request. Avoid instantiating
 * objects or loading classes here!
 *
 * @note: 'parser-factory-callback' fields delegate to a global instance of
 * WikibaseFormatterBuilders.
 *
 * @see WikibaseFormatterBuilders
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
	// NOTE: 'formatter-factory-callback' callbacks act as glue between the high level
	// ValueFormatter factory (OutputFormatValueFormatterFactory) and the low level factory
	// for formatters for well known data types (WikibaseValueFormatterBuilders).
	// WikibaseValueFormatterBuilders should be used *only* here, program logic should use a
	// OutputFormatValueFormatterFactory as returned by WikibaseRepo::getValueFormatterFactory().

	$newEntityIdParser = function( ValueParsers\ParserOptions $options ) {
		$repo = WikibaseRepo::getDefaultInstance();
		return new EntityIdValueParser( $repo->getEntityIdParser() );
	};

	$newNullParser = function( ValueParsers\ParserOptions $options ) {
		return new NullParser();
	};

	return array(
		'commonsMedia' => array(
			'parser-factory-callback' => $newNullParser, //TODO: use StringParser
		),
		'globe-coordinate' => array(
			'parser-factory-callback' => function( ValueParsers\ParserOptions $options ) {
				return new GlobeCoordinateParser( $options );
			}
		),
		'monolingualtext' => array(
			'parser-factory-callback' => function( ValueParsers\ParserOptions $options ) {
				return new MonolingualTextParser( $options );
			}
		),
		'quantity' => array(
			'parser-factory-callback' => function( ValueParsers\ParserOptions $options ) {
				$language = Language::factory( $options->getOption( ValueParser::OPT_LANG ) );
				$unlocalizer = new Wikibase\Lib\MediaWikiNumberUnlocalizer( $language);
				return new QuantityParser( $options, $unlocalizer );
			},
		),
		'string' => array(
			'parser-factory-callback' => $newNullParser, //TODO: use StringParser
		),
		'time' => array(
			'parser-factory-callback' => function( ValueParsers\ParserOptions $options ) {
				$factory = new TimeParserFactory( $options );
				return $factory->getTimeParser();
			},
		),
		'url' => array(
			'parser-factory-callback' => $newNullParser, //TODO: use StringParser
		),
		'wikibase-item' => array(
			'parser-factory-callback' => $newEntityIdParser,
		),
		'wikibase-property' => array(
			'parser-factory-callback' => $newEntityIdParser,
		),
	);

});
