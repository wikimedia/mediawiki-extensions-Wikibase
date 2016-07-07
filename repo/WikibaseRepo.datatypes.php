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
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */

use DataValues\Geo\Parsers\GlobeCoordinateParser;
use ValueFormatters\FormatterOptions;
use ValueParsers\ParserOptions;
use ValueParsers\QuantityParser;
use ValueParsers\StringParser;
use ValueParsers\ValueParser;
use Wikibase\Lib\FieldPropertyInfoProvider;
use Wikibase\PropertyInfoStore;
use Wikibase\Rdf\DedupeBag;
use Wikibase\Rdf\EntityMentionListener;
use Wikibase\Rdf\JulianDateTimeValueCleaner;
use Wikibase\Rdf\RdfVocabulary;
use Wikibase\Rdf\Values\CommonsMediaRdfBuilder;
use Wikibase\Rdf\Values\ComplexValueRdfHelper;
use Wikibase\Rdf\Values\EntityIdRdfBuilder;
use Wikibase\Rdf\Values\ExternalIdentifierRdfBuilder;
use Wikibase\Rdf\Values\GlobeCoordinateRdfBuilder;
use Wikibase\Rdf\Values\LiteralValueRdfBuilder;
use Wikibase\Rdf\Values\MonolingualTextRdfBuilder;
use Wikibase\Rdf\Values\ObjectUriRdfBuilder;
use Wikibase\Rdf\Values\QuantityRdfBuilder;
use Wikibase\Rdf\Values\TimeRdfBuilder;
use Wikibase\Repo\Parsers\EntityIdValueParser;
use Wikibase\Repo\Parsers\MediaWikiNumberUnlocalizer;
use Wikibase\Repo\Parsers\MonolingualTextParser;
use Wikibase\Repo\Parsers\TimeParserFactory;
use Wikibase\Repo\Parsers\WikibaseStringValueNormalizer;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Purtle\RdfWriter;

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
				$factory = WikibaseRepo::getDefaultValueFormatterBuilders();
				return $factory->newUnDeserializableValueFormatter( $format, $options );
			}
		),
		'PT:commonsMedia' => array(
			'validator-factory-callback' => function() {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				// Don't go for commons during unit tests.
				return $factory->buildMediaValidators(
					defined( 'MW_PHPUNIT_TEST' ) ? 'doNotCheckExistence' : 'checkExistence'
				);
			},
			'parser-factory-callback' => $newStringParser,
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				$factory = WikibaseRepo::getDefaultValueFormatterBuilders();
				return $factory->newCommonsMediaFormatter( $format, $options );
			},
			'rdf-builder-factory-callback' => function (
				$mode,
				RdfVocabulary $vocab,
				RdfWriter $writer,
				EntityMentionListener $tracker,
				DedupeBag $dedupe
			) {
				return new CommonsMediaRdfBuilder( $vocab );
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
				$factory = WikibaseRepo::getDefaultValueFormatterBuilders();
				return $factory->newGlobeCoordinateFormatter( $format, $options );
			},
			'rdf-builder-factory-callback' => function (
				$mode,
				RdfVocabulary $vocab,
				RdfWriter $writer,
				EntityMentionListener $tracker,
				DedupeBag $dedupe
			) {
				$complexValueHelper = $mode === 'simple' ? null : new ComplexValueRdfHelper( $vocab, $writer->sub(), $dedupe );
				return new GlobeCoordinateRdfBuilder( $complexValueHelper );
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
				$factory = WikibaseRepo::getDefaultValueFormatterBuilders();
				return $factory->newMonolingualFormatter( $format, $options );
			},
			'rdf-builder-factory-callback' => function (
				$mode,
				RdfVocabulary $vocab,
				RdfWriter $writer,
				EntityMentionListener $tracker,
				DedupeBag $dedupe
			) {
				return new MonolingualTextRdfBuilder();
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
				$factory = WikibaseRepo::getDefaultValueFormatterBuilders();
				return $factory->newQuantityFormatter( $format, $options );
			},
			'rdf-builder-factory-callback' => function (
				$mode,
				RdfVocabulary $vocab,
				RdfWriter $writer,
				EntityMentionListener $tracker,
				DedupeBag $dedupe
			) {
				$complexValueHelper = $mode === 'simple' ? null : new ComplexValueRdfHelper( $vocab, $writer->sub(), $dedupe );
				return new QuantityRdfBuilder( $complexValueHelper );
			},
		),
		'VT:string' => array(
			'validator-factory-callback' => function() {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				return $factory->buildStringValidators();
			},
			'parser-factory-callback' => $newStringParser,
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				$factory = WikibaseRepo::getDefaultValueFormatterBuilders();
				return $factory->newStringFormatter( $format, $options );
			},
			'rdf-builder-factory-callback' => function (
				$mode,
				RdfVocabulary $vocab,
				RdfWriter $writer,
				EntityMentionListener $tracker,
				DedupeBag $dedupe
			) {
				return new LiteralValueRdfBuilder( null, null );
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
				$factory = WikibaseRepo::getDefaultValueFormatterBuilders();
				return $factory->newTimeFormatter( $format, $options );
			},
			'rdf-builder-factory-callback' => function (
				$mode,
				RdfVocabulary $vocab,
				RdfWriter $writer,
				EntityMentionListener $tracker,
				DedupeBag $dedupe
			) {
				// TODO: if data is fixed to be always Gregorian, replace with DateTimeValueCleaner
				$dateCleaner = new JulianDateTimeValueCleaner();
				$complexValueHelper = $mode === 'simple' ? null : new ComplexValueRdfHelper( $vocab, $writer->sub(), $dedupe );
				return new TimeRdfBuilder( $dateCleaner, $complexValueHelper );
			},
		),
		'PT:url' => array(
			'validator-factory-callback' => function() {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				return $factory->buildUrlValidators();
			},
			'parser-factory-callback' => $newStringParser,
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				$factory = WikibaseRepo::getDefaultValueFormatterBuilders();
				return $factory->newUrlFormatter( $format, $options );
			},
			'rdf-builder-factory-callback' => function (
				$mode,
				RdfVocabulary $vocab,
				RdfWriter $writer,
				EntityMentionListener $tracker,
				DedupeBag $dedupe
			) {
				return new ObjectUriRdfBuilder();
			},
		),
		'PT:external-id' => array(
			'validator-factory-callback' => function() {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				return $factory->buildStringValidators();
			},
			'parser-factory-callback' => $newStringParser,
			// NOTE: for 'formatter-factory-callback', we fall back to plain text formatting
			'snak-formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				$factory = WikibaseRepo::getDefaultSnakFormatterBuilders();
				return $factory->newExternalIdentifierFormatter( $format, $options );
			},
			'rdf-builder-factory-callback' => function (
				$mode,
				RdfVocabulary $vocab,
				RdfWriter $writer,
				EntityMentionListener $tracker,
				DedupeBag $dedupe
			) {
				$repo = WikibaseRepo::getDefaultInstance();
				$uriPatternProvider = new FieldPropertyInfoProvider(
					$repo->getStore()->getPropertyInfoStore(),
					PropertyInfoStore::KEY_FORMATTER_URL
				);
				return new ExternalIdentifierRdfBuilder( $uriPatternProvider );
			},
		),
		'VT:wikibase-entityid' => array(
			'validator-factory-callback' => function() {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				return $factory->buildEntityValidators();
			},
			'parser-factory-callback' => $newEntityIdParser,
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				$factory = WikibaseRepo::getDefaultValueFormatterBuilders();
				return $factory->newEntityIdFormatter( $format, $options );
			},
			'rdf-builder-factory-callback' => function (
				$mode,
				RdfVocabulary $vocab,
				RdfWriter $writer,
				EntityMentionListener $tracker,
				DedupeBag $dedupe
			) {
				return new EntityIdRdfBuilder( $vocab, $tracker );
			},
		),
		'PT:wikibase-item' => array(
			'validator-factory-callback' => function() {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				return $factory->buildItemValidators();
			},
		),
		'PT:wikibase-property' => array(
			'validator-factory-callback' => function() {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				return $factory->buildPropertyValidators();
			},
		)
	);

} );
