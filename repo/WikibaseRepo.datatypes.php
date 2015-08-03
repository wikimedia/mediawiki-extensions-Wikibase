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

use Wikibase\Repo\WikibaseRepo;

return call_user_func( function() {
	// NOTE: 'parser-factory-callback' callbacks act as glue between the high level
	// ValueFormatter factory (OutputFormatValueFormatterFactory) and the low level factory
	// for formatters for well known data types (WikibaseValueFormatterBuilders).
	// WikibaseValueFormatterBuilders should be used *only* here, program logic should use a
	// OutputFormatValueFormatterFactory as returned by WikibaseRepo::getValueFormatterFactory().

	return array(
		'commonsMedia' => array(
			'parser-factory-callback' => function ( $factory, $format, $options ) {
				$factory = WikibaseRepo::getDefaultWikibaseValueFormatterBuilders();
				$factory->getCommonsMediaFormatter( $format, $options );
			}
		),
		'globe-coordinate' => array(
			'parser-factory-callback' => function ( $factory, $format, $options ) {
				$factory = WikibaseRepo::getDefaultWikibaseValueFormatterBuilders();
				$factory->getGlobeCoordinateFormatter( $format, $options );
			}
		),
		'monolingualtext' => array(
			'parser-factory-callback' => function ( $factory, $format, $options ) {
				$factory = WikibaseRepo::getDefaultWikibaseValueFormatterBuilders();
				$factory->getMonolingualTextFormatter( $format, $options );
			}
		),
		'quantity' => array(
			'parser-factory-callback' => function ( $factory, $format, $options ) {
				$factory = WikibaseRepo::getDefaultWikibaseValueFormatterBuilders();
				$factory->getQuantityFormatter( $format, $options );
			}
		),
		'string' => array(
			'parser-factory-callback' => function ( $factory, $format, $options ) {
				$factory = WikibaseRepo::getDefaultWikibaseValueFormatterBuilders();
				$factory->getStringFormatter( $format, $options );
			}
		),
		'time' => array(
			'parser-factory-callback' => function ( $factory, $format, $options ) {
				$factory = WikibaseRepo::getDefaultWikibaseValueFormatterBuilders();
				$factory->getTimeFormatter( $format, $options );
			}
		),
		'url' => array(
			'parser-factory-callback' => function ( $factory, $format, $options ) {
				$factory = WikibaseRepo::getDefaultWikibaseValueFormatterBuilders();
				$factory->getUrlFormatter( $format, $options );
			}
		),
		'wikibase-item' => array(
			'parser-factory-callback' => function ( $factory, $format, $options ) {
				$factory = WikibaseRepo::getDefaultWikibaseValueFormatterBuilders();
				$factory->getEntityIdFormatter( $format, $options );
			}
		),
		'wikibase-property' => array(
			'parser-factory-callback' => function ( $factory, $format, $options ) {
				$factory = WikibaseRepo::getDefaultWikibaseValueFormatterBuilders();
				$factory->getEntityIdFormatter( $format, $options );
			}
		),
	);

});
