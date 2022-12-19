<?php

/**
 * Definition of data types for use with Wikibase.
 * The array returned by the code below is supposed to be merged into the Repo data types.
 * It defines the formatters used by the repo to display data values of different types.
 *
 * @note: Keep in sync with lib/WikibaseLib.datatypes.php
 *
 * @note This is bootstrap code, it is executed for EVERY request.
 * Avoid instantiating objects here!
 *
 * @note: 'validator-factory-callback' fields delegate to a global instance of
 * ValidatorsBuilders
 *
 * @note: 'formatter-factory-callback' fields delegate to a global instance of
 * WikibaseValueFormatterBuilders.
 *
 * @see ValidatorsBuilders
 * @see WikibaseValueFormatterBuilders
 * @see @ref docs_topics_datatypes Documentation on how to add new data type
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
use Wikibase\Lib\Formatters\EntityIdValueFormatter;
use Wikibase\Lib\Formatters\SnakFormat;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\Lib\Store\FieldPropertyInfoProvider;
use Wikibase\Lib\Store\PropertyInfoStore;
use Wikibase\Repo\Parsers\EntityIdValueParser;
use Wikibase\Repo\Parsers\MediaWikiNumberUnlocalizer;
use Wikibase\Repo\Parsers\MonolingualTextParser;
use Wikibase\Repo\Parsers\TimeParserFactory;
use Wikibase\Repo\Parsers\WikibaseStringValueNormalizer;
use Wikibase\Repo\Rdf\DedupeBag;
use Wikibase\Repo\Rdf\EntityMentionListener;
use Wikibase\Repo\Rdf\JulianDateTimeValueCleaner;
use Wikibase\Repo\Rdf\PropertySpecificComponentsRdfBuilder;
use Wikibase\Repo\Rdf\RdfProducer;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikibase\Repo\Rdf\Values\CommonsMediaRdfBuilder;
use Wikibase\Repo\Rdf\Values\ComplexValueRdfHelper;
use Wikibase\Repo\Rdf\Values\EntityIdRdfBuilder;
use Wikibase\Repo\Rdf\Values\ExternalIdentifierRdfBuilder;
use Wikibase\Repo\Rdf\Values\GeoShapeRdfBuilder;
use Wikibase\Repo\Rdf\Values\GlobeCoordinateRdfBuilder;
use Wikibase\Repo\Rdf\Values\LiteralValueRdfBuilder;
use Wikibase\Repo\Rdf\Values\MonolingualTextRdfBuilder;
use Wikibase\Repo\Rdf\Values\ObjectUriRdfBuilder;
use Wikibase\Repo\Rdf\Values\QuantityRdfBuilder;
use Wikibase\Repo\Rdf\Values\TabularDataRdfBuilder;
use Wikibase\Repo\Rdf\Values\TimeRdfBuilder;
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
			},
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
			'rdf-data-type' => function() {
				return PropertySpecificComponentsRdfBuilder::OBJECT_PROPERTY;
			},
			'normalizer-factory-callback' => static function () {
				if ( defined( 'MW_PHPUNIT_TEST' ) ) {
					// Don't go for commons during unit tests.
					return [];
				}
				return WikibaseRepo::getCommonsMediaValueNormalizer();
			},
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
			'formatter-factory-callback' => function( $format ) {
				$factory = WikibaseRepo::getDefaultValueFormatterBuilders();
				return $factory->newGeoShapeFormatter( $format );
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
			'rdf-data-type' => function() {
				return PropertySpecificComponentsRdfBuilder::OBJECT_PROPERTY;
			},
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
			'rdf-data-type' => function() {
				return PropertySpecificComponentsRdfBuilder::OBJECT_PROPERTY;
			},
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
				$constraints = WikibaseRepo::getSettings()
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
				$language = MediaWikiServices::getInstance()->getLanguageFactory()
					->getLanguage( $options->getOption( ValueParser::OPT_LANG ) );
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
					WikibaseRepo::getUnitConverter() : null;
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
				$constraints = WikibaseRepo::getSettings()
					->getSetting( 'string-limits' )['VT:string'];
				$maxLength = $constraints['length'];
				// max length is also used in MetaDataBridgeConfig, make sure to keep in sync
				return $factory->buildStringValidators( $maxLength );
			},
			'parser-factory-callback' => function ( ParserOptions $options ) {
				$normalizer = WikibaseRepo::getStringNormalizer();
				return new StringParser( new WikibaseStringValueNormalizer( $normalizer ) );
			},
			'formatter-factory-callback' => function( $format ) {
				$factory = WikibaseRepo::getDefaultValueFormatterBuilders();
				return $factory->newStringFormatter( $format );
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
			'normalizer-factory-callback' => static function () {
				return WikibaseRepo::getStringValueNormalizer();
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
				$constraints = WikibaseRepo::getSettings()
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
			'rdf-data-type' => function() {
				return PropertySpecificComponentsRdfBuilder::OBJECT_PROPERTY;
			},
		],
		'PT:external-id' => [
			// NOTE: for 'formatter-factory-callback', we fall back to plain text formatting
			'snak-formatter-factory-callback' => function( $format ) {
				$factory = WikibaseRepo::getDefaultSnakFormatterBuilders();
				return $factory->newExternalIdentifierFormatter( $format );
			},
			'rdf-builder-factory-callback' => function (
				$mode,
				RdfVocabulary $vocab,
				RdfWriter $writer,
				EntityMentionListener $tracker,
				DedupeBag $dedupe
			) {
				$uriPatternProvider = new FieldPropertyInfoProvider(
					WikibaseRepo::getStore()->getPropertyInfoLookup(),
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
				$entityIdParser = WikibaseRepo::getEntityIdParser();
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
					$logger = LoggerFactory::getInstance( 'Wikibase' );
					try {
						return new EntityIdValueFormatter( $factory->newItemIdHtmlLinkFormatter( $options ) );
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
			'rdf-data-type' => function() {
				return PropertySpecificComponentsRdfBuilder::OBJECT_PROPERTY;
			},
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
					$logger = LoggerFactory::getInstance( 'Wikibase.NewPropertyIdFormatter' );
					try {
						return new EntityIdValueFormatter( $factory->newPropertyIdHtmlLinkFormatter( $options ) );
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
			'rdf-data-type' => function() {
				return PropertySpecificComponentsRdfBuilder::OBJECT_PROPERTY;
			},
		],
	];
} );
