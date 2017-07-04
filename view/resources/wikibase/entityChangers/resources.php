<?php

/**
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
return call_user_func( function() {
	preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
		. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', __DIR__, $remoteExtPath );

	$moduleTemplate = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => '..' . $remoteExtPath[0],
	];

	return [
		'wikibase.entityChangers.__namespace' => $moduleTemplate + [
			'scripts' => [
				'namespace.js',
			],
			'dependencies' => [
				'wikibase',
			],
		],

		'wikibase.entityChangers.AliasesChanger' => $moduleTemplate + [
			'scripts' => [
				'AliasesChanger.js',
			],
			'dependencies' => [
				'wikibase.datamodel.MultiTerm',
				'wikibase.entityChangers.__namespace',
				'wikibase.api.RepoApiError',
			],
		],

		'wikibase.entityChangers.StatementsChanger' => $moduleTemplate + [
			'scripts' => [
				'StatementsChanger.js',
			],
			'dependencies' => [
				'wikibase.entityChangers.__namespace',
				'wikibase.api.RepoApiError',
			],
		],

		'wikibase.entityChangers.DescriptionsChanger' => $moduleTemplate + [
			'scripts' => [
				'DescriptionsChanger.js',
			],
			'dependencies' => [
				'wikibase.entityChangers.__namespace',
				'wikibase.api.RepoApiError',
			]
		],

		'wikibase.entityChangers.EntityChangersFactory' => $moduleTemplate + [
			'scripts' => [
				'EntityChangersFactory.js',
			],
			'dependencies' => [
				'wikibase.entityChangers.__namespace',
				'wikibase.entityChangers.AliasesChanger',
				'wikibase.entityChangers.DescriptionsChanger',
				'wikibase.entityChangers.EntityTermsChanger',
				'wikibase.entityChangers.LabelsChanger',
				'wikibase.entityChangers.SiteLinkSetsChanger',
				'wikibase.entityChangers.StatementsChanger',
				'wikibase.serialization.StatementDeserializer',
				'wikibase.serialization.StatementSerializer',
			]
		],

		'wikibase.entityChangers.EntityTermsChanger' => $moduleTemplate + [
			'scripts' => [
				'EntityTermsChanger.js',
			],
			'dependencies' => [
				'wikibase.entityChangers.__namespace',
				'wikibase.api.RepoApiError',
			]
		],

		'wikibase.entityChangers.LabelsChanger' => $moduleTemplate + [
			'scripts' => [
				'LabelsChanger.js',
			],
			'dependencies' => [
				'wikibase.entityChangers.__namespace',
				'wikibase.api.RepoApiError',
			]
		],

		'wikibase.entityChangers.SiteLinksChanger' => $moduleTemplate + [
			'scripts' => [
				'SiteLinksChanger.js',
			],
			'dependencies' => [
				'wikibase.entityChangers.__namespace',
				'wikibase.api.RepoApiError',
			]
		],

		'wikibase.entityChangers.SiteLinkSetsChanger' => $moduleTemplate + [
			'scripts' => [
				'SiteLinkSetsChanger.js',
			],
			'dependencies' => [
				'wikibase.entityChangers.__namespace',
				'wikibase.entityChangers.SiteLinksChanger',
				'wikibase.api.RepoApiError',
			]
		],
	];
} );
