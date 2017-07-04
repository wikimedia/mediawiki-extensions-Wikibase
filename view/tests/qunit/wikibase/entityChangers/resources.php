<?php

/**
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
return call_user_func( function() {
	$remoteExtPathParts = explode(
		DIRECTORY_SEPARATOR . 'extensions' . DIRECTORY_SEPARATOR, __DIR__, 2
	);
	$moduleBase = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => $remoteExtPathParts[1],
	];

	$modules = [

		'wikibase.entityChangers.AliasesChanger.tests' => $moduleBase + [
			'scripts' => [
				'AliasesChanger.tests.js',
			],
			'dependencies' => [
				'wikibase.datamodel',
				'wikibase.entityChangers.AliasesChanger',
			],
		],

		'wikibase.entityChangers.StatementsChanger.tests' => $moduleBase + [
			'scripts' => [
				'StatementsChanger.tests.js',
			],
			'dependencies' => [
				'wikibase.datamodel',
				'wikibase.entityChangers.StatementsChanger',
				'wikibase.serialization.StatementDeserializer',
				'wikibase.serialization.StatementSerializer',
			],
		],

		'wikibase.entityChangers.DescriptionsChanger.tests' => $moduleBase + [
			'scripts' => [
				'DescriptionsChanger.tests.js',
			],
			'dependencies' => [
				'wikibase.datamodel',
				'wikibase.entityChangers.DescriptionsChanger'
			],
		],

		'wikibase.entityChangers.EntityTermsChanger.tests' => $moduleBase + [
			'scripts' => [
				'EntityTermsChanger.tests.js',
			],
			'dependencies' => [
				'wikibase.datamodel',
				'wikibase.entityChangers.EntityTermsChanger'
			],
		],

		'wikibase.entityChangers.LabelsChanger.tests' => $moduleBase + [
			'scripts' => [
				'LabelsChanger.tests.js',
			],
			'dependencies' => [
				'wikibase.datamodel',
				'wikibase.entityChangers.LabelsChanger'
			],
		],

		'wikibase.entityChangers.SiteLinksChanger.tests' => $moduleBase + [
			'scripts' => [
				'SiteLinksChanger.tests.js',
			],
			'dependencies' => [
				'wikibase.datamodel',
				'wikibase.entityChangers.SiteLinksChanger'
			],
		],

		'wikibase.entityChangers.SiteLinkSetsChanger.tests' => $moduleBase + [
			'scripts' => [
				'SiteLinkSetsChanger.tests.js',
			],
			'dependencies' => [
				'wikibase.datamodel',
				'wikibase.entityChangers.SiteLinkSetsChanger'
			],
		],

	];

	return $modules;
} );
