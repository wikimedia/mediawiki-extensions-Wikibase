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

	$resources = array(

		// Loads the actual valueview widget into jQuery.valueview.valueview and maps
		// jQuery.valueview to jQuery.valueview.valueview without losing any properties.
		'jquery.valueview' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview.js',
			),
			'dependencies' => array(
				'jquery',
				'jquery.valueview.valueview',
			),
		),

		'jquery.valueview.BifidExpert' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview.BifidExpert.js',
			),
			'dependencies' => array(
				'jquery',
				'jquery.valueview.Expert',
				'util.inherit',
			),
		),

		'jquery.valueview.Expert' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview.Expert.js',
			),
			'dependencies' => array(
				'dataValues.DataValue',
				'jquery',
				'util.inherit',
				'util.MessageProvider',
				'util.Notifier',
			),
		),

		'jquery.valueview.ExpertFactory' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview.ExpertFactory.js',
			),
			'dependencies' => array(
				'jquery',
			),
		),

		'jquery.valueview.experts' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview.experts.js',
			),
			'dependencies' => array(
				'jquery',
			),
		),

		// The actual valueview widget:
		'jquery.valueview.valueview' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview.valueview.js',
			),
			'styles' => array(
				'jquery.valueview.valueview.css',
			),
			'dependencies' => array(
				'dataValues.DataValue',
				'jquery',
				'jquery.ui.widget',
				'jquery.valueview.ViewState',
				'jquery.valueview.ExpertFactory',
				'jquery.valueview.experts.EmptyValue',
				'jquery.valueview.experts.UnsupportedValue',
				'util.Notifier',
				'valueFormatters.ValueFormatterFactory',
				'valueParsers.ValueParserFactory',
			),
		),

		'jquery.valueview.ViewState' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview.ViewState.js',
			),
			'dependencies' => array(
				'jquery',
			),
		),

		'mw.ext.valueView' => $moduleTemplate + array(
			'scripts' => array(
				'mw.ext.valueView.js',
			),
			'dependencies' => array(
				'dataValues.values',
				'jquery.valueview',
				'jquery.valueview.experts.GlobeCoordinateValue',
				'jquery.valueview.experts.QuantityType',
				'jquery.valueview.experts.StringValue',
				'jquery.valueview.experts.TimeValue',
				'jquery.valueview.ExpertFactory',
				'mediawiki',
				'mw.ext.valueFormatters',
				'mw.ext.valueParsers',
			),
		),

	);

	return $resources + include( __DIR__ . '/experts/resources.php' );

} );
