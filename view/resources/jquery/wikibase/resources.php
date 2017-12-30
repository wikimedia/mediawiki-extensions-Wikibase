<?php

/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
		. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', __DIR__, $remoteExtPath );

	$moduleTemplate = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => '..' . $remoteExtPath[0],
	];

	$modules = [
		'jquery.wikibase.aliasesview' => $moduleTemplate + [
			'scripts' => [
				'jquery.wikibase.aliasesview.js',
			],
			'styles' => [
				'themes/default/jquery.wikibase.aliasesview.css',
			],
			'dependencies' => [
				'jquery.inputautoexpand',
				'jquery.ui.tagadata',
				'jquery.ui.EditableTemplatedWidget',
				'jquery.util.getDirectionality',
				'wikibase.datamodel.MultiTerm',
			],
			'messages' => [
				'wikibase-aliases-input-help-message',
				'wikibase-alias-edit-placeholder',
			],
		],

		'jquery.wikibase.badgeselector' => $moduleTemplate + [
			'scripts' => [
				'jquery.wikibase.badgeselector.js',
			],
			'styles' => [
				'themes/default/jquery.wikibase.badgeselector.css',
			],
			'dependencies' => [
				'jquery.ui.menu',
				'jquery.ui.EditableTemplatedWidget',
				'wikibase.templates',
			],
		],

		'jquery.wikibase.statementgrouplabelscroll' => $moduleTemplate + [
			'scripts' => [
				'jquery.wikibase.statementgrouplabelscroll.js',
			],
			'dependencies' => [
				'jquery.ui.position',
				'jquery.ui.widget',
			],
		],

		'jquery.wikibase.statementgrouplistview' => $moduleTemplate + [
			'scripts' => [
				'jquery.wikibase.statementgrouplistview.js',
			],
			'dependencies' => [
				'jquery.ui.TemplatedWidget',
				'jquery.ui.widget',
				'jquery.wikibase.statementgrouplabelscroll',
				'jquery.wikibase.listview',
				'wikibase.datamodel.StatementGroupSet',
			],
			'messages' => [
				'wikibase-statementgrouplistview-add',
				'wikibase-statementgrouplistview-add-tooltip',
			],
		],

		'jquery.wikibase.statementgroupview' => $moduleTemplate + [
			'scripts' => [
				'jquery.wikibase.statementgroupview.js',
			],
			'dependencies' => [
				'jquery.ui.TemplatedWidget',
				'jquery.ui.widget',
				'wikibase.datamodel.StatementGroup',
				'wikibase.datamodel.StatementList',
			],
		],

		'jquery.wikibase.statementlistview' => $moduleTemplate + [
			'scripts' => [
				'jquery.wikibase.statementlistview.js',
			],
			'dependencies' => [
				'jquery.ui.TemplatedWidget',
				'jquery.ui.widget',
				'jquery.wikibase.listview',
				'wikibase.datamodel.StatementList',
			],
			'messages' => [
					'wikibase-statementlistview-add',
					'wikibase-statementlistview-add-tooltip',
			],
		],

		'jquery.wikibase.descriptionview' => $moduleTemplate + [
			'scripts' => [
				'jquery.wikibase.descriptionview.js',
			],
			'styles' => [
				'themes/default/jquery.wikibase.descriptionview.css',
			],
			'dependencies' => [
				'jquery.inputautoexpand',
				'jquery.ui.core',
				'jquery.ui.EditableTemplatedWidget',
				'jquery.util.getDirectionality',
				'wikibase.datamodel.Term',
				'wikibase.getLanguageNameByCode',
			],
			'messages' => [
				'wikibase-description-edit-placeholder',
				'wikibase-description-edit-placeholder-language-aware',
				'wikibase-description-empty',
			],
		],

		'jquery.wikibase.entityselector' => $moduleTemplate + [
			'scripts' => [
				'jquery.wikibase.entityselector.js',
			],
			'styles' => [
				'themes/default/jquery.wikibase.entityselector.css',
			],
			'dependencies' => [
				'jquery.event.special.eachchange',
				'jquery.throttle-debounce',
				'jquery.ui.suggester',
				'jquery.ui.ooMenu',
				'jquery.ui.widget',
			],
			'messages' => [
				'wikibase-entityselector-more',
				'wikibase-entityselector-notfound',
			],
		],

		'jquery.wikibase.entityview' => $moduleTemplate + [
			'scripts' => [
				'jquery.wikibase.entityview.js',
			],
			'styles' => [
				'themes/default/jquery.wikibase.entityview.css',
			],
			'dependencies' => [
				'jquery.ui.TemplatedWidget',
			],
		],

		'jquery.wikibase.entitytermsview' => $moduleTemplate + [
			'scripts' => [
				'jquery.wikibase.entitytermsview.js',
			],
			'styles' => [
				'themes/default/jquery.wikibase.entitytermsview.css',
			],
			'dependencies' => [
				'jquery.ui.closeable',
				'jquery.ui.EditableTemplatedWidget',
				'jquery.ui.toggler',
				'jquery.wikibase.entitytermsforlanguagelistview',
				'mediawiki.api',
				'mediawiki.api.options',
				'mediawiki.cookie',
				'mediawiki.user',
			],
			'messages' => [
				'wikibase-entitytermsview-entitytermsforlanguagelistview-configure-link',
				'wikibase-entitytermsview-entitytermsforlanguagelistview-configure-link-label',
				'wikibase-entitytermsview-entitytermsforlanguagelistview-toggler',
				'wikibase-description-empty',
				'wikibase-label-empty',
			],
		],

		'jquery.wikibase.entitytermsforlanguagelistview' => $moduleTemplate + [
			'scripts' => [
				'jquery.wikibase.entitytermsforlanguagelistview.js',
			],
			'styles' => [
				'themes/default/jquery.wikibase.entitytermsforlanguagelistview.css',
			],
			'dependencies' => [
				'jquery.ui.EditableTemplatedWidget',
				'jquery.wikibase.entitytermsforlanguageview',
				'wikibase.getLanguageNameByCode',
			],
			'messages' => [
				'wikibase-entitytermsforlanguagelistview-aliases',
				'wikibase-entitytermsforlanguagelistview-description',
				'wikibase-entitytermsforlanguagelistview-label',
				'wikibase-entitytermsforlanguagelistview-language',
				'wikibase-entitytermsforlanguagelistview-less',
				'wikibase-entitytermsforlanguagelistview-more',
			],
		],

		'jquery.wikibase.entitytermsforlanguageview' => $moduleTemplate + [
			'scripts' => [
				'jquery.wikibase.entitytermsforlanguageview.js',
			],
			'styles' => [
				'themes/default/jquery.wikibase.entitytermsforlanguageview.css',
			],
			'dependencies' => [
				'jquery.ui.EditableTemplatedWidget',
				'jquery.wikibase.aliasesview',
				'jquery.wikibase.descriptionview',
				'jquery.wikibase.labelview',
				'wikibase.getLanguageNameByCode',
				'wikibase.templates',
			],
		],

		'jquery.wikibase.itemview' => $moduleTemplate + [
			'scripts' => [
				'jquery.wikibase.itemview.js',
			],
			'dependencies' => [
				'jquery.wikibase.entityview',
				'jquery.wikibase.sitelinkgrouplistview',
			],
		],

		'jquery.wikibase.labelview' => $moduleTemplate + [
			'scripts' => [
				'jquery.wikibase.labelview.js'
			],
			'styles' => [
				'themes/default/jquery.wikibase.labelview.css',
			],
			'dependencies' => [
				'jquery.ui.EditableTemplatedWidget',
				'jquery.util.getDirectionality',
				'wikibase.datamodel.Term',
				'wikibase.getLanguageNameByCode',
			],
			'messages' => [
				'parentheses',
				'wikibase-label-edit-placeholder',
				'wikibase-label-edit-placeholder-language-aware',
				'wikibase-label-empty',
				'wikibase-label-input-help-message',
			],
		],

		'jquery.wikibase.listview' => $moduleTemplate + [
			'scripts' => [
				'jquery.wikibase.listview.js',
				'jquery.wikibase.listview.ListItemAdapter.js',
			],
			'dependencies' => [
				'jquery.ui.TemplatedWidget',
				'jquery.ui.widget',
			],
		],

		'jquery.wikibase.pagesuggester' => $moduleTemplate + [
			'scripts' => [
				'jquery.wikibase.pagesuggester.js',
			],
			'dependencies' => [
				'jquery.ui.ooMenu',
				'jquery.ui.suggester',
				'util.highlightSubstring',
				'wikibase.sites',
			],
		],

		'jquery.wikibase.propertyview' => $moduleTemplate + [
			'scripts' => [
				'jquery.wikibase.propertyview.js',
			],
			'dependencies' => [
				'jquery.wikibase.entityview',
				'wikibase.templates',
			],
		],

		'jquery.wikibase.referenceview' => $moduleTemplate + [
			'scripts' => [
				'jquery.wikibase.referenceview.js',
			],
			'dependencies' => [
				'jquery.removeClassByRegex',
				'jquery.ui.EditableTemplatedWidget',
				'jquery.wikibase.listview',
				'wikibase.datamodel',
			],
		],

		'jquery.wikibase.sitelinkgrouplistview' => $moduleTemplate + [
			'scripts' => [
				'jquery.wikibase.sitelinkgrouplistview.js'
			],
			'styles' => [
				'themes/default/jquery.wikibase.sitelinkgrouplistview.css',
			],
			'dependencies' => [
				'jquery.ui.TemplatedWidget',
				'jquery.wikibase.listview',
				'wikibase.sites',
			],
		],

		'jquery.wikibase.sitelinkgroupview' => $moduleTemplate + [
			'scripts' => [
				'jquery.wikibase.sitelinkgroupview.js'
			],
			'styles' => [
				'themes/default/jquery.wikibase.sitelinkgroupview.css',
			],
			'dependencies' => [
				'jquery.sticknode',
				'jquery.ui.EditableTemplatedWidget',
				'jquery.util.EventSingletonManager',
				'jquery.wikibase.sitelinkgroupview.mw-collapsible.styles',
				'mediawiki.jqueryMsg', // for {{plural}} and {{gender}} support in messages
				'wikibase.buildErrorOutput',
				'wikibase.sites',
			],
			'messages' => [
				'wikibase-sitelinkgroupview-input-help-message',
			],
		],

		'jquery.wikibase.sitelinkgroupview.mw-collapsible.styles' => $moduleTemplate + [
			'styles' => [
				'themes/default/jquery.wikibase.sitelinkgroupview.mw-collapsible.css',
			],
		],

		'jquery.wikibase.sitelinklistview' => $moduleTemplate + [
			'scripts' => [
				'jquery.wikibase.sitelinklistview.js',
			],
			'styles' => [
				'themes/default/jquery.wikibase.sitelinklistview.css',
			],
			'dependencies' => [
				'jquery.event.special.eachchange',
				'jquery.ui.EditableTemplatedWidget',
				'jquery.util.EventSingletonManager',
				'jquery.wikibase.listview',
				'jquery.wikibase.sitelinkview',
				'wikibase.datamodel.SiteLink',
				'wikibase.sites',
				'wikibase.utilities', // wikibase.utilities.ui
			],
			'messages' => [
				'parentheses',
				'wikibase-sitelinks-counter',
			],
		],

		'jquery.wikibase.sitelinkview' => $moduleTemplate + [
			'scripts' => [
				'jquery.wikibase.sitelinkview.js',
			],
			'styles' => [
				'themes/default/jquery.wikibase.sitelinkview.css',
			],
			'dependencies' => [
				'jquery.ui.EditableTemplatedWidget',
				'jquery.util.EventSingletonManager',
				'jquery.wikibase.badgeselector',
				'jquery.wikibase.pagesuggester',
				'jquery.wikibase.siteselector',
				'mediawiki.util',
				'oojs-ui',
				'wikibase.datamodel.SiteLink',
				'wikibase.sites',
				'wikibase.templates',
			],
			'messages' => [
				'wikibase-badgeselector-badge-placeholder-title',
				'wikibase-remove',
				'wikibase-sitelink-site-edit-placeholder',
				'wikibase-sitelink-page-edit-placeholder',
			],
		],

		'jquery.wikibase.snaklistview' => $moduleTemplate + [
			'scripts' => [
				'jquery.wikibase.snaklistview.js',
			],
			'dependencies' => [
				'jquery.ui.EditableTemplatedWidget',
				'jquery.wikibase.listview',
				'wikibase.datamodel.Snak',
				'wikibase.datamodel.SnakList',
			],
		],

		'jquery.wikibase.statementview' => $moduleTemplate + [
			'scripts' => [
				'jquery.wikibase.statementview.js',
				'jquery.wikibase.statementview.RankSelector.js',
			],
			'dependencies' => [
				'jquery.ui.EditableTemplatedWidget',
				'jquery.ui.menu',
				'jquery.ui.position',
				'jquery.ui.toggler',
				'util.inherit',
				'jquery.wikibase.listview',
				'jquery.wikibase.referenceview',
				'jquery.wikibase.snakview',
				'jquery.wikibase.snaklistview',
				'jquery.wikibase.statementview.RankSelector.styles',
				'wikibase.datamodel.Claim',
				'wikibase.datamodel.ReferenceList',
				'wikibase.datamodel.SnakList',
				'wikibase.datamodel.Statement',
				'wikibase.utilities',
			],
			'messages' => [
				'wikibase-addqualifier',
				'wikibase-addreference',
				'wikibase-claimview-snak-tooltip',
				'wikibase-claimview-snak-new-tooltip',
				'wikibase-statementview-rank-preferred',
				'wikibase-statementview-rank-tooltip-preferred',
				'wikibase-statementview-rank-normal',
				'wikibase-statementview-rank-tooltip-normal',
				'wikibase-statementview-rank-deprecated',
				'wikibase-statementview-rank-tooltip-deprecated',
				'wikibase-statementview-references-counter',
			],
		],

		'jquery.wikibase.statementview.RankSelector.styles' => $moduleTemplate + [
			'position' => 'top',
			'styles' => [
				'themes/default/jquery.wikibase.statementview.RankSelector.css',
			],
			'targets' => [ 'desktop', 'mobile' ],
		],
	];

	return array_merge(
		$modules,
		include __DIR__ . '/snakview/resources.php',
		include __DIR__ . '/toolbar/resources.php'
	);
} );
