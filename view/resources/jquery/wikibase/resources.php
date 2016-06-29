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

	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => '..' . $remoteExtPath[0],
	);

	$modules = array(

		'jquery.wikibase.aliasesview' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.aliasesview.js',
			),
			'styles' => array(
				'themes/default/jquery.wikibase.aliasesview.css',
			),
			'dependencies' => array(
				'jquery.inputautoexpand',
				'jquery.ui.tagadata',
				'jquery.ui.EditableTemplatedWidget',
				'jquery.util.getDirectionality',
				'wikibase.datamodel.MultiTerm',
			),
			'messages' => array(
				'wikibase-aliases-input-help-message',
				'wikibase-alias-edit-placeholder',
			),
		),

		'jquery.wikibase.badgeselector' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.badgeselector.js',
			),
			'styles' => array(
				'themes/default/jquery.wikibase.badgeselector.css',
			),
			'dependencies' => array(
				'jquery.ui.menu',
				'jquery.ui.TemplatedWidget',
				'wikibase.templates',
			),
		),

		'jquery.wikibase.statementgrouplabelscroll' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.statementgrouplabelscroll.js',
			),
			'dependencies' => array(
				'jquery.ui.position',
				'jquery.ui.widget',
			),
		),

		'jquery.wikibase.statementgrouplistview' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.statementgrouplistview.js',
			),
			'dependencies' => array(
				'jquery.ui.TemplatedWidget',
				'jquery.ui.widget',
				'jquery.wikibase.statementgrouplabelscroll',
				'jquery.wikibase.listview',
				'wikibase.datamodel.StatementGroupSet',
			),
		),

		'jquery.wikibase.statementgroupview' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.statementgroupview.js',
			),
			'dependencies' => array(
				'jquery.ui.TemplatedWidget',
				'jquery.ui.widget',
				'wikibase.datamodel.StatementGroup',
				'wikibase.datamodel.StatementList',
			),
		),

		'jquery.wikibase.statementlistview' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.statementlistview.js',
			),
			'dependencies' => array(
				'jquery.ui.TemplatedWidget',
				'jquery.ui.widget',
				'jquery.wikibase.listview',
				'wikibase.datamodel.StatementList',
			),
		),

		'jquery.wikibase.descriptionview' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.descriptionview.js',
			),
			'styles' => array(
				'themes/default/jquery.wikibase.descriptionview.css',
			),
			'dependencies' => array(
				'jquery.inputautoexpand',
				'jquery.ui.core',
				'jquery.ui.TemplatedWidget',
				'jquery.util.getDirectionality',
				'wikibase.datamodel.Term',
				'wikibase.getLanguageNameByCode',
			),
			'messages' => array(
				'wikibase-description-edit-placeholder',
				'wikibase-description-edit-placeholder-language-aware',
				'wikibase-description-empty',
			),
		),

		'jquery.wikibase.entityselector' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.entityselector.js',
			),
			'styles' => array(
				'themes/default/jquery.wikibase.entityselector.css',
			),
			'dependencies' => array(
				'jquery.event.special.eachchange',
				'jquery.throttle-debounce',
				'jquery.ui.suggester',
				'jquery.ui.ooMenu',
				'jquery.ui.widget',
			),
			'messages' => array(
				'wikibase-entityselector-more',
			),
		),

		'jquery.wikibase.entityview' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.entityview.js',
			),
			'styles' => array(
				'themes/default/jquery.wikibase.entityview.css',
			),
			'dependencies' => array(
				'jquery.ui.TemplatedWidget',
			),
		),

		'jquery.wikibase.entitytermsview' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.entitytermsview.js',
			),
			'styles' => array(
				'themes/default/jquery.wikibase.entitytermsview.css',
			),
			'dependencies' => array(
				'jquery.cookie',
				'jquery.ui.closeable',
				'jquery.ui.EditableTemplatedWidget',
				'jquery.ui.toggler',
				'jquery.wikibase.entitytermsforlanguagelistview',
				'mediawiki.api',
				'mediawiki.user',
			),
			'messages' => array(
				'wikibase-entitytermsview-entitytermsforlanguagelistview-configure-link',
				'wikibase-entitytermsview-entitytermsforlanguagelistview-configure-link-label',
				'wikibase-entitytermsview-entitytermsforlanguagelistview-toggler',
				'wikibase-description-empty',
				'wikibase-label-empty',
			),
		),

		'jquery.wikibase.entitytermsforlanguagelistview' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.entitytermsforlanguagelistview.js',
			),
			'styles' => array(
				'themes/default/jquery.wikibase.entitytermsforlanguagelistview.css',
			),
			'dependencies' => array(
				'jquery.ui.TemplatedWidget',
				'jquery.wikibase.entitytermsforlanguageview',
				'wikibase.getLanguageNameByCode',
			),
			'messages' => array(
				'wikibase-entitytermsforlanguagelistview-aliases',
				'wikibase-entitytermsforlanguagelistview-description',
				'wikibase-entitytermsforlanguagelistview-label',
				'wikibase-entitytermsforlanguagelistview-language',
				'wikibase-entitytermsforlanguagelistview-less',
				'wikibase-entitytermsforlanguagelistview-more',
			),
		),

		'jquery.wikibase.entitytermsforlanguageview' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.entitytermsforlanguageview.js',
			),
			'styles' => array(
				'themes/default/jquery.wikibase.entitytermsforlanguageview.css',
			),
			'dependencies' => array(
				'jquery.ui.TemplatedWidget',
				'jquery.wikibase.aliasesview',
				'jquery.wikibase.descriptionview',
				'jquery.wikibase.labelview',
				'wikibase.getLanguageNameByCode',
				'wikibase.templates',
			),
		),

		'jquery.wikibase.itemview' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.itemview.js',
			),
			'dependencies' => array(
				'jquery.wikibase.entityview',
				'jquery.wikibase.sitelinkgrouplistview',
			),
		),

		'jquery.wikibase.labelview' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.labelview.js'
			),
			'styles' => array(
				'themes/default/jquery.wikibase.labelview.css',
			),
			'dependencies' => array(
				'jquery.ui.EditableTemplatedWidget',
				'jquery.util.getDirectionality',
				'wikibase.datamodel.Term',
				'wikibase.getLanguageNameByCode',
			),
			'messages' => array(
				'parentheses',
				'wikibase-label-edit-placeholder',
				'wikibase-label-edit-placeholder-language-aware',
				'wikibase-label-empty',
				'wikibase-label-input-help-message',
			),
		),

		'jquery.wikibase.listview' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.listview.js',
				'jquery.wikibase.listview.ListItemAdapter.js',
			),
			'dependencies' => array(
				'jquery.ui.TemplatedWidget',
				'jquery.ui.widget',
			),
		),

		'jquery.wikibase.pagesuggester' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.pagesuggester.js',
			),
			'dependencies' => array(
				'jquery.ui.ooMenu',
				'jquery.ui.suggester',
				'util.highlightSubstring',
				'wikibase.sites',
			),
		),

		'jquery.wikibase.propertyview' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.propertyview.js',
			),
			'dependencies' => array(
				'jquery.wikibase.entityview',
				'wikibase.templates',
			),
		),

		'jquery.wikibase.referenceview' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.referenceview.js',
			),
			'dependencies' => array(
				'jquery.removeClassByRegex',
				'jquery.wikibase.listview',
				'wikibase.datamodel',
			),
		),

		'jquery.wikibase.sitelinkgrouplistview' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.sitelinkgrouplistview.js'
			),
			'styles' => array(
				'themes/default/jquery.wikibase.sitelinkgrouplistview.css',
			),
			'dependencies' => array(
				'jquery.ui.TemplatedWidget',
				'jquery.wikibase.listview',
				'wikibase.sites',
			),
		),

		'jquery.wikibase.sitelinkgroupview' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.sitelinkgroupview.js'
			),
			'styles' => array(
				'themes/default/jquery.wikibase.sitelinkgroupview.css',
			),
			'dependencies' => array(
				'jquery.makeCollapsible',
				'jquery.sticknode',
				'jquery.ui.TemplatedWidget',
				'jquery.util.EventSingletonManager',
				'mediawiki.jqueryMsg', // for {{plural}} and {{gender}} support in messages
				'wikibase.buildErrorOutput',
				'wikibase.sites',
			),
			'messages' => array(
				'wikibase-sitelinkgroupview-input-help-message',
			),
		),

		'jquery.wikibase.sitelinklistview' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.sitelinklistview.js',
			),
			'styles' => array(
				'themes/default/jquery.wikibase.sitelinklistview.css',
			),
			'dependencies' => array(
				'jquery.event.special.eachchange',
				'jquery.ui.EditableTemplatedWidget',
				'jquery.util.EventSingletonManager',
				'jquery.wikibase.listview',
				'jquery.wikibase.sitelinkview',
				'wikibase.datamodel.SiteLink',
				'wikibase.sites',
				'wikibase.utilities', // wikibase.utilities.ui
			),
			'messages' => array(
				'parentheses',
				'wikibase-sitelinks-counter',
				'wikibase-propertyedittool-counter-pending-tooltip',
			),
		),

		'jquery.wikibase.sitelinkview' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.sitelinkview.js',
			),
			'styles' => array(
				'themes/default/jquery.wikibase.sitelinkview.css',
			),
			'dependencies' => array(
				'jquery.ui.TemplatedWidget',
				'jquery.util.EventSingletonManager',
				'jquery.wikibase.badgeselector',
				'jquery.wikibase.pagesuggester',
				'jquery.wikibase.siteselector',
				'mediawiki.util',
				'oojs-ui',
				'wikibase.datamodel.SiteLink',
				'wikibase.sites',
				'wikibase.templates',
			),
			'messages' => array(
				'wikibase-badgeselector-badge-placeholder-title',
				'wikibase-sitelink-site-edit-placeholder',
				'wikibase-sitelink-page-edit-placeholder',
			),
		),

		'jquery.wikibase.snaklistview' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.snaklistview.js',
			),
			'dependencies' => array(
				'jquery.ui.TemplatedWidget',
				'jquery.wikibase.listview',
				'wikibase.datamodel.Snak',
				'wikibase.datamodel.SnakList',
			),
		),

		'jquery.wikibase.statementview' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.statementview.js',
				'jquery.wikibase.statementview.RankSelector.js',
			),
			'dependencies' => array(
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
			),
			'messages' => array(
				'wikibase-addqualifier',
				'wikibase-addreference',
				'wikibase-claimview-snak-tooltip',
				'wikibase-claimview-snak-new-tooltip',
				'wikibase-statementview-rank-preferred',
				'wikibase-statementview-rank-normal',
				'wikibase-statementview-rank-deprecated',
				'wikibase-statementview-references-counter',
				'wikibase-statementview-referencesheading-pendingcountertooltip',
			),
		),

		'jquery.wikibase.statementview.RankSelector.styles' => $moduleTemplate + array(
			'position' => 'top',
			'styles' => array(
				'themes/default/jquery.wikibase.statementview.RankSelector.css',
			),
			'targets' => array( 'desktop', 'mobile' ),
		),

	);

	return array_merge(
		$modules,
		include __DIR__ . '/snakview/resources.php',
		include __DIR__ . '/toolbar/resources.php'
	);

} );
