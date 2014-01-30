<?php
/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	$remoteExtPathParts = explode(
		DIRECTORY_SEPARATOR . 'extensions' . DIRECTORY_SEPARATOR, __DIR__, 2
	);

	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => $remoteExtPathParts[1],
	);

	$modules = array(

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
				'jquery',
				'util.inherit',
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
				'jquery',
				'time.js', // required by TimeValue
				'util.inherit',
			),
		),

		'mw.ext.dataValues' => $moduleTemplate + array(
			'scripts' => array(
				'mw.ext.dataValues.js',
			),
			'dependencies' => array(
				// load all values. TODO: this is bad but the system is not as advanced as ValueView yet.
				'dataValues.values',
				'jquery',
				'mediawiki',
				'time.js',
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

	);

	$modules = array_merge(
		$modules,
		include( __DIR__ . '/valueFormatters/resources.php' ),
		include( __DIR__ . '/valueParsers/resources.php' )
	);

	return $modules;

} );
