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
			'wikibase.view.ControllerViewFactory',
			'wikibase.tests',
		]
	],
	'wikibase.tests' => $moduleBase + [
		'scripts' => [
			'wikibase/wikibase.tests.js',
		],
		'dependencies' => [
			'wikibase',
		],
	],

	'wikibase.view.tests.ViewFactoryFactory' => $packageFilesModuleBase + [
		'packageFiles' => [
			'tests/qunit/wikibase/view/ViewFactoryFactory.tests.js',
			'resources/wikibase/view/ViewFactoryFactory.js',
		],
		'dependencies' => [
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
			'wikibase.view.ControllerViewFactory'
		]
	],
	'wikibase.view.tests.ToolbarViewController' => $packageFilesModuleBase + [
		'packageFiles' => [
			'tests/qunit/wikibase/view/ToolbarViewController.tests.js',

			'tests/qunit/wikibase/view/testViewController.js',
			'resources/wikibase/view/ToolbarViewController.js',
			'resources/wikibase/view/ViewController.js',
		],
		'dependencies' => [
			'wikibase.view.ControllerViewFactory',
			'wikibase.view.ReadModeViewFactory',
		],
	],
	'wikibase.view.tests.GuidGenerator' => $packageFilesModuleBase + [
		'packageFiles' => [
			'tests/qunit/wikibase/utilities/GuidGenerator.tests.js',
			'resources/wikibase/utilities/wikibase.utilities.GuidGenerator.js',
		],
		'dependencies' => [
			'wikibase'
		]
	],
	'wikibase.view.tests.ToolbarFactory' => $packageFilesModuleBase + [
		'packageFiles' => [
			'tests/qunit/wikibase/view/ToolbarFactory.tests.js',
			'resources/jquery/wikibase/toolbar/jquery.wikibase.toolbaritem.js',
			'resources/jquery/wikibase/toolbar/jquery.wikibase.toolbarbutton.js',
			'resources/jquery/wikibase/toolbar/jquery.wikibase.toolbar.js',
			'resources/jquery/wikibase/toolbar/jquery.wikibase.singlebuttontoolbar.js',
			'resources/jquery/wikibase/toolbar/jquery.wikibase.addtoolbar.js',
			'resources/jquery/wikibase/toolbar/jquery.wikibase.edittoolbar.js',
			'resources/jquery/wikibase/toolbar/jquery.wikibase.removetoolbar.js',
			'resources/wikibase/view/ToolbarFactory.js',
		],
		'dependencies' => [
			'wikibase.view.ControllerViewFactory',
			'jquery.wikibase.wbtooltip',
			'wikibase.api.RepoApi',
			'wikibase.view.__namespace',
		]
	],
];
