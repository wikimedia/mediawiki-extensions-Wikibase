<?php
/**
 * @license GPL-2.0-or-later
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	$moduleTemplate = [
		'localBasePath' => __DIR__ . '/../../../wikibase-data-values/src',
		'remoteExtPath' => 'Wikibase/view/lib/wikibase-data-values/src',
	];

	$modules = [
		'dataValues' => $moduleTemplate + [
				'scripts' => [
					'dataValues.js',
				],
			],

		'dataValues.DataValue' => $moduleTemplate + [
				'scripts' => [
					'DataValue.js',
				],
				'dependencies' => [
					'dataValues',
					'util.inherit',
				],
			],

		'dataValues.values' => $moduleTemplate + [
				'scripts' => [
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
					'values/UnDeserializableValue.js',
				],
				'dependencies' => [
					'dataValues.DataValue',
					'dataValues.TimeValue',
					'globeCoordinate.js', // required by GlobeCoordinateValue
					'util.inherit',
				],
			],

		'dataValues.TimeValue' => $moduleTemplate + [
				'scripts' => [
					'values/TimeValue.js',
				],
				'dependencies' => [
					'dataValues.DataValue',
					'util.inherit',
				],
			],

	];

	$modules = array_merge(
		$modules,
		include __DIR__ . '/valueFormatters/resources.php',
		include __DIR__ . '/valueParsers/resources.php'
	);

	return $modules;
} );
