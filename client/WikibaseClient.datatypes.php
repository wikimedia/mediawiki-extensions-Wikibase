<?php
/**
 * Definition of data types for use with Wikibase.
 * The array returned by the code below is supposed to be merged into the Client data types.
 * It defines the formatters used by the client to display data values of different types.
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
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */

use ValueFormatters\FormatterOptions;
use Wikibase\Client\WikibaseClient;
use Wikibase\Lib\Formatters\UnmappedEntityIdValueFormatter;

return call_user_func( function() {
	// NOTE: 'formatter-factory-callback' callbacks act as glue between the high level interface
	// OutputFormatValueFormatterFactory and the low level factory for validators for well
	// known data types, the WikibaseValueFormatterBuilders class.
	// WikibaseValueFormatterBuilders should be used *only* here, program logic should use a
	// OutputFormatValueFormatterFactory as returned by WikibaseClient::getValueFormatterFactory().

	// NOTE: Factory callbacks are registered below by value type (using the prefix "VT:") or by
	// property data type (prefix "PT:").

	return [
		'VT:bad' => [
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				$factory = WikibaseClient::getDefaultValueFormatterBuilders();
				return $factory->newUnDeserializableValueFormatter( $format, $options );
			},
		],
		'VT:globecoordinate' => [
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				$factory = WikibaseClient::getDefaultValueFormatterBuilders();
				return $factory->newGlobeCoordinateFormatter( $format, $options );
			},
		],
		'VT:monolingualtext' => [
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				$factory = WikibaseClient::getDefaultValueFormatterBuilders();
				return $factory->newMonolingualFormatter( $format );
			},
		],
		'VT:quantity' => [
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				$factory = WikibaseClient::getDefaultValueFormatterBuilders();
				return $factory->newQuantityFormatter( $format, $options );
			},
		],
		'VT:string' => [
			'formatter-factory-callback' => function( $format ) {
				$factory = WikibaseClient::getDefaultValueFormatterBuilders();
				return $factory->newStringFormatter( $format );
			},
		],
		'PT:url' => [
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				$factory = WikibaseClient::getDefaultValueFormatterBuilders();
				return $factory->newUrlFormatter( $format, $options );
			},
		],
		'PT:commonsMedia' => [
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				$factory = WikibaseClient::getDefaultValueFormatterBuilders();
				return $factory->newCommonsMediaFormatter( $format, $options );
			},
		],
		'PT:geo-shape' => [
			'formatter-factory-callback' => function( $format ) {
				$factory = WikibaseClient::getDefaultValueFormatterBuilders();
				return $factory->newGeoShapeFormatter( $format );
			},
		],
		'PT:tabular-data' => [
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				$factory = WikibaseClient::getDefaultValueFormatterBuilders();
				return $factory->newTabularDataFormatter( $format, $options );
			},
		],
		'VT:time' => [
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				$factory = WikibaseClient::getDefaultValueFormatterBuilders();
				return $factory->newTimeFormatter( $format, $options );
			},
		],
		'VT:wikibase-entityid' => [
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				$factory = WikibaseClient::getDefaultValueFormatterBuilders();
				return $factory->newEntityIdFormatter( $format, $options );
			},
		],
		'VT:wikibase-unmapped-entityid' => [
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				return new UnmappedEntityIdValueFormatter();
			},
		],
		'PT:external-id' => [
			'snak-formatter-factory-callback' => function( $format ) {
				$factory = WikibaseClient::getDefaultSnakFormatterBuilders();
				return $factory->newExternalIdentifierFormatter( $format );
			},
		],
	];
} );
