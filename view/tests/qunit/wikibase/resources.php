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
		'wikibase.getLanguageNameByCode.tests' => $moduleBase + array(
			'scripts' => array(
				'wikibase.getLanguageNameByCode.tests.js'
			),
			'dependencies' => array(
				'wikibase.getLanguageNameByCode'
			)
		),

		'wikibase.templates.tests' => $moduleBase + array(
			'scripts' => array(
				'templates.tests.js',
			),
			'dependencies' => array(
				'wikibase.templates',
			),
		),

		'wikibase.ValueViewBuilder.tests' => $moduleBase + array(
			'scripts' => array(
				'wikibase.ValueViewBuilder.tests.js'
			),
			'dependencies' => array(
				'test.sinonjs',
				'wikibase.ValueViewBuilder'
			)
		),
	);
} );
