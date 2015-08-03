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
 * @note: 'validator-factory-callback' fields delegate to a global instance of
 * ValidatorsBuilders.
 *
 * @see ValidatorsBuilders
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */

use Wikibase\Repo\WikibaseRepo;

return call_user_func( function() {
	// NOTE: 'validator-factory-callback' callbacks act as glue between the high level interface
	// DataValueValidatorFactory and the low level factory for validators for well known data types,
	// the ValidatorBuilders class.
	// ValidatorBuilders should be used *only* here, program logic should use a
	// DataValueValidatorFactory as returned by WikibaseRepo::getDataTypeValidatorFactory().

	return array(
		'commonsMedia' => array(
			'validator-factory-callback' => function () {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				$factory->buildStringValidators();
			},
			'parser-factory-callback' => function ( $factory, $format, $options ) {
				$factory = WikibaseRepo::getDefaultWikibaseValueFormatterBuilders();
				$factory->getCommonsMediaFormatter( $format, $options );
			}
		),
		'globe-coordinate' => array(
			'validator-factory-callback' => function () {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				$factory->buildCoordinateValidators();
			},
			'parser-factory-callback' => function ( $factory, $format, $options ) {
				$factory = WikibaseRepo::getDefaultWikibaseValueFormatterBuilders();
				$factory->getGlobeCoordinateFormatter( $format, $options );
			}
		),
		'monolingualtext' => array(
			'validator-factory-callback' => function () {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				$factory->buildMonolingualTextValidators();
			'parser-factory-callback' => function ( $factory, $format, $options ) {
				$factory = WikibaseRepo::getDefaultWikibaseValueFormatterBuilders();
				$factory->getMonolingualTextFormatter( $format, $options );
			}
		),
		'quantity' => array(
			'validator-factory-callback' => function () {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				$factory->buildQuantityValidators();
			},
			'parser-factory-callback' => function ( $factory, $format, $options ) {
				$factory = WikibaseRepo::getDefaultWikibaseValueFormatterBuilders();
				$factory->getQuantityFormatter( $format, $options );
			}
		),
		'string' => array(
			'validator-factory-callback' => function () {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				$factory->buildStringValidators();
			},
			'parser-factory-callback' => function ( $factory, $format, $options ) {
				$factory = WikibaseRepo::getDefaultWikibaseValueFormatterBuilders();
				$factory->getStringFormatter( $format, $options );
			}
		),
		'time' => array(
			'validator-factory-callback' => function () {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				$factory->buildTimeValidators();
			},
			'parser-factory-callback' => function ( $factory, $format, $options ) {
				$factory = WikibaseRepo::getDefaultWikibaseValueFormatterBuilders();
				$factory->getTimeFormatter( $format, $options );
			}
		),
		'url' => array(
			'validator-factory-callback' => function () {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				$factory->buildUrlValidators();
			},
			'parser-factory-callback' => function ( $factory, $format, $options ) {
				$factory = WikibaseRepo::getDefaultWikibaseValueFormatterBuilders();
				$factory->getUrlFormatter( $format, $options );
			}
		),
		'wikibase-item' => array(
			'validator-factory-callback' => function () {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				$factory->buildItemValidators();
			},
			'parser-factory-callback' => function ( $factory, $format, $options ) {
				$factory = WikibaseRepo::getDefaultWikibaseValueFormatterBuilders();
				$factory->getEntityIdFormatter( $format, $options );
			}
		),
		'wikibase-property' => array(
			'validator-factory-callback' => function () {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				$factory->buildPropertyValidators();
			},
			'parser-factory-callback' => function ( $factory, $format, $options ) {
				$factory = WikibaseRepo::getDefaultWikibaseValueFormatterBuilders();
				$factory->getEntityIdFormatter( $format, $options );
			}
		),
	);

});
