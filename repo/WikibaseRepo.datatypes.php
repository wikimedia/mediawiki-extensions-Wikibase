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
 * @see docs/datatypes.wiki Documentation on how to add new data type
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */

use DataValues\Geo\Parsers\GlobeCoordinateParser;
use DataValues\StringValue;
use DataValues\UnboundedQuantityValue;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use ValueFormatters\FormatterOptions;
use ValueParsers\ParserOptions;
use ValueParsers\QuantityParser;
use ValueParsers\StringParser;
use ValueParsers\ValueParser;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\Lib\Formatters\ControlledFallbackEntityIdFormatter;
use Wikibase\Lib\Formatters\SnakFormat;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\Lib\Store\FieldPropertyInfoProvider;
use Wikibase\Lib\Store\PropertyInfoStore;
use Wikibase\Rdf\DedupeBag;
use Wikibase\Rdf\EntityMentionListener;
use Wikibase\Rdf\JulianDateTimeValueCleaner;
use Wikibase\Rdf\PropertyRdfBuilder;
use Wikibase\Rdf\RdfProducer;
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
use Wikibase\Repo\Rdf\Values\GeoShapeRdfBuilder;
use Wikibase\Repo\Rdf\Values\TabularDataRdfBuilder;
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

	return [
		'VT:bad' => [
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				$factory = WikibaseRepo::getDefaultValueFormatterBuilders();
				return $factory->newUnDeserializableValueFormatter( $format, $options );
			}
		],
		'PT:commonsMedia' => [
			'expert-module' => 'jquery.valueview.experts.CommonsMediaType',
			'validator-factory-callback' => function() {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				// Don't go for commons during unit tests.
				return $factory->buildMediaValidators(
					defined( 'MW_PHPUNIT_TEST' ) ? 'doNotCheckExistence' : 'checkExistence'
				);
			},
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				$factory = WikibaseRepo::getDefaultValueFormatterBuilders();
				return $factory->newCommonsMediaFormatter( $format, $options );
			},
			'rdf-builder-factory-callback' => function (
				$flags,
				RdfVocabulary $vocab,
				RdfWriter $writer,
				EntityMentionListener $tracker,
				DedupeBag $dedupe
			) {
				return new CommonsMediaRdfBuilder( $vocab );
			},
			'rdf-data-type' => PropertyRdfBuilder::OBJECT_PROPERTY,
		],
		'PT:geo-shape' => [
			'expert-module' => 'jquery.valueview.experts.GeoShape',
			'validator-factory-callback' => function() {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				// Don't go for commons during unit tests.
				return $factory->buildGeoShapeValidators(
					defined( 'MW_PHPUNIT_TEST' ) ? 'doNotCheckExistence' : 'checkExistence'
				);
			},
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				$factory = WikibaseRepo::getDefaultValueFormatterBuilders();
				return $factory->newGeoShapeFormatter( $format, $options );
			},
			'rdf-builder-factory-callback' => function (
				$flags,
				RdfVocabulary $vocab,
				RdfWriter $writer,
				EntityMentionListener $tracker,
				DedupeBag $dedupe
			) {
				return new GeoShapeRdfBuilder( $vocab );
			},
			'rdf-data-type' => PropertyRdfBuilder::OBJECT_PROPERTY,
		],
		'PT:tabular-data' => [
			'expert-module' => 'jquery.valueview.experts.TabularData',
			'validator-factory-callback' => function() {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				// Don't go for commons during unit tests.
				return $factory->buildTabularDataValidators(
					defined( 'MW_PHPUNIT_TEST' ) ? 'doNotCheckExistence' : 'checkExistence'
				);
			},
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				$factory = WikibaseRepo::getDefaultValueFormatterBuilders();
				return $factory->newTabularDataFormatter( $format, $options );
			},
			'rdf-builder-factory-callback' => function (
				$flags,
				RdfVocabulary $vocab,
				RdfWriter $writer,
				EntityMentionListener $tracker,
				DedupeBag $dedupe
			) {
				return new TabularDataRdfBuilder( $vocab );
			},
			'rdf-data-type' => PropertyRdfBuilder::OBJECT_PROPERTY,
		],
		'PT:entity-schema' => [
			'validator-factory-callback' => function() {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				// Don't go for commons during unit tests.
				return $factory->buildEntitySchemaValidators();
			},
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				$factory = WikibaseRepo::getDefaultValueFormatterBuilders();
				return $factory->newEntitySchemaFormatter( $format, $options );
			},
			'rdf-builder-factory-callback' => function (
				$flags,
				RdfVocabulary $vocab,
				RdfWriter $writer,
				EntityMentionListener $tracker,
				DedupeBag $dedupe
			) {
				return new LiteralValueRdfBuilder( null, null );
			},
		],
		'VT:globecoordinate' => [
			'expert-module' => 'jquery.valueview.experts.GlobeCoordinateInput',
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
				$flags,
				RdfVocabulary $vocab,
				RdfWriter $writer,
				EntityMentionListener $tracker,
				DedupeBag $dedupe
			) {
				$complexValueHelper = ( $flags & RdfProducer::PRODUCE_FULL_VALUES ) ?
					new ComplexValueRdfHelper( $vocab, $writer->sub(), $dedupe ) : null;
				return new GlobeCoordinateRdfBuilder( $complexValueHelper );
			},
		],
		'VT:monolingualtext' => [
			'expert-module' => 'jquery.valueview.experts.MonolingualText',
			'validator-factory-callback' => function() {
				$constraints = WikibaseRepo::getDefaultInstance()->getSettings()
					->getSetting( 'string-limits' )['VT:monolingualtext'];
				$maxLength = $constraints['length'];
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				return $factory->buildMonolingualTextValidators( $maxLength );
			},
			'parser-factory-callback' => function( ParserOptions $options ) {
				return new MonolingualTextParser( $options );
			},
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				$factory = WikibaseRepo::getDefaultValueFormatterBuilders();
				return $factory->newMonolingualFormatter( $format );
			},
			'rdf-builder-factory-callback' => function (
				$flags,
				RdfVocabulary $vocab,
				RdfWriter $writer,
				EntityMentionListener $tracker,
				DedupeBag $dedupe
			) {
				return new MonolingualTextRdfBuilder();
			},
		],
		'VT:quantity' => [
			'expert-module' => 'jquery.valueview.experts.QuantityInput',
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
				$flags,
				RdfVocabulary $vocab,
				RdfWriter $writer,
				EntityMentionListener $tracker,
				DedupeBag $dedupe
			) {
				$complexValueHelper = ( $flags & RdfProducer::PRODUCE_FULL_VALUES ) ?
					new ComplexValueRdfHelper( $vocab, $writer->sub(), $dedupe ) : null;
				$unitConverter = ( $flags & RdfProducer::PRODUCE_NORMALIZED_VALUES ) ?
					WikibaseRepo::getDefaultInstance()->getUnitConverter() : null;
				return new QuantityRdfBuilder( $complexValueHelper, $unitConverter );
			},
			'search-index-data-formatter-callback' => function ( UnboundedQuantityValue $value ) {
				return (string)round( $value->getAmount()->getValueFloat() );
			},
		],
		'VT:string' => [
			'expert-module' => 'jquery.valueview.experts.StringValue',
			'validator-factory-callback' => function() {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				$constraints = WikibaseRepo::getDefaultInstance()->getSettings()
					->getSetting( 'string-limits' )['VT:string'];
				$maxLength = $constraints['length'];
				return $factory->buildStringValidators( $maxLength );
			},
			'parser-factory-callback' => function ( ParserOptions $options ) {
				$normalizer = WikibaseRepo::getDefaultInstance()->getStringNormalizer();
				return new StringParser( new WikibaseStringValueNormalizer( $normalizer ) );
			},
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				$factory = WikibaseRepo::getDefaultValueFormatterBuilders();
				return $factory->newStringFormatter( $format, $options );
			},
			'rdf-builder-factory-callback' => function (
				$flags,
				RdfVocabulary $vocab,
				RdfWriter $writer,
				EntityMentionListener $tracker,
				DedupeBag $dedupe
			) {
				return new LiteralValueRdfBuilder( null, null );
			},
			'search-index-data-formatter-callback' => function ( StringValue $value ) {
				return $value->getValue();
			},
		],
		'VT:time' => [
			'expert-module' => 'jquery.valueview.experts.TimeInput',
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
				$flags,
				RdfVocabulary $vocab,
				RdfWriter $writer,
				EntityMentionListener $tracker,
				DedupeBag $dedupe
			) {
				// TODO: if data is fixed to be always Gregorian, replace with DateTimeValueCleaner
				$dateCleaner = new JulianDateTimeValueCleaner();
				$complexValueHelper = ( $flags & RdfProducer::PRODUCE_FULL_VALUES ) ?
					new ComplexValueRdfHelper( $vocab, $writer->sub(), $dedupe ) : null;
				return new TimeRdfBuilder( $dateCleaner, $complexValueHelper );
			},
		],
		'PT:url' => [
			'validator-factory-callback' => function() {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				$constraints = WikibaseRepo::getDefaultInstance()->getSettings()
					->getSetting( 'string-limits' )['PT:url'];
				$maxLength = $constraints['length'];
				return $factory->buildUrlValidators( $maxLength );
			},
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				$factory = WikibaseRepo::getDefaultValueFormatterBuilders();
				return $factory->newUrlFormatter( $format, $options );
			},
			'rdf-builder-factory-callback' => function (
				$flags,
				RdfVocabulary $vocab,
				RdfWriter $writer,
				EntityMentionListener $tracker,
				DedupeBag $dedupe
			) {
				return new ObjectUriRdfBuilder();
			},
			'rdf-data-type' => PropertyRdfBuilder::OBJECT_PROPERTY,
		],
		'PT:external-id' => [
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
					$repo->getStore()->getPropertyInfoLookup(),
					PropertyInfoStore::KEY_CANONICAL_URI
				);
				return new ExternalIdentifierRdfBuilder( $vocab, $uriPatternProvider );
			},
		],
		'VT:wikibase-entityid' => [
			'validator-factory-callback' => function() {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				return $factory->buildEntityValidators();
			},
			'parser-factory-callback' => function ( ParserOptions $options ) {
				$entityIdParser = WikibaseRepo::getDefaultInstance()->getEntityIdParser();
				return new EntityIdValueParser( $entityIdParser );
			},
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				$factory = WikibaseRepo::getDefaultValueFormatterBuilders();
				return $factory->newEntityIdFormatter( $format, $options );
			},
			'rdf-builder-factory-callback' => function (
				$flags,
				RdfVocabulary $vocab,
				RdfWriter $writer,
				EntityMentionListener $tracker,
				DedupeBag $dedupe
			) {
				return new EntityIdRdfBuilder( $vocab, $tracker );
			},
			'search-index-data-formatter-callback' => function ( EntityIdValue $value ) {
				return $value->getEntityId()->getSerialization();
			},
		],
		'PT:wikibase-item' => [
			'expert-module' => 'wikibase.experts.Item',
			'validator-factory-callback' => function() {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				return $factory->buildItemValidators();
			},
			'formatter-factory-callback' => function ( $format, FormatterOptions $options ) {
				$factory = WikibaseRepo::getDefaultValueFormatterBuilders();
				$snakFormat = new SnakFormat();

				if ( $snakFormat->getBaseFormat( $format ) === SnakFormatter::FORMAT_HTML ) {
					//TODO Cleanup the code once https://phabricator.wikimedia.org/T196882 is Done.

					$logger = LoggerFactory::getInstance( 'Wikibase.NewItemIdFormatter' );
					try {
						$statsdDataFactory = MediaWikiServices::getInstance()
							->getStatsdDataFactory();

						$formatter = new ControlledFallbackEntityIdFormatter(
							$factory->newItemPropertyIdHtmlLinkFormatter( $options ),
							$factory->newLabelsProviderEntityIdHtmlLinkFormatter( $options ),
							$statsdDataFactory,
							'wikibase.repo.wb_terms.newItemIdFormatter.'
						);

						$formatter->setLogger( $logger );

						return new \Wikibase\Lib\Formatters\EntityIdValueFormatter( $formatter );
					} catch ( \Exception $e ) {
						$logger->error(
							"Failed to construct ItemIdHtmlLinkFormatter: {exception_message}",
							[
								'exception' => $e,
							]
						);

						return $factory->newEntityIdFormatter( $format, $options );
					}
				}

				return $factory->newEntityIdFormatter( $format, $options );
			},
			'rdf-data-type' => PropertyRdfBuilder::OBJECT_PROPERTY,
		],
		'PT:wikibase-property' => [
			'expert-module' => 'wikibase.experts.Property',
			'validator-factory-callback' => function() {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				return $factory->buildPropertyValidators();
			},
			'formatter-factory-callback' => function ( $format, FormatterOptions $options ) {
				$factory = WikibaseRepo::getDefaultValueFormatterBuilders();
				$snakFormat = new SnakFormat();

				if ( $snakFormat->getBaseFormat( $format ) === SnakFormatter::FORMAT_HTML ) {
					//TODO Cleanup the code once https://phabricator.wikimedia.org/T196882 is Done.

					$logger = LoggerFactory::getInstance( 'Wikibase.NewPropertyIdFormatter' );
					try {
						$statsdDataFactory = MediaWikiServices::getInstance()
							->getStatsdDataFactory();

						$formatter = new ControlledFallbackEntityIdFormatter(
							$factory->newItemPropertyIdHtmlLinkFormatter( $options ),
							$factory->newLabelsProviderEntityIdHtmlLinkFormatter( $options ),
							$statsdDataFactory,
							'wikibase.repo.wb_terms.newPropertyIdFormatter.'
						);

						$formatter->setLogger( $logger );

						return new \Wikibase\Lib\Formatters\EntityIdValueFormatter( $formatter );
					} catch ( \Exception $e ) {
						$logger->error(
							"Failed to construct ItemPropertyIdHtmlLinkFormatter: {exception_message}",
							[
								'exception' => $e,
							]
						);

						return $factory->newEntityIdFormatter( $format, $options );
					}
				}

				return $factory->newEntityIdFormatter( $format, $options );
			},
			'rdf-data-type' => PropertyRdfBuilder::OBJECT_PROPERTY,
		]
	];
} );
