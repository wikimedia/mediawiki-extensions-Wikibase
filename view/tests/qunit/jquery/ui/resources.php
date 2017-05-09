<?php

/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
return call_user_func( function() {
	$remoteExtPathParts = explode(
		DIRECTORY_SEPARATOR . 'extensions' . DIRECTORY_SEPARATOR, __DIR__, 2
	);
	$moduleBase = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => $remoteExtPathParts[1],
	);

	return array(
		'jquery.ui.closeable.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.ui.closeable.tests.js',
			),
			'dependencies' => array(
				'jquery.ui.closeable',
			),
		),

		'jquery.ui.tagadata.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.ui.tagadata.tests.js',
			),
			'dependencies' => array(
				'jquery.ui.tagadata',
			),
		),

		'jquery.ui.EditableTemplatedWidget.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.ui.EditableTemplatedWidget.tests.js',
			),
			'dependencies' => array(
				'jquery.ui.EditableTemplatedWidget',
				'wikibase.templates',
			),
		),

		'jquery.ui.TemplatedWidget.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.ui.TemplatedWidget.tests.js',
			),
			'dependencies' => array(
				'jquery.ui.TemplatedWidget',
				'wikibase.templates',
			),
		),
	);
} );
