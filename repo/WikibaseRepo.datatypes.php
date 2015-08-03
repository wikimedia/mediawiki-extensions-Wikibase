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
				return $factory->buildStringValidators();
			},
		),
		'globe-coordinate' => array(
			'validator-factory-callback' => function () {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				return $factory->buildCoordinateValidators();
			},
		),
		'monolingualtext' => array(
			'validator-factory-callback' => function () {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				return $factory->buildMonolingualTextValidators();
			}
		),
		'quantity' => array(
			'validator-factory-callback' => function () {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				return $factory->buildQuantityValidators();
			},
		),
		'string' => array(
			'validator-factory-callback' => function () {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				return $factory->buildStringValidators();
			},
		),
		'time' => array(
			'validator-factory-callback' => function () {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				return $factory->buildTimeValidators();
			},
		),
		'url' => array(
			'validator-factory-callback' => function () {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				return $factory->buildUrlValidators();
			},
		),
		'wikibase-item' => array(
			'validator-factory-callback' => function () {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				return $factory->buildItemValidators();
			},
		),
		'wikibase-property' => array(
			'validator-factory-callback' => function () {
				$factory = WikibaseRepo::getDefaultValidatorBuilders();
				return $factory->buildPropertyValidators();
			},
		),
	);

});
