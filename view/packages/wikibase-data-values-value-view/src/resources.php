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

		'jquery.valueview.Expert' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview.Expert.js',
			),
			'dependencies' => array(
				'jquery',
				'util.inherit',
				'util.MessageProvider',
				'util.Notifier',
			),
		),

		'jquery.valueview.ExpertStore' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview.ExpertStore.js',
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
				'jquery.valueview.ExpertStore',
				'jquery.valueview.experts.EmptyValue',
				'jquery.valueview.experts.UnsupportedValue',
				'util.Notifier',
				'valueFormatters.ValueFormatterStore',
				'valueParsers.ValueParserStore',
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

	);

	return $resources + include( __DIR__ . '/experts/resources.php' );

} );
