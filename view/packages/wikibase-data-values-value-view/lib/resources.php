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

	return array(

		'jquery.animateWithEvent' => $moduleTemplate + array(
			'scripts' => array(
				'jquery/jquery.animateWithEvent.js',
			),
			'dependencies' => array(
				'jquery',
				'jquery.AnimationEvent',
			),
		),

		'jquery.AnimationEvent' => $moduleTemplate + array(
			'scripts' => array(
				'jquery/jquery.AnimationEvent.js',
			),
			'dependencies' => array(
				'jquery',
				'jquery.PurposedCallbacks',
			),
		),

		'jquery.autocompletestring' => $moduleTemplate + array(
			'scripts' => array(
				'jquery/jquery.autocompletestring.js',
			),
			'dependencies' => array(
				'jquery',
				'jquery.util.adaptlettercase',
			),
		),

		'jquery.focusAt' => $moduleTemplate + array(
			'scripts' => array(
				'jquery/jquery.focusAt.js',
			),
			'dependencies' => array(
				'jquery',
			),
		),

		'jquery.inputautoexpand' => $moduleTemplate + array(
			'scripts' => array(
				'jquery/jquery.inputautoexpand.js',
			),
			'dependencies' => array(
				'jquery',
				'jquery.event.special.eachchange',
			),
		),

		'jquery.NativeEventHandler' => $moduleTemplate + array(
			'scripts' => array(
				'jquery/jquery.NativeEventHandler.js',
			),
			'dependencies' => array(
				'jquery',
			),
		),

		'jquery.PurposedCallbacks' => $moduleTemplate + array(
			'scripts' => array(
				'jquery/jquery.PurposedCallbacks.js',
			),
			'dependencies' => array(
				'jquery',
			),
		),


		'jquery.event.special.eachchange' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.event/jquery.event.special.eachchange.js'
			),
			'dependencies' => array(
				'jquery',
				'jquery.client',
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
				'jquery',
				'jquery.animateWithEvent',
				'jquery.event.special.eachchange',
				'jquery.ui.position',
				'jquery.ui.widget',
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
				'jquery',
				'jquery.ui.autocomplete', // needs jquery.ui.menu
				'jquery.ui.widget',
				'jquery.ui.position',
			),
			'messages' => array(
				'valueview-listrotator-auto',
			),
		),

		'jquery.ui.preview' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.ui/jquery.ui.preview.js',
			),
			'styles' => array(
				'jquery.ui/jquery.ui.preview.css',
			),
			'dependencies' => array(
				'jquery',
				'jquery.ui.widget',
			),
		),

		'jquery.ui.suggester' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.ui/jquery.ui.suggester.js',
			),
			'styles' => array(
				'jquery.ui/jquery.ui.suggester.css',
			),
			'dependencies' => array(
				'jquery',
				'jquery.autocompletestring',
				'jquery.ui.autocomplete',
				'jquery.ui.widget',
				'jquery.util.adaptlettercase',
				'jquery.util.getscrollbarwidth',
			),
		),

		'jquery.ui.toggler' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.ui/jquery.ui.toggler.js',
			),
			'styles' => array(
				'jquery.ui/jquery.ui.toggler.css',
			),
			'dependencies' => array(
				'jquery',
				'jquery.animateWithEvent',
				'jquery.ui.widget',
			),
		),


		'jquery.util.adaptlettercase' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.util/jquery.util.adaptlettercase.js',
			),
			'dependencies' => array(
				'jquery',
			),
		),

		'jquery.util.getscrollbarwidth' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.util/jquery.util.getscrollbarwidth.js',
			),
			'dependencies' => array(
				'jquery',
			),
		),


		'util.MessageProvider' => $moduleTemplate + array(
			'scripts' => array(
				'util/util.MessageProvider.js',
			),
		),

		'util.Notifier' => $moduleTemplate + array(
			'scripts' => array(
				'util/util.Notifier.js',
			),
		),

	);

} );
