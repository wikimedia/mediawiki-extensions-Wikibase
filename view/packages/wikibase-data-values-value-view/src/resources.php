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
				'jquery.valueview.valueview',
			),
		),

		'jquery.valueview.BifidExpert' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview.BifidExpert.js',
			),
			'dependencies' => array(
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
				'dataTypes',
				'dataValues.DataValue',
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

		// FIXME: Remove MediaWiki dependency
		// TODO: Move to jquery.ui.preview
		'jquery.valueview.preview' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview.preview.js',
			),
			'styles' => array(
				'themes/default/jquery.valueview.preview.css',
			),
			'dependencies' => array(
				'jquery.ui.widget',
				'mediawiki',
				'mediawiki.legacy.shared',
			),
			'messages' => array(
				'valueview-preview-label',
				'valueview-preview-novalue',
			),
		),

		// The actual valueview widget:
		'jquery.valueview.valueview' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview.valueview.js',
			),
			'styles' => array(
				'themes/default/jquery.valueview.valueview.css',
			),
			'dependencies' => array(
				'dataTypes',
				'dataValues.DataValue',
				'jquery.ui.widget',
				'jquery.valueview.ViewState',
				'jquery.valueview.ExpertFactory',
				'jquery.valueview.experts.UnsupportedValue',
				'jquery.valueview.experts.EmptyValue',
				// NOTE: Do not add additional experts here unless they are directly required by the
				// valueview widget. All experts are supposed to be loaded via the expert provider
				// passed to the widget.
				'util.Notifier',
			),
		),

		'jquery.valueview.ViewState' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview.ViewState.js',
			),
		),

		'mw.ext.valueView' => $moduleTemplate + array(
			'scripts' => array(
				'mw.ext.valueView.js',
			),
			'dependencies' => array(
				'dataTypes',
				'dataValues.values',
				'jquery.valueview',
				'jquery.valueview.experts.CommonsMediaType',
				'jquery.valueview.experts.GlobeCoordinateValue',
				'jquery.valueview.experts.QuantityType',
				'jquery.valueview.experts.StringValue',
				'jquery.valueview.experts.TimeValue',
				'jquery.valueview.experts.UrlType',
				'jquery.valueview.ExpertFactory',
				'mediawiki',
			),
		),

	);

	return $resources + include( __DIR__ . '/experts/resources.php' );

} );
