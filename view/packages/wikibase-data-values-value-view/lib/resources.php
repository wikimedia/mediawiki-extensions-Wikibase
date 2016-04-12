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
				'jquery.AnimationEvent',
			),
		),

		'jquery.AnimationEvent' => $moduleTemplate + array(
			'scripts' => array(
				'jquery/jquery.AnimationEvent.js',
			),
			'dependencies' => array(
				'jquery.PurposedCallbacks',
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

		'jquery.focusAt' => $moduleTemplate + array(
			'scripts' => array(
				'jquery/jquery.focusAt.js',
			),
		),

		'jquery.inputautoexpand' => $moduleTemplate + array(
			'scripts' => array(
				'jquery/jquery.inputautoexpand.js',
			),
			'dependencies' => array(
				'jquery.event.special.eachchange',
			),
		),

		'jquery.PurposedCallbacks' => $moduleTemplate + array(
			'scripts' => array(
				'jquery/jquery.PurposedCallbacks.js',
			),
		),


		'jquery.event.special.eachchange' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.event/jquery.event.special.eachchange.js'
			),
			'dependencies' => array(
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
				'jquery.ui.autocomplete', // needs jquery.ui.menu
				'jquery.ui.widget',
				'jquery.ui.position',
			),
			'messages' => array(
				'valueview-listrotator-manually',
			),
		),

		'jquery.ui.ooMenu' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.ui/jquery.ui.ooMenu.js',
			),
			'styles' => array(
				'jquery.ui/jquery.ui.ooMenu.css',
			),
			'dependencies' => array(
				'jquery.ui.widget',
				'jquery.util.getscrollbarwidth',
				'util.inherit',
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
				'jquery.ui.widget',
				'util.CombiningMessageProvider',
				'util.HashMessageProvider'
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
				'jquery.ui.core',
				'jquery.ui.ooMenu',
				'jquery.ui.position',
				'jquery.ui.widget',
			),
		),

		'jquery.ui.commonssuggester' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.ui/jquery.ui.commonssuggester.js',
			),
			'dependencies' => array(
				'jquery.ui.suggester',
				'jquery.ui.widget',
				'util.highlightSubstring',
			),
		),

		'jquery.ui.languagesuggester' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.ui/jquery.ui.languagesuggester.js',
			),
			'dependencies' => array(
				'jquery.ui.suggester',
				'jquery.ui.widget',
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
				'jquery.animateWithEvent',
				'jquery.ui.core',
				'jquery.ui.widget',
			),
		),

		'jquery.ui.unitsuggester' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.ui/jquery.ui.unitsuggester.js',
			),
			'styles' => array(
				'jquery.ui/jquery.ui.unitsuggester.css',
			),
			'dependencies' => array(
				'jquery.ui.suggester',
				'jquery.ui.widget',
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


		'util.ContentLanguages' => $moduleTemplate + array(
			'scripts' => array(
				'util/util.ContentLanguages.js',
			),
			'dependencies' => array(
				'util.inherit',
			)
		),

		'util.Extendable' => $moduleTemplate + array(
			'scripts' => array(
				'util/util.Extendable.js',
			),
		),

		'util.highlightSubstring' => $moduleTemplate + array(
			'scripts' => array(
				'util/util.highlightSubstring.js',
			),
		),

		'util.MessageProvider' => $moduleTemplate + array(
			'scripts' => array(
				'util/util.MessageProvider.js',
			),
		),
		'util.HashMessageProvider' => $moduleTemplate + array(
			'scripts' => array(
				'util/util.HashMessageProvider.js',
			),
		),
		'util.CombiningMessageProvider' => $moduleTemplate + array(
			'scripts' => array(
				'util/util.CombiningMessageProvider.js',
			),
		),
		'util.PrefixingMessageProvider' => $moduleTemplate + array(
			'scripts' => array(
				'util/util.PrefixingMessageProvider.js',
			),
		),

		'util.Notifier' => $moduleTemplate + array(
			'scripts' => array(
				'util/util.Notifier.js',
			),
		),

	);

} );
