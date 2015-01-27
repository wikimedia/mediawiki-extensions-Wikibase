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
	$moduleBase = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => $remoteExtPathParts[1],
	);

	$modules = array(

		'jquery.wikibase.aliasesview.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.aliasesview.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.aliasesview',
				'wikibase.datamodel.MultiTerm',
			),
		),

		'jquery.wikibase.badgeselector.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.badgeselector.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.badgeselector',
				'mediawiki.Title',
				'wikibase.datamodel',
				'wikibase.store.EntityStore',
				'wikibase.store.FetchedContent',
			),
		),

		'jquery.wikibase.statementgrouplabelscroll.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.statementgrouplabelscroll.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.statementgrouplabelscroll',
			),
		),

		'jquery.wikibase.statementgrouplistview.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.statementgrouplistview.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.statementgrouplistview',
				'wikibase.datamodel.Claim',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.Statement',
				'wikibase.datamodel.StatementGroup',
				'wikibase.datamodel.StatementGroupSet',
				'wikibase.datamodel.StatementList',
			),
		),

		'jquery.wikibase.statementgroupview.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.statementgroupview.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.statementgroupview',
				'wikibase.datamodel.Claim',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.PropertySomeValueSnak',
				'wikibase.datamodel.Statement',
				'wikibase.datamodel.StatementGroup',
				'wikibase.datamodel.StatementList',
			),
		),

		'jquery.wikibase.statementlistview.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.statementlistview.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.statementlistview',
				'wikibase.datamodel.Claim',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.Statement',
				'wikibase.datamodel.StatementList',
			),
		),

		'jquery.wikibase.descriptionview.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.descriptionview.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.descriptionview',
				'wikibase.datamodel.Term',
			),
		),

		'jquery.wikibase.entityselector.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.entityselector.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.entityselector',
			),
		),

		'jquery.wikibase.entityview.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.entityview.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.entityview',
				'wikibase.datamodel.Property',
			),
		),

		'jquery.wikibase.entitytermsview.tests' => $moduleBase + array(
				'scripts' => array(
					'jquery.wikibase.entitytermsview.tests.js',
				),
				'dependencies' => array(
					'jquery.wikibase.entitytermsview',
					'wikibase.datamodel.MultiTerm',
					'wikibase.datamodel.Term',
				),
			),

		'jquery.wikibase.entitytermsforlanguagelistview.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.entitytermsforlanguagelistview.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.entitytermsforlanguagelistview',
				'wikibase.datamodel.MultiTerm',
				'wikibase.datamodel.Term',
			),
		),

		'jquery.wikibase.entitytermsforlanguageview.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.entitytermsforlanguageview.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.entitytermsforlanguageview',
			),
		),

		'jquery.wikibase.itemview.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.itemview.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.itemview',
				'wikibase.datamodel.Item',
			),
		),

		'jquery.wikibase.labelview.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.labelview.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.labelview',
				'wikibase.datamodel.Term',
			),
		),

		'jquery.wikibase.listview.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.listview.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.listview',
			),
		),

		'jquery.wikibase.pagesuggester.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.pagesuggester.tests.js'
			),
			'dependencies' => array(
				'jquery.wikibase.pagesuggester',
			),
		),

		'jquery.wikibase.propertyview.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.propertyview.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.propertyview',
				'wikibase.datamodel.Property',
			),
		),

		'jquery.wikibase.referenceview.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.referenceview.tests.js',
			),
			'dependencies' => array(
				'jquery.valueview.ExpertStore',
				'jquery.wikibase.referenceview',
				'mediawiki.Title',
				'wikibase.datamodel',
				'wikibase.store.FetchedContent',
				'wikibase.ValueViewBuilder',
				'valueFormatters'
			),
		),

		'jquery.wikibase.sitelinkgrouplistview.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.sitelinkgrouplistview.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.sitelinkgrouplistview',
				'wikibase.datamodel',
				'wikibase.tests.qunit.testrunner',
			),
		),

		'jquery.wikibase.sitelinkgroupview.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.sitelinkgroupview.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.sitelinkgroupview',
				'wikibase.datamodel',
				'wikibase.store.EntityStore',
				'wikibase.tests.qunit.testrunner',
			),
		),

		'jquery.wikibase.sitelinklistview.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.sitelinklistview.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.sitelinklistview',
				'wikibase.datamodel',
				'wikibase.store.EntityStore',
				'wikibase.tests.qunit.testrunner',
			),
		),

		'jquery.wikibase.sitelinkview.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.sitelinkview.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.sitelinkview',
				'wikibase.datamodel',
				'wikibase.store.EntityStore',
				'wikibase.tests.qunit.testrunner',
			),
		),

		'jquery.wikibase.snaklistview.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.snaklistview.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.snaklistview',
				'wikibase.store.FetchedContent',
				'wikibase.ValueViewBuilder',
				'wikibase.datamodel',
				'mediawiki.Title',
				'jquery.valueview',
				'valueFormatters'
			),
		),

		'jquery.wikibase.statementview.RankSelector.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.statementview.RankSelector.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.statementview',
				'wikibase.datamodel',
			),
		),

		'jquery.wikibase.statementview.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.statementview.tests.js',
			),
			'dependencies' => array(
				'dataValues.values',
				'jquery.wikibase.statementview',
				'test.sinonjs',
				'wikibase.datamodel.Claim',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.PropertyValueSnak',
				'wikibase.datamodel.Reference',
				'wikibase.datamodel.ReferenceList',
				'wikibase.datamodel.Statement',
			),
		),

	);

	return array_merge(
		$modules,
		include( __DIR__ . '/snakview/resources.php' ),
		include( __DIR__ . '/toolbar/resources.php' )
	);

} );
