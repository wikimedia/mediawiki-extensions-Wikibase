<?php
/**
 * @license GNU GPL v2+
 * @author Adrian Lang <adrian.lang@wikimedia.de>
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

		'wikibase.entityChangers.__namespace' => $moduleTemplate + array(
			'scripts' => array(
				'namespace.js'
			),
			'dependencies' => array(
				'wikibase' // For the namespace
			)
		),

		'wikibase.entityChangers.AliasesChanger' => $moduleTemplate + array(
			'scripts' => array(
				'AliasesChanger.js',
			),
			'dependencies' => array(
				'wikibase.entityChangers.__namespace',
				'wikibase.RepoApiError',
			)
		),

		'wikibase.entityChangers.ClaimsChanger' => $moduleTemplate + array(
			'scripts' => array(
				'ClaimsChanger.js',
			),
			'dependencies' => array(
				'wikibase.entityChangers.__namespace',
				'wikibase.RepoApiError',
			)
		),

		'wikibase.entityChangers.DescriptionsChanger' => $moduleTemplate + array(
			'scripts' => array(
				'DescriptionsChanger.js',
			),
			'dependencies' => array(
				'wikibase.entityChangers.__namespace',
				'wikibase.RepoApiError',
			)
		),

		'wikibase.entityChangers.EntityChangersFactory' => $moduleTemplate + array(
			'scripts' => array(
				'EntityChangersFactory.js',
			),
			'dependencies' => array(
				'wikibase.entityChangers.__namespace',
				'wikibase.entityChangers.AliasesChanger',
				'wikibase.entityChangers.ClaimsChanger',
				'wikibase.entityChangers.DescriptionsChanger',
				'wikibase.entityChangers.LabelsChanger',
				'wikibase.entityChangers.ReferencesChanger',
				'wikibase.entityChangers.SiteLinksChanger',
			)
		),

		'wikibase.entityChangers.LabelsChanger' => $moduleTemplate + array(
			'scripts' => array(
				'LabelsChanger.js',
			),
			'dependencies' => array(
				'wikibase.entityChangers.__namespace',
				'wikibase.RepoApiError',
			)
		),

		'wikibase.entityChangers.ReferencesChanger' => $moduleTemplate + array(
			'scripts' => array(
				'ReferencesChanger.js',
			),
			'dependencies' => array(
				'wikibase.entityChangers.__namespace',
				'wikibase.RepoApiError',
			)
		),

		'wikibase.entityChangers.SiteLinksChanger' => $moduleTemplate + array(
			'scripts' => array(
				'SiteLinksChanger.js',
			),
			'dependencies' => array(
				'wikibase.entityChangers.__namespace',
				'wikibase.RepoApiError',
			)
		),

	);

} );
