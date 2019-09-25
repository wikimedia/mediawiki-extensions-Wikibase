<?php

/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
$moduleBase = [
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'Wikibase/view/tests/qunit',
];
$packageFilesModuleBase = [
	'localBasePath' => dirname( dirname( __DIR__ ) ),
	'remoteExtPath' => 'Wikibase/view',
];

return [
	'wikibase.view.tests.getMockListItemAdapter' => $moduleBase + [
		'scripts' => 'getMockListItemAdapter.js',
		'dependencies' => [
			'jquery.wikibase.listview',
			'wikibase.tests',
		]
	],

	'wikibase.view.tests' => $moduleBase + [
		'scripts' => [
			'experts/wikibase.experts.modules.tests.js',
			'jquery/ui/jquery.ui.closeable.tests.js',
			'jquery/ui/jquery.ui.tagadata.tests.js',
			'jquery/ui/jquery.ui.EditableTemplatedWidget.tests.js',
			'jquery/ui/jquery.ui.TemplatedWidget.tests.js',
			'jquery/wikibase/snakview/snakview.tests.js',
			'jquery/wikibase/toolbar/jquery.wikibase.addtoolbar.tests.js',
			'jquery/wikibase/toolbar/jquery.wikibase.edittoolbar.tests.js',
			'jquery/wikibase/toolbar/jquery.wikibase.removetoolbar.tests.js',
			'jquery/wikibase/toolbar/jquery.wikibase.singlebuttontoolbar.tests.js',
			'jquery/wikibase/toolbar/jquery.wikibase.toolbar.tests.js',
			'jquery/wikibase/toolbar/jquery.wikibase.toolbarbutton.tests.js',
			'jquery/wikibase/toolbar/jquery.wikibase.toolbaritem.tests.js',
			'jquery/wikibase/jquery.wikibase.aliasesview.tests.js',
			'jquery/wikibase/jquery.wikibase.badgeselector.tests.js',
			'jquery/wikibase/jquery.wikibase.statementgrouplabelscroll.tests.js',
			'jquery/wikibase/jquery.wikibase.statementgrouplistview.tests.js',
			'jquery/wikibase/jquery.wikibase.statementgroupview.tests.js',
			'jquery/wikibase/jquery.wikibase.statementlistview.tests.js',
			'jquery/wikibase/jquery.wikibase.descriptionview.tests.js',
			'jquery/wikibase/jquery.wikibase.entityselector.tests.js',
			'jquery/wikibase/jquery.wikibase.entityview.tests.js',
			'jquery/wikibase/jquery.wikibase.entitytermsview.tests.js',
			'jquery/wikibase/jquery.wikibase.entitytermsforlanguagelistview.tests.js',
			'jquery/wikibase/jquery.wikibase.entitytermsforlanguageview.tests.js',
			'jquery/wikibase/jquery.wikibase.itemview.tests.js',
			'jquery/wikibase/jquery.wikibase.labelview.tests.js',
			'jquery/wikibase/jquery.wikibase.listview.tests.js',
			'jquery/wikibase/jquery.wikibase.pagesuggester.tests.js',
			'jquery/wikibase/jquery.wikibase.propertyview.tests.js',
			'jquery/wikibase/jquery.wikibase.referenceview.tests.js',
			'jquery/wikibase/jquery.wikibase.referenceview.tabsenabled.tests.js',
			'jquery/wikibase/jquery.wikibase.sitelinkgrouplistview.tests.js',
			'jquery/wikibase/jquery.wikibase.sitelinkgroupview.tests.js',
			'jquery/wikibase/jquery.wikibase.sitelinklistview.tests.js',
			'jquery/wikibase/jquery.wikibase.sitelinkview.tests.js',
			'jquery/wikibase/jquery.wikibase.snaklistview.tests.js',
			'jquery/wikibase/jquery.wikibase.statementview.RankSelector.tests.js',
			'jquery/wikibase/jquery.wikibase.statementview.tests.js',
			'jquery/jquery.removeClassByRegex.tests.js',
			'jquery/jquery.sticknode.tests.js',
			'jquery/jquery.util.getDirectionality.tests.js',
			'wikibase/entityChangers/AliasesChanger.tests.js',
			'wikibase/entityChangers/StatementsChanger.tests.js',
			'wikibase/entityChangers/StatementsChangerState.tests.js',
			'wikibase/entityChangers/DescriptionsChanger.tests.js',
			'wikibase/entityChangers/EntityTermsChanger.tests.js',
			'wikibase/entityChangers/LabelsChanger.tests.js',
			'wikibase/entityChangers/SiteLinksChanger.tests.js',
			'wikibase/entityChangers/SiteLinkSetsChanger.tests.js',
			'wikibase/utilities/ClaimGuidGenerator.tests.js',
			'wikibase/utilities/GuidGenerator.tests.js',
			'wikibase/view/testViewController.js',
			'wikibase/view/ToolbarViewController.tests.js',
			'wikibase/view/ToolbarFactory.tests.js',
			'wikibase/wikibase.WikibaseContentLanguages.tests.js',
			'wikibase/wikibase.getUserLanguages.tests.js',
			'wikibase/wikibase.getLanguageNameByCode.tests.js',
			'wikibase/templates.tests.js',
		],
		'dependencies' => [
			'dataValues.values',
			'jquery.ui.EditableTemplatedWidget',
			'jquery.util.getDirectionality',
			'jquery.wikibase.entityselector',
			'jquery.wikibase.entityview',
			'jquery.wikibase.listview',
			'jquery.wikibase.referenceview',
			'jquery.wikibase.statementview',
			'test.sinonjs',
			'wikibase.datamodel',
			'wikibase.entityChangers.EntityChangersFactory',
			'wikibase.experts.modules',
			'wikibase.getLanguageNameByCode',
			'wikibase.serialization.SnakDeserializer',
			'wikibase.serialization.SnakSerializer',
			'wikibase.serialization.StatementDeserializer',
			'wikibase.serialization.StatementSerializer',
			'wikibase.templates',
			'wikibase.tests.qunit.testrunner',
			'wikibase.tests',
			'wikibase.utilities.ClaimGuidGenerator',
			'wikibase.view.ToolbarFactory',
			'wikibase.view.ControllerViewFactory',
			'wikibase.view.tests.getMockListItemAdapter',
			'wikibase.WikibaseContentLanguages',
			'wikibase.getUserLanguages',
		],
	],

	'wikibase.view.tests.ViewFactoryFactory' => $packageFilesModuleBase + [
		'packageFiles' => [
			'tests/qunit/wikibase/view/ViewFactoryFactory.tests.js',
			'resources/wikibase/view/ViewFactoryFactory.js',
		],
		'dependencies' => [
			'test.sinonjs',
			'wikibase.view.ControllerViewFactory',
			'wikibase.view.ReadModeViewFactory',
		],
	],
	'wikibase.view.tests.CachingEntityStore' => $packageFilesModuleBase + [
		'packageFiles' => [
			'tests/qunit/wikibase/store/store.CachingEntityStore.tests.js',

			'resources/wikibase/store/store.CachingEntityStore.js',
			'resources/wikibase/store/store.EntityStore.js',
		],
	],
	'wikibase.view.tests.CombiningEntityStore' => $packageFilesModuleBase + [
		'packageFiles' => [
			'tests/qunit/wikibase/store/store.CombiningEntityStore.tests.js',

			'resources/wikibase/store/store.CombiningEntityStore.js',
			'resources/wikibase/store/store.EntityStore.js',
		],
	],
	'wikibase.view.tests.DataValueBasedEntityIdHtmlFormatter' => $packageFilesModuleBase + [
		'packageFiles' => [
			'tests/qunit/wikibase/entityIdFormatter/DataValueBasedEntityIdHtmlFormatter.tests.js',

			'resources/wikibase/entityIdFormatter/DataValueBasedEntityIdHtmlFormatter.js',
			'resources/wikibase/entityIdFormatter/EntityIdHtmlFormatter.js',
			'tests/qunit/wikibase/entityIdFormatter/testEntityIdHtmlFormatter.js',
		],
	],
	'wikibase.view.tests.DataValueBasedEntityIdPlainFormatter' => $packageFilesModuleBase + [
		'packageFiles' => [
			'tests/qunit/wikibase/entityIdFormatter/DataValueBasedEntityIdPlainFormatter.tests.js',

			'resources/wikibase/entityIdFormatter/DataValueBasedEntityIdPlainFormatter.js',
			'resources/wikibase/entityIdFormatter/DataValueBasedEntityIdHtmlFormatter.js',
			'resources/wikibase/entityIdFormatter/EntityIdPlainFormatter.js',
			'resources/wikibase/entityIdFormatter/EntityIdHtmlFormatter.js',
		],
	],

	'wikibase.view.tests.EventSingletonManager' => $packageFilesModuleBase + [
		'packageFiles' => [
			'tests/qunit/jquery/jquery.util.EventSingletonManager.tests.js',

			'resources/jquery/jquery.util.EventSingletonManager.js'
		],
	],

	'wikibase.view.tests.ValueViewBuilder' => $packageFilesModuleBase + [
		'packageFiles' => [
			'tests/qunit/wikibase/wikibase.ValueViewBuilder.tests.js',

			'resources/wikibase/wikibase.ValueViewBuilder.js'
		],
	],

	'wikibase.view.tests.ValueFactory' => $packageFilesModuleBase + [
		'packageFiles' => [
			'tests/qunit/wikibase/view/ViewFactory.tests.js',
		],
		'dependencies' => [
			'wikibase.view.ViewFactory'
		]
	],
];
