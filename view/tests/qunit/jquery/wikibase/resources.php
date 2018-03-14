<?php

/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	$moduleBase = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => 'Wikibase/view/tests/qunit/jquery/wikibase',
	];

	$modules = [

		'jquery.wikibase.aliasesview.tests' => $moduleBase + [
			'scripts' => [
				'jquery.wikibase.aliasesview.tests.js',
			],
			'dependencies' => [
				'jquery.wikibase.aliasesview',
				'wikibase.datamodel.MultiTerm',
			],
		],

		'jquery.wikibase.badgeselector.tests' => $moduleBase + [
			'scripts' => [
				'jquery.wikibase.badgeselector.tests.js',
			],
			'dependencies' => [
				'jquery.wikibase.badgeselector',
				'wikibase.datamodel',
			],
		],

		'jquery.wikibase.statementgrouplabelscroll.tests' => $moduleBase + [
			'scripts' => [
				'jquery.wikibase.statementgrouplabelscroll.tests.js',
			],
			'dependencies' => [
				'jquery.wikibase.statementgrouplabelscroll',
			],
		],

		'jquery.wikibase.statementgrouplistview.tests' => $moduleBase + [
			'scripts' => [
				'jquery.wikibase.statementgrouplistview.tests.js',
			],
			'dependencies' => [
				'jquery.wikibase.statementgrouplistview',
				'wikibase.datamodel.StatementGroup',
				'wikibase.datamodel.StatementGroupSet',
				'wikibase.datamodel.StatementList',
				'wikibase.tests.getMockListItemAdapter',
			],
		],

		'jquery.wikibase.statementgroupview.tests' => $moduleBase + [
			'scripts' => [
				'jquery.wikibase.statementgroupview.tests.js',
			],
			'dependencies' => [
				'jquery.wikibase.statementgroupview',
				'wikibase.datamodel.Claim',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.PropertySomeValueSnak',
				'wikibase.datamodel.Statement',
				'wikibase.datamodel.StatementGroup',
				'wikibase.datamodel.StatementList',
			],
		],

		'jquery.wikibase.statementlistview.tests' => $moduleBase + [
			'scripts' => [
				'jquery.wikibase.statementlistview.tests.js',
			],
			'dependencies' => [
				'jquery.wikibase.statementlistview',
				'wikibase.datamodel.Claim',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.Statement',
				'wikibase.datamodel.StatementList',
				'wikibase.tests.getMockListItemAdapter',
			],
		],

		'jquery.wikibase.descriptionview.tests' => $moduleBase + [
			'scripts' => [
				'jquery.wikibase.descriptionview.tests.js',
			],
			'dependencies' => [
				'jquery.wikibase.descriptionview',
				'wikibase.datamodel.Term',
			],
		],

		'jquery.wikibase.entityselector.tests' => $moduleBase + [
			'scripts' => [
				'jquery.wikibase.entityselector.tests.js',
			],
			'dependencies' => [
				'jquery.wikibase.entityselector',
			],
		],

		'jquery.wikibase.entityview.tests' => $moduleBase + [
			'scripts' => [
				'jquery.wikibase.entityview.tests.js',
			],
			'dependencies' => [
				'jquery.wikibase.entityview',
				'wikibase.datamodel.Property',
			],
		],

		'jquery.wikibase.entitytermsview.tests' => $moduleBase + [
				'scripts' => [
					'jquery.wikibase.entitytermsview.tests.js',
				],
				'dependencies' => [
					'jquery.wikibase.entitytermsview',
					'wikibase.datamodel.MultiTerm',
					'wikibase.datamodel.Term',
				],
			],

		'jquery.wikibase.entitytermsforlanguagelistview.tests' => $moduleBase + [
			'scripts' => [
				'jquery.wikibase.entitytermsforlanguagelistview.tests.js',
			],
			'dependencies' => [
				'jquery.wikibase.entitytermsforlanguagelistview',
				'wikibase.datamodel.MultiTerm',
				'wikibase.datamodel.Term',
			],
		],

		'jquery.wikibase.entitytermsforlanguageview.tests' => $moduleBase + [
			'scripts' => [
				'jquery.wikibase.entitytermsforlanguageview.tests.js',
			],
			'dependencies' => [
				'jquery.wikibase.entitytermsforlanguageview',
			],
		],

		'jquery.wikibase.itemview.tests' => $moduleBase + [
			'scripts' => [
				'jquery.wikibase.itemview.tests.js',
			],
			'dependencies' => [
				'jquery.wikibase.itemview',
				'wikibase.datamodel.Item',
				'wikibase.store.EntityStore',
			],
		],

		'jquery.wikibase.labelview.tests' => $moduleBase + [
			'scripts' => [
				'jquery.wikibase.labelview.tests.js',
			],
			'dependencies' => [
				'jquery.wikibase.labelview',
				'wikibase.datamodel.Term',
			],
		],

		'jquery.wikibase.listview.tests' => $moduleBase + [
			'scripts' => [
				'jquery.wikibase.listview.tests.js',
			],
			'dependencies' => [
				'jquery.wikibase.listview',
				'wikibase.tests.getMockListItemAdapter',
			],
		],

		'jquery.wikibase.pagesuggester.tests' => $moduleBase + [
			'scripts' => [
				'jquery.wikibase.pagesuggester.tests.js'
			],
			'dependencies' => [
				'jquery.wikibase.pagesuggester',
			],
		],

		'jquery.wikibase.propertyview.tests' => $moduleBase + [
			'scripts' => [
				'jquery.wikibase.propertyview.tests.js',
			],
			'dependencies' => [
				'jquery.wikibase.propertyview',
				'wikibase.datamodel.Property',
				'wikibase.store.EntityStore',
			],
		],

		'jquery.wikibase.referenceview.tests' => $moduleBase + [
			'scripts' => [
				'jquery.wikibase.referenceview.tests.js',
			],
			'dependencies' => [
				'jquery.wikibase.referenceview',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.Reference',
				'wikibase.datamodel.SnakList',
				'wikibase.tests.getMockListItemAdapter',
			],
		],

		'jquery.wikibase.sitelinkgrouplistview.tests' => $moduleBase + [
			'scripts' => [
				'jquery.wikibase.sitelinkgrouplistview.tests.js',
			],
			'dependencies' => [
				'jquery.wikibase.sitelinkgrouplistview',
				'wikibase.datamodel.SiteLink',
				'wikibase.datamodel.SiteLinkSet',
				'wikibase.tests.getMockListItemAdapter',
				'wikibase.tests.qunit.testrunner',
			],
		],

		'jquery.wikibase.sitelinkgroupview.tests' => $moduleBase + [
			'scripts' => [
				'jquery.wikibase.sitelinkgroupview.tests.js',
			],
			'dependencies' => [
				'jquery.wikibase.sitelinkgroupview',
				'wikibase.datamodel',
				'wikibase.tests.qunit.testrunner',
			],
		],

		'jquery.wikibase.sitelinklistview.tests' => $moduleBase + [
			'scripts' => [
				'jquery.wikibase.sitelinklistview.tests.js',
			],
			'dependencies' => [
				'jquery.wikibase.sitelinklistview',
				'wikibase.datamodel',
				'wikibase.tests.qunit.testrunner',
			],
		],

		'jquery.wikibase.sitelinkview.tests' => $moduleBase + [
			'scripts' => [
				'jquery.wikibase.sitelinkview.tests.js',
			],
			'dependencies' => [
				'jquery.wikibase.sitelinkview',
				'wikibase.datamodel',
				'wikibase.tests.qunit.testrunner',
			],
		],

		'jquery.wikibase.snaklistview.tests' => $moduleBase + [
			'scripts' => [
				'jquery.wikibase.snaklistview.tests.js',
			],
			'dependencies' => [
				'jquery.wikibase.snaklistview',
				'wikibase.datamodel',
				'wikibase.tests.getMockListItemAdapter',
			],
		],

		'jquery.wikibase.statementview.RankSelector.tests' => $moduleBase + [
			'scripts' => [
				'jquery.wikibase.statementview.RankSelector.tests.js',
			],
			'dependencies' => [
				'jquery.wikibase.statementview',
				'wikibase.datamodel',
			],
		],

		'jquery.wikibase.statementview.tests' => $moduleBase + [
			'scripts' => [
				'jquery.wikibase.statementview.tests.js',
			],
			'dependencies' => [
				'dataValues.values',
				'jquery.wikibase.statementview',
				'test.sinonjs',
				'wikibase.datamodel.Claim',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.Reference',
				'wikibase.datamodel.ReferenceList',
				'wikibase.datamodel.Statement',
				'wikibase.tests.getMockListItemAdapter',
			],
		],

	];

	return array_merge(
		$modules,
		include __DIR__ . '/snakview/resources.php',
		include __DIR__ . '/toolbar/resources.php'
	);
} );
