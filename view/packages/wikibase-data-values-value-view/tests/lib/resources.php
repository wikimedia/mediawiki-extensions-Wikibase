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

		'jquery.animateWithEvent.tests' => $moduleTemplate + array(
			'scripts' => array(
				'jquery/jquery.animateWithEvent.tests.js',
			),
			'dependencies' => array(
				'jquery.animateWithEvent',
			),
		),

		'jquery.AnimationEvent.tests' => $moduleTemplate + array(
			'scripts' => array(
				'jquery/jquery.AnimationEvent.tests.js',
			),
			'dependencies' => array(
				'jquery.AnimationEvent',
			),
		),

		'jquery.autocompletestring.tests' => $moduleTemplate + array(
			'scripts' => array(
				'jquery/jquery.autocompletestring.tests.js',
			),
			'dependencies' => array(
				'jquery.autocompletestring',
			),
		),

		'jquery.focusAt.tests' => $moduleTemplate + array(
			'scripts' => array(
				'jquery/jquery.focusAt.tests.js',
			),
			'dependencies' => array(
				'jquery.focusAt',
				'qunit.parameterize',
			),
		),

		'jquery.inputautoexpand.tests' => $moduleTemplate + array(
			'scripts' => array(
				'jquery/jquery.inputautoexpand.tests.js',
			),
			'dependencies' => array(
				'jquery.inputautoexpand',
			),
		),

		'jquery.PurposedCallbacks.tests' => $moduleTemplate + array(
			'scripts' => array(
				'jquery/jquery.PurposedCallbacks.tests.js',
			),
			'dependencies' => array(
				'jquery.PurposedCallbacks',
			),
		),


		'jquery.event.special.eachchange.tests' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.event/jquery.event.special.eachchange.tests.js',
			),
			'dependencies' => array(
				'jquery.event.special.eachchange',
			),
		),


		'jquery.ui.inputextender.tests' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.ui/jquery.ui.inputextender.tests.js',
			),
			'dependencies' => array(
				'jquery.ui.inputextender',
			),
		),

		'jquery.ui.listrotator.tests' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.ui/jquery.ui.listrotator.tests.js',
			),
			'dependencies' => array(
				'jquery.ui.listrotator',
			),
		),

		'jquery.ui.ooMenu.tests' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.ui/jquery.ui.ooMenu.tests.js',
			),
			'dependencies' => array(
				'jquery.ui.ooMenu',
			),
		),

		'jquery.ui.preview.tests' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.ui/jquery.ui.preview.tests.js',
			),
			'dependencies' => array(
				'jquery.ui.preview',
			),
		),

		'jquery.ui.suggester.tests' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.ui/jquery.ui.suggester.tests.js',
			),
			'dependencies' => array(
				'jquery.ui.suggester',
			),
		),

		'jquery.ui.toggler.tests' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.ui/jquery.ui.toggler.tests.js',
			),
			'dependencies' => array(
				'jquery.ui.toggler',
			),
		),


		'jquery.util.adaptlettercase.tests' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.util/jquery.util.adaptlettercase.tests.js',
			),
			'dependencies' => array(
				'jquery.util.adaptlettercase',
			),
		),

		'jquery.util.getscrollbarwidth.tests' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.util/jquery.util.getscrollbarwidth.tests.js',
			),
			'dependencies' => array(
				'jquery.util.getscrollbarwidth',
			),
		),


		'util.highlightSubstring.tests' => $moduleTemplate + array(
			'scripts' => array(
				'util/util.highlightSubstring.tests.js',
			),
			'dependencies' => array(
				'util.highlightSubstring',
			),
		),

		'util.HashMessageProvider.tests' => $moduleTemplate + array(
			'scripts' => array(
				'util/util.HashMessageProvider.tests.js',
			),
			'dependencies' => array(
				'test.sinonjs',
				'util.HashMessageProvider',
			),
		),

		'util.Notifier.tests' => $moduleTemplate + array(
			'scripts' => array(
				'util/util.Notifier.tests.js',
			),
			'dependencies' => array(
				'util.Notifier',
			),
		),

	);

} );
