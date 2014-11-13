<?php

/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	$remoteExtPathParts = explode(
		DIRECTORY_SEPARATOR . 'extensions' . DIRECTORY_SEPARATOR, __DIR__, 2
	);
	$moduleBase = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => $remoteExtPathParts[1],
	);

	$modules = array(

		'jquery.ui.TemplatedWidget.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.ui/jquery.ui.TemplatedWidget.tests.js',
			),
			'dependencies' => array(
				'jquery.ui.TemplatedWidget',
				'wikibase.templates',
			),
		),

		'templates.tests' => $moduleBase + array(
			'scripts' => array(
				'templates.tests.js',
			),
			'dependencies' => array(
				'wikibase.templates',
			),
		),

		'wikibase.getLanguageNameByCode.tests' => $moduleBase + array(
			'scripts' => array(
				'wikibase.getLanguageNameByCode.tests.js'
			),
			'dependencies' => array(
				'wikibase.getLanguageNameByCode'
			)
		),

	);

	return array_merge(
		$modules,
		include __DIR__ . '/utilities/resources.php'
	);

} );
