<?php
/**
 * Definition of "DataValues" resourceloader modules.
 * When included this returns an array with all the modules introduced by "DataValues" extension.
 *
 * @since 0.1
 *
 * @file
 * @ingroup DataValues
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {

	$moduleTemplate = array(
		'localBasePath' => __DIR__ . '/resources',
		'remoteExtPath' =>  '../vendor/data-values/javascript/resources',
	);

	$mwVvResources = array(
		'mw.ext.dataValues' => $moduleTemplate + array(
			'scripts' => array(
				'mw.ext.dataValues.js',
			),
			'dependencies' => array(
				// load all values. TODO: this is bad but the system is not as advanced as ValueView yet.
				'dataValues.values'
			),
			'messages' => array(
				'jan', 'january',
				'feb', 'february',
				'mar', 'march',
				'apr', 'april',
				'may', 'may_long',
				'jun', 'june',
				'jul', 'july',
				'aug', 'august',
				'sep', 'september',
				'oct', 'october',
				'nov', 'november',
				'dec', 'december',
			)
		),

		// Dependencies required by "DataValues" library:

		// globeCoordinate.js
		'globeCoordinate.js' => $moduleTemplate + array(
			'scripts' => array(
				'globeCoordinate.js/src/globeCoordinate.js',
				'globeCoordinate.js/src/globeCoordinate.Formatter.js',
				'globeCoordinate.js/src/globeCoordinate.GlobeCoordinate.js',
			),
		),

		// time.js
		'time.js' => $moduleTemplate + array(
			'scripts' => array(
				'time.js/src/time.js',
				'time.js/src/time.Time.js',
				'time.js/src/time.Time.validate.js',
				'time.js/src/time.Parser.js',
			),
			'dependencies' => array(
				'jquery',
			),
		),

		'time.js.validTimeDefinitions' => $moduleTemplate + array(
			'scripts' => array(
				'time.js/tests/time.validTimeDefinitions.js', // example times for testing purposes
			),
			'dependencies' => array(
				'time.js',
			),
		),

		// qunit-parameterize from https://github.com/AStepaniuk/qunit-parameterize
		'qunit.parameterize' => $moduleTemplate + array(
			'scripts' => array(
				'qunit.parameterize/qunit.parameterize.js',
			),
			'dependencies' => array(
				'jquery.qunit',
			),
		),
	);

	// return "DataValue" module's native resources plus those required by the MW extension:
	return $mwVvResources + include( __DIR__ . '/DataValues.resources.php' );
} );
// @codeCoverageIgnoreEnd
