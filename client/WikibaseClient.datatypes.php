<?php
/**
 * Definition of data types for use with Wikibase.
 * The array returned by the code below is supposed to be merged into $wgWBClientDataTypes.
 * It defines the formatters used by the client to display data values of different types.
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

use ValueFormatters\FormatterOptions;
use Wikibase\Client\WikibaseClient;

return call_user_func( function() {
	// NOTE: 'formatter-factory-callback' callbacks act as glue between the high level interface
	// OutputFormatValueFormatterFactory and the low level factory for validators for well
	// known data types, the WikibaseValueFormatterBuilders class.
	// WikibaseValueFormatterBuilders should be used *only* here, program logic should use a
	// OutputFormatValueFormatterFactory as returned by WikibaseClient::getValueFormatterFactory().

	// NOTE: Factory callbacks are registered below by value type (using the prefix "VT:") or by
	// property data type (prefix "PT:").

	return array(
		'VT:bad' => array(
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				$factory = WikibaseClient::getDefaultValueFormatterBuilders();
				return $factory->newUnDeserializableValueFormatter( $format, $options );
			}
		),
		'VT:globecoordinate' => array(
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				$factory = WikibaseClient::getDefaultValueFormatterBuilders();
				return $factory->newGlobeCoordinateFormatter( $format, $options );
			},
		),
		'VT:monolingualtext' => array(
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				$factory = WikibaseClient::getDefaultValueFormatterBuilders();
				return $factory->newMonolingualFormatter( $format, $options );
			},
		),
		'VT:quantity' => array(
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				$factory = WikibaseClient::getDefaultValueFormatterBuilders();
				return $factory->newQuantityFormatter( $format, $options );
			},
		),
		'VT:string' => array(
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				$factory = WikibaseClient::getDefaultValueFormatterBuilders();
				return $factory->newStringFormatter( $format, $options );
			},
		),
		'PT:url' => array(
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				$factory = WikibaseClient::getDefaultValueFormatterBuilders();
				return $factory->newUrlFormatter( $format, $options );
			},
		),
		'PT:commonsMedia' => array(
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				$factory = WikibaseClient::getDefaultValueFormatterBuilders();
				return $factory->newCommonsMediaFormatter( $format, $options );
			},
		),
		'VT:time' => array(
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				$factory = WikibaseClient::getDefaultValueFormatterBuilders();
				return $factory->newTimeFormatter( $format, $options );
			},
		),
		'VT:wikibase-entityid' => array(
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				$factory = WikibaseClient::getDefaultValueFormatterBuilders();
				return $factory->newEntityIdFormatter( $format, $options );
			},
		),
		'PT:external-id' => array(
			'snak-formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				$factory = WikibaseClient::getDefaultSnakFormatterBuilders();
				return $factory->newExternalIdentifierFormatter( $format, $options );
			},
		),
	);

} );
