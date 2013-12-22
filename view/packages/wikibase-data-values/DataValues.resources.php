<?php
/**
 * Definition of "DataValues" resourceloader modules.
 * When included this returns an array with all modules introduced by the "DataValues" JavaScript
 * module.
 *
 * External dependencies:
 * - jQuery 1.8
 * - time.js
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
		'remoteExtPath' => '../vendor/data-values/javascript/resources',
	);

	return array(
		'dataValues' => $moduleTemplate + array(
			'scripts' => array(
				'dataValues.js',
			),
		),

		'dataValues.DataValue' => $moduleTemplate + array(
			'scripts' => array(
				'DataValue.js',
			),
			'dependencies' => array(
				'dataValues',
				'dataValues.util',
			),
		),

		'dataValues.values' => $moduleTemplate + array(
			'scripts' => array(
				// Note: The order here is relevant, scripts should be places after the ones they
				//  depend on.
				'values/BoolValue.js',
				'values/DecimalValue.js',
				'values/GlobeCoordinateValue.js',
				'values/MonolingualTextValue.js',
				'values/MultilingualTextValue.js',
				'values/StringValue.js',
				'values/NumberValue.js',
				'values/TimeValue.js',
				'values/QuantityValue.js',
				'values/UnknownValue.js',
				'values/UnUnserializableValue.js',
			),
			'dependencies' => array(
				'dataValues.DataValue',
				'globeCoordinate.js', // required by GlobeCoordinateValue
				'time.js' // required by TimeValue
			),
		),

		'dataValues.util' => $moduleTemplate + array(
			'scripts' => array(
				'dataValues.util.js',
				'dataValues.util.inherit.js',
				'dataValues.util.Notify.js',
			),
			'dependencies' => array(
				'dataValues',
			),
		),
	);

} );
// @codeCoverageIgnoreEnd
