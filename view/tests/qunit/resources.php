<?php

/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
$moduleBase = [
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'Wikibase/view/tests/qunit',
];

$modules = [
	'wikibase.tests.getMockListItemAdapter' => $moduleBase + [
		'scripts' => 'getMockListItemAdapter.js',
		'dependencies' => [
			'jquery.wikibase.listview',
			'wikibase.tests',
		]
	],
	'wikibase.experts.modules.tests' => $moduleBase + [
		'scripts' => 'experts/wikibase.experts.modules.tests.js',
		'dependencies' => [
			'wikibase.experts.modules'
		]
	],
];

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
