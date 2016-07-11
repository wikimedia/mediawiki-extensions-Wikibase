<?php

/**
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
return call_user_func( function() {
	preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
		. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', __DIR__, $remoteExtPath );

	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => '..' . $remoteExtPath[0],
	);

	return array(

		'wikibase.entityChangers.__namespace' => $moduleTemplate + array(
			'scripts' => array(
				'namespace.js',
			),
			'dependencies' => array(
				'wikibase',
			),
		),

		'wikibase.entityChangers.AliasesChanger' => $moduleTemplate + array(
			'scripts' => array(
				'AliasesChanger.js',
			),
			'dependencies' => array(
				'wikibase.datamodel.MultiTerm',
				'wikibase.entityChangers.__namespace',
				'wikibase.api.RepoApiError',
			),
		),

		'wikibase.entityChangers.StatementsChanger' => $moduleTemplate + array(
			'scripts' => array(
				'StatementsChanger.js',
			),
			'dependencies' => array(
				'wikibase.entityChangers.__namespace',
				'wikibase.api.RepoApiError',
			),
		),

		'wikibase.entityChangers.DescriptionsChanger' => $moduleTemplate + array(
			'scripts' => array(
				'DescriptionsChanger.js',
			),
			'dependencies' => array(
				'wikibase.entityChangers.__namespace',
				'wikibase.api.RepoApiError',
			)
		),

		'wikibase.entityChangers.EntityChangersFactory' => $moduleTemplate + array(
			'scripts' => array(
				'EntityChangersFactory.js',
			),
			'dependencies' => array(
				'wikibase.entityChangers.__namespace',
				'wikibase.entityChangers.AliasesChanger',
				'wikibase.entityChangers.DescriptionsChanger',
				'wikibase.entityChangers.EntityTermsChanger',
				'wikibase.entityChangers.LabelsChanger',
				'wikibase.entityChangers.ReferencesChanger',
				'wikibase.entityChangers.SiteLinkSetsChanger',
				'wikibase.entityChangers.StatementsChanger',
				'wikibase.serialization.ReferenceDeserializer',
				'wikibase.serialization.ReferenceSerializer',
				'wikibase.serialization.StatementDeserializer',
				'wikibase.serialization.StatementSerializer',
			)
		),

		'wikibase.entityChangers.EntityTermsChanger' => $moduleTemplate + array(
			'scripts' => array(
				'EntityTermsChanger.js',
			),
			'dependencies' => array(
				'wikibase.entityChangers.__namespace',
				'wikibase.api.RepoApiError',
			)
		),

		'wikibase.entityChangers.LabelsChanger' => $moduleTemplate + array(
			'scripts' => array(
				'LabelsChanger.js',
			),
			'dependencies' => array(
				'wikibase.entityChangers.__namespace',
				'wikibase.api.RepoApiError',
			)
		),

		'wikibase.entityChangers.ReferencesChanger' => $moduleTemplate + array(
			'scripts' => array(
				'ReferencesChanger.js',
			),
			'dependencies' => array(
				'wikibase.entityChangers.__namespace',
				'wikibase.api.RepoApiError',
			),
		),

		'wikibase.entityChangers.SiteLinksChanger' => $moduleTemplate + array(
			'scripts' => array(
				'SiteLinksChanger.js',
			),
			'dependencies' => array(
				'wikibase.entityChangers.__namespace',
				'wikibase.api.RepoApiError',
			)
		),

		'wikibase.entityChangers.SiteLinkSetsChanger' => $moduleTemplate + array(
			'scripts' => array(
				'SiteLinkSetsChanger.js',
			),
			'dependencies' => array(
				'wikibase.entityChangers.__namespace',
				'wikibase.entityChangers.SiteLinksChanger',
				'wikibase.api.RepoApiError',
			)
		),

	);

} );
