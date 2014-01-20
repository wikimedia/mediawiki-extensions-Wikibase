<?php
/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {

	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => '..' . substr( __DIR__, strlen( $GLOBALS['IP'] ) ),
	);

	return array(

		'dataValues.tests' => $moduleTemplate + array(
			'scripts' => array(
				'dataValues.tests.js',
			),
			'dependencies' => array(
				'dataValues',
			),
		),

		'dataValues.DataValue.tests' => $moduleTemplate + array(
			'scripts' => array(
				'dataValues.DataValue.tests.js',
			),
			'dependencies' => array(
				'dataValues.DataValue',
			),
		),

		'dataValues.values.tests' => $moduleTemplate + array(
			'scripts' => array(
				'values/BoolValue.tests.js',
				'values/DecimalValue.tests.js',
				'values/GlobeCoordinateValue.tests.js',
				'values/MonolingualTextValue.tests.js',
				'values/MultilingualTextValue.tests.js',
				'values/StringValue.tests.js',
				'values/NumberValue.tests.js',
				'values/TimeValue.tests.js',
				'values/QuantityValue.tests.js',
				'values/UnknownValue.tests.js',
				'values/UnUnserializableValue.tests.js',
			),
			'dependencies' => array(
				'dataValues.DataValue.tests',
				'dataValues.values',
				'util.inherit',
			),
		),

	);

} );
