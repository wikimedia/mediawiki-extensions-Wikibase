<?php

/**
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
$remoteExtPathParts = explode(
	DIRECTORY_SEPARATOR . 'extensions' . DIRECTORY_SEPARATOR, __DIR__, 2
);
$moduleBase = array(
	'localBasePath' => __DIR__,
	'remoteExtPath' => $remoteExtPathParts[1],
);

$modules = array(
	'wikibase.tests.getMockListItemAdapter' => $moduleBase + array(
		'scripts' => 'getMockListItemAdapter.js',
		'dependencies' => array(
			'jquery.wikibase.listview',
			'wikibase.tests',
		)
	)
);

return array_merge(
	$modules,
	include __DIR__ . '/jquery/resources.php',
	include __DIR__ . '/jquery/ui/resources.php',
	include __DIR__ . '/jquery/wikibase/resources.php',
	include __DIR__ . '/wikibase/resources.php',
	include __DIR__ . '/wikibase/entityChangers/resources.php',
	include __DIR__ . '/wikibase/entityIdFormatter/resources.php',
	include __DIR__ . '/wikibase/store/resources.php',
	include __DIR__ . '/wikibase/utilities/resources.php',
	include __DIR__ . '/wikibase/view/resources.php'
);
