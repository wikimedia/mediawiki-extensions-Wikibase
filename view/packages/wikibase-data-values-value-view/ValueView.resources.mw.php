<?php
/**
 * Definition of "ValueView" resourceloader modules.
 * When included this returns an array with all modules introduced by the "valueview" jQuery
 * extension.
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {

	$remoteExtPathParts = explode( DIRECTORY_SEPARATOR . 'extensions' . DIRECTORY_SEPARATOR , __DIR__, 2 );
	$moduleTemplate = array(
		'localBasePath' => __DIR__ . '/resources',
		'remoteExtPath' =>  $remoteExtPathParts[1] . '/resources',
	);

	$mwVvResources = array(
		'mw.ext.valueView' => $moduleTemplate + array(
			'scripts' => array(
				'mw.ext.valueView.js',
			),
			'dependencies' => array(
				'dataTypes',
				'dataValues.values',
				'jquery.valueview',
				'jquery.valueview.experts.commonsmediatype',
				'jquery.valueview.experts.globecoordinatevalue',
				'jquery.valueview.experts.quantitytype',
				'jquery.valueview.experts.stringvalue',
				'jquery.valueview.experts.timevalue',
				'jquery.valueview.experts.urltype',
			),
		),

		'jquery.valueview.experts.commonsmediatype' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview/valueview.experts/experts.SuggestedStringValue.js',
				'jquery.valueview/valueview.experts/experts.CommonsMediaType.js',
			),
			'dependencies' => array(
				'jquery.valueview.experts.staticdom',
				'jquery.valueview.BifidExpert',
				'jquery.valueview.experts.stringvalue',
				'jquery.ui.suggester',
				'mediawiki.util'
			),
		),

		// Dependencies required by jQuery.valueview library:
		'jquery.PurposedCallbacks' => $moduleTemplate + array(
			'scripts' => array(
				'jquery/jquery.PurposedCallbacks.js',
			),
			'dependencies' => array(
				'jquery',
			),
		),

		'jquery.animateWithEvent' => $moduleTemplate + array(
			'scripts' => array(
				'jquery/jquery.AnimationEvent.js',
				'jquery/jquery.animateWithEvent.js',
			),
			'dependencies' => array(
				'jquery.PurposedCallbacks',
			),
		),

		'jquery.nativeEventHandler' => $moduleTemplate + array(
			'scripts' => array(
				'jquery/jquery.nativeEventHandler.js',
			)
		),

		'jquery.eachchange' => $moduleTemplate + array(
			'scripts' => array(
				'jquery/jquery.eachchange.js'
			),
			'dependencies' => array(
				'jquery.client'
			)
		),

		'jquery.inputAutoExpand' => $moduleTemplate + array(
			'scripts' => array(
				'jquery/jquery.inputAutoExpand.js',
			),
			'dependencies' => array(
				'jquery.eachchange'
			)
		),

		'jquery.fn.focusAt' => $moduleTemplate + array(
			'scripts' => array(
				'jquery/jquery.fn.focusAt.js',
			),
			'dependencies' => array(
				'jquery',
			),
		),

		'jquery.ui.suggester' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.ui/jquery.ui.suggester.js'
			),
			'styles' => array(
				'jquery.ui/jquery.ui.suggester.css'
			),
			'dependencies' => array(
				'jquery.autocompletestring',
				'jquery.ui.autocomplete',
				'jquery.ui.widget',
				'jquery.util.adaptlettercase',
				'jquery.util.getscrollbarwidth',
			)
		),

		'jquery.time.timeinput' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.time/jquery.time.timeinput.js',
			),
			'dependencies' => array(
				'jquery.ui.widget',
				'jquery.eachchange',
				'time.js'
			),
		),

		'jquery.ui.inputextender' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.ui/jquery.ui.inputextender.js',
			),
			'styles' => array(
				'jquery.ui/jquery.ui.inputextender.css',
			),
			'dependencies' => array(
				'jquery.eachchange',
				'jquery.ui.position',
				'jquery.ui.widget',
				'jquery.animateWithEvent',
			),
		),

		'jquery.ui.toggler' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.ui/jquery.ui.toggler.js',
			),
			'styles' => array(
				'jquery.ui/themes/default/jquery.ui.toggler.css',
			),
			'dependencies' => array(
				'jquery.ui.widget',
				'jquery.animateWithEvent',
			),
		),

		'jquery.ui.listrotator' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.ui/jquery.ui.listrotator.js',
			),
			'styles' => array(
				'jquery.ui/jquery.ui.listrotator.css',
			),
			'dependencies' => array(
				'jquery.ui.widget',
				'jquery.ui.position',
				'jquery.ui.autocomplete', // needs jquery.ui.menu
			),
			'messages' => array(
				'valueview-listrotator-auto',
			),
		),

		'jquery.autocompletestring' => $moduleTemplate + array(
			'scripts' => array(
				'jquery/jquery.autocompletestring.js',
			),
			'dependencies' => array(
				'jquery.util.adaptlettercase',
			),
		),

		'jquery.util.adaptlettercase' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.util/jquery.util.adaptlettercase.js',
			),
		),

		'jquery.util.getscrollbarwidth' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.util/jquery.util.getscrollbarwidth.js',
			),
		),

	);

	// return jQuery.valueview's native resources plus those required by the MW extension:
	return $mwVvResources + include( __DIR__ . '/ValueView.resources.php' );
} );
// @codeCoverageIgnoreEnd
