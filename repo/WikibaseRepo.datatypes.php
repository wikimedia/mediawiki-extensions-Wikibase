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
use ValueParsers\ParserOptions;
use ValueParsers\QuantityParser;
use ValueParsers\StringParser;
use ValueParsers\ValueParser;
use Wikibase\Rdf\DedupeBag;
use Wikibase\Rdf\EntityMentionListener;
use Wikibase\Rdf\JulianDateTimeValueCleaner;
use Wikibase\Rdf\RdfVocabulary;
use Wikibase\Rdf\Values\CommonsMediaRdfBuilder;
use Wikibase\Rdf\Values\ComplexValueRdfHelper;
use Wikibase\Rdf\Values\EntityIdRdfBuilder;
use Wikibase\Rdf\Values\GlobeCoordinateRdfBuilder;
use Wikibase\Rdf\Values\LiteralValueRdfBuilder;
use Wikibase\Rdf\Values\MonolingualTextRdfBuilder;
use Wikibase\Rdf\Values\ObjectValueRdfBuilder;
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
		'commonsMedia' => array(
			'validator-factory-callback' => function() {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				return $factory->buildStringValidators();
			},
			'parser-factory-callback' => $newStringParser,
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				$factory = WikibaseRepo::getDefaultFormatterBuilders();
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
		'globe-coordinate' => array(
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
		'monolingualtext' => array(
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
		'quantity' => array(
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
		'string' => array(
			'validator-factory-callback' => function() {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				return $factory->buildStringValidators();
			},
			'parser-factory-callback' => $newStringParser,
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				return null; // rely on formatter for string value type
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
		'time' => array(
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
		'url' => array(
			'validator-factory-callback' => function() {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				return $factory->buildUrlValidators();
			},
			'parser-factory-callback' => $newStringParser,
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				$factory = WikibaseRepo::getDefaultFormatterBuilders();
				return $factory->newUrlFormatter( $format, $options );
			},
			'rdf-builder-factory-callback' => function (
				$mode,
				RdfVocabulary $vocab,
				RdfWriter $writer,
				EntityMentionListener $tracker,
				DedupeBag $dedupe
			) {
				return new ObjectValueRdfBuilder();
			},
		),
		'wikibase-item' => array(
			'validator-factory-callback' => function() {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				return $factory->buildItemValidators();
			},
			'parser-factory-callback' => $newEntityIdParser,
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				$factory = WikibaseRepo::getDefaultFormatterBuilders();
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
		'wikibase-property' => array(
			'validator-factory-callback' => function() {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				return $factory->buildPropertyValidators();
			},
			'parser-factory-callback' => $newEntityIdParser,
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				$factory = WikibaseRepo::getDefaultFormatterBuilders();
				return $factory->newEntityIdFormatter( $format, $options );
			},
			'rdf-builder-factory-callback' => function ( $mode, RdfVocabulary $vocab, RdfWriter $writer, EntityMentionListener $tracker, DedupeBag $dedupe ) {
				return new EntityIdRdfBuilder( $vocab, $tracker );
			},
		),
	);

} );
