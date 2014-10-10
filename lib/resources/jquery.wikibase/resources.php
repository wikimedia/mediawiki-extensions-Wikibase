<?php
/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	$remoteExtPathParts = explode(
		DIRECTORY_SEPARATOR . 'extensions' . DIRECTORY_SEPARATOR, __DIR__, 2
	);
	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => $remoteExtPathParts[1],
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
				'jquery.ui.TemplatedWidget',
				'jquery.wikibase.edittoolbar',
				'jquery.wikibase.toolbarcontroller',
				'wikibase.templates',
			),
			'messages' => array(
				'wikibase-aliases-label',
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
				'wikibase.datamodel',
				'wikibase.templates',
				'wikibase.utilities',
			),
		),

		'jquery.wikibase.claimgrouplabelscroll' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.claimgrouplabelscroll.js',
			),
			'dependencies' => array(
				'jquery.ui.position',
				'jquery.ui.widget',
			),
		),

		'jquery.wikibase.claimgrouplistview' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.claimgrouplistview.js',
			),
			'dependencies' => array(
				'jquery.ui.TemplatedWidget',
				'jquery.ui.widget',
				'jquery.wikibase.claimlistview',
				'jquery.wikibase.listview',
				'jquery.wikibase.toolbarcontroller',
				'wikibase',
				'wikibase.datamodel',
			),
		),

		'jquery.wikibase.claimlistview' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.claimlistview.js',
			),
			'dependencies' => array(
				'jquery.ui.TemplatedWidget',
				'jquery.ui.widget',
				'jquery.wikibase.claimview',
				'jquery.wikibase.listview',
				'jquery.wikibase.statementview',
				'jquery.wikibase.toolbarcontroller',
				'wikibase',
				'wikibase.datamodel',
				'wikibase.templates',
				'wikibase.utilities',
				'wikibase.utilities.ClaimGuidGenerator',
			),
			'messages' => array(
				'wikibase-entity-property',
			),
		),

		'jquery.wikibase.claimview' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.claimview.js',
			),
			'dependencies' => array(
				'jquery.wikibase.snakview',
				'jquery.wikibase.snaklistview',
				'wikibase.datamodel',
				'jquery.wikibase.toolbarcontroller',
			),
			'messages' => array(
				'wikibase-addqualifier',
				'wikibase-claimview-snak-tooltip',
				'wikibase-claimview-snak-new-tooltip',
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
				'jquery.ui.TemplatedWidget',
				'jquery.wikibase.edittoolbar',
				'jquery.wikibase.toolbarcontroller',
				'wikibase',
			),
			'messages' => array(
				'wikibase-description-edit-placeholder',
				'wikibase-description-edit-placeholder-language-aware',
				'wikibase-description-empty',
				'wikibase-description-input-help-message',
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
				'jquery.ui.suggester',
				'jquery.ui.ooMenu',
				'jquery.ui.widget',
			),
			'messages' => array(
				'wikibase-aliases-label',
				'wikibase-entityselector-more',
			),
		),

		'jquery.wikibase.entityview' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.entityview.js',
			),
			'dependencies' => array(
				'jquery.ui.TemplatedWidget',
				'jquery.wikibase.aliasesview',
				'jquery.wikibase.claimgrouplistview',
				'jquery.wikibase.claimlistview',
				'jquery.wikibase.descriptionview',
				'jquery.wikibase.fingerprintgroupview',
				'jquery.wikibase.labelview',
				'jquery.wikibase.toolbarcontroller',
				'jquery.wikibase.sitelinkgrouplistview',
				'jquery.wikibase.statementview',
				'wikibase',
				'wikibase.templates',
			),
			'messages' => array(
				'wikibase-fingerprintgroupview-input-help-message',
				'wikibase-terms',
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
				'jquery.ui.TemplatedWidget',
				'jquery.wikibase.edittoolbar',
				'jquery.wikibase.toolbarcontroller',
				'wikibase',
			),
			'messages' => array(
				'parentheses',
				'wikibase-label-edit-placeholder',
				'wikibase-label-edit-placeholder-language-aware',
				'wikibase-label-empty',
				'wikibase-label-input-help-message',
			),
		),

		'jquery.wikibase.fingerprintgroupview' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.fingerprintgroupview.js',
			),
			'styles' => array(
				'themes/default/jquery.wikibase.fingerprintgroupview.css',
			),
			'dependencies' => array(
				'jquery.ui.TemplatedWidget',
				'jquery.wikibase.fingerprintlistview',
			),
			'messages' => array(
				'wikibase-terms',
			),
		),

		'jquery.wikibase.fingerprintlistview' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.fingerprintlistview.js',
			),
			'styles' => array(
				'themes/default/jquery.wikibase.fingerprintlistview.css',
			),
			'dependencies' => array(
				'wikibase',
				'jquery.ui.TemplatedWidget',
				'jquery.wikibase.fingerprintview',
			),
			'messages' => array(
				'wikibase-fingerprintview-input-help-message',
			),
		),

		'jquery.wikibase.fingerprintview' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.fingerprintview.js',
			),
			'styles' => array(
				'themes/default/jquery.wikibase.fingerprintview.css',
			),
			'dependencies' => array(
				'mediawiki.Title',
				'wikibase',
				'jquery.ui.TemplatedWidget',
				'jquery.wikibase.descriptionview',
				'jquery.wikibase.edittoolbar',
				'jquery.wikibase.labelview',
				'jquery.wikibase.toolbarcontroller',
			),
			'messages' => array(
				'wikibase-fingerprintview-input-help-message',
			),
		),

		'jquery.wikibase.listview' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.listview.js',
				'jquery.wikibase.listview.ListItemAdapter.js',
			),
			'dependencies' => array(
				'jquery.NativeEventHandler',
				'jquery.ui.TemplatedWidget',
				'jquery.ui.widget',
			),
		),

		'jquery.wikibase.pagesuggester' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.pagesuggester.js',
			),
			'dependencies' => array(
				'jquery.ui.suggester',
				'wikibase.sites',
			),
		),

		'jquery.wikibase.referenceview' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.referenceview.js',
			),
			'dependencies' => array(
				'jquery.removeClassByRegex',
				'jquery.wikibase.listview',
				'jquery.wikibase.snaklistview',
				'jquery.wikibase.toolbarcontroller',
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
				'jquery.wikibase.sitelinkgroupview',
			),
			'messages' => array(
				'wikibase-sitelinkgroupview-input-help-message',
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
				'jquery.ui.TemplatedWidget',
				'jquery.wikibase.sitelinklistview',
				'mediawiki.jqueryMsg', // for {{plural}} and {{gender}} support in messages
				'wikibase.sites',
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
				'jquery.tablesorter',
				'jquery.ui.TemplatedWidget',
				'jquery.wikibase.addtoolbar',
				'jquery.wikibase.edittoolbar',
				'jquery.wikibase.listview',
				'jquery.wikibase.sitelinkview',
				'jquery.wikibase.toolbarcontroller',
				'wikibase',
				'wikibase.datamodel',
				'wikibase.templates',
				'wikibase.utilities', // wikibase.utilities.ui
			),
			'messages' => array(
				'parentheses',
				'wikibase-badgeselector-badge-placeholder-title',
				'wikibase-propertyedittool-counter-entrieslabel',
				'wikibase-propertyedittool-counter-pending-tooltip',
				'wikibase-sitelink-site-edit-placeholder',
				'wikibase-sitelink-page-edit-placeholder',
				'wikibase-sitelinks-sitename-columnheading',
				'wikibase-sitelinks-sitename-columnheading-special',
				'wikibase-sitelinks-siteid-columnheading',
				'wikibase-sitelinks-link-columnheading',
				'wikibase-sitelinksedittool-full',
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
				'jquery.wikibase.badgeselector',
				'jquery.wikibase.pagesuggester',
				'jquery.wikibase.siteselector',
				'jquery.wikibase.toolbarcontroller',
				'mediawiki.util',
				'wikibase.datamodel',
				'wikibase.sites',
				'wikibase.templates',
			),
			'messages' => array(
				'wikibase-add-badges',
				'wikibase-sitelinks-input-help-message',
			),
		),

		'jquery.wikibase.siteselector' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.siteselector.js',
			),
			'dependencies' => array(
				'jquery.event.special.eachchange',
				'jquery.ui.ooMenu',
				'jquery.ui.suggester',
			),
		),

		'jquery.wikibase.snaklistview' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.snaklistview.js',
			),
			'dependencies' => array(
				'jquery.NativeEventHandler',
				'jquery.ui.TemplatedWidget',
				'jquery.ui.widget',
				'jquery.wikibase.listview',
				'jquery.wikibase.snakview',
				'wikibase.datamodel',
			),
			'messages' => array(
				'wikibase-claimview-snak-tooltip',
				'wikibase-claimview-snak-new-tooltip',
			),
		),

		'jquery.wikibase.statementview' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.statementview.js',
				'jquery.wikibase.statementview.RankSelector.js',
			),
			'styles' => array(
				'themes/default/jquery.wikibase.statementview.RankSelector.css',
			),
			'dependencies' => array(
				'jquery.ui.TemplatedWidget',
				'jquery.ui.menu',
				'jquery.ui.position',
				'jquery.ui.toggler',
				'util.inherit',
				'jquery.wikibase.claimview',
				'jquery.wikibase.listview',
				'jquery.wikibase.referenceview',
				'jquery.wikibase.toolbarcontroller',
				'wikibase.datamodel',
				'wikibase.utilities',
			),
			'messages' => array(
				'wikibase-statementview-rank-preferred',
				'wikibase-statementview-rank-normal',
				'wikibase-statementview-rank-deprecated',
				'wikibase-statementview-referencesheading-pendingcountersubject',
				'wikibase-statementview-referencesheading-pendingcountertooltip',
				'wikibase-addreference',
			),
		),

		'jquery.wikibase.wbtooltip' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase.wbtooltip.js',
			),
			'styles' => array(
				'themes/default/jquery.wikibase.wbtooltip.css'
			),
			'dependencies' => array(
				'jquery.tipsy',
				'jquery.ui.toggler',
				'jquery.ui.widget',
			),
			'messages' => array(
				'wikibase-tooltip-error-details',
			),
		),

	);

	return array_merge(
		$modules,
		include( __DIR__ . '/snakview/resources.php' ),
		include( __DIR__ . '/toolbar/resources.php' )
	);

} );
