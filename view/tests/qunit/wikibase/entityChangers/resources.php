<?php

/**
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
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

		'wikibase.entityChangers.AliasesChanger.tests' => $moduleBase + array(
			'scripts' => array(
				'AliasesChanger.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel',
				'wikibase.entityChangers.AliasesChanger',
			),
		),

		'wikibase.entityChangers.StatementsChanger.tests' => $moduleBase + array(
			'scripts' => array(
				'StatementsChanger.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel',
				'wikibase.entityChangers.StatementsChanger',
				'wikibase.serialization.StatementDeserializer',
				'wikibase.serialization.StatementSerializer',
			),
		),

		'wikibase.entityChangers.DescriptionsChanger.tests' => $moduleBase + array(
			'scripts' => array(
				'DescriptionsChanger.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel',
				'wikibase.entityChangers.DescriptionsChanger'
			),
		),

		'wikibase.entityChangers.EntityTermsChanger.tests' => $moduleBase + array(
			'scripts' => array(
				'EntityTermsChanger.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel',
				'wikibase.entityChangers.EntityTermsChanger'
			),
		),

		'wikibase.entityChangers.LabelsChanger.tests' => $moduleBase + array(
			'scripts' => array(
				'LabelsChanger.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel',
				'wikibase.entityChangers.LabelsChanger'
			),
		),

		'wikibase.entityChangers.ReferencesChanger.tests' => $moduleBase + array(
			'scripts' => array(
				'ReferencesChanger.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel',
				'wikibase.entityChangers.ReferencesChanger',
				'wikibase.serialization.ReferenceDeserializer',
				'wikibase.serialization.ReferenceSerializer',
			),
		),

		'wikibase.entityChangers.SiteLinksChanger.tests' => $moduleBase + array(
			'scripts' => array(
				'SiteLinksChanger.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel',
				'wikibase.entityChangers.SiteLinksChanger'
			),
		),

		'wikibase.entityChangers.SiteLinkSetsChanger.tests' => $moduleBase + array(
			'scripts' => array(
				'SiteLinkSetsChanger.tests.js',
			),
			'dependencies' => array(
				'wikibase.datamodel',
				'wikibase.entityChangers.SiteLinkSetsChanger'
			),
		),

	);

	return $modules;
} );
