<?php

/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
return call_user_func( function() {
	$moduleTemplate = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => 'Wikibase/view/resources/wikibase/view',
	];

	$modules = [
		'wikibase.view.__namespace' => $moduleTemplate + [
			'scripts' => [
				'namespace.js'
			],
			'dependencies' => [
				'wikibase'
			]
		],

		'wikibase.view.ViewController' => $moduleTemplate + [
			'scripts' => 'ViewController.js',
			'dependencies' => [
				'util.inherit',
				'wikibase.view.__namespace',
			]
		],

		'wikibase.view.StructureEditorFactory' => $moduleTemplate + [
			'scripts' => 'StructureEditorFactory.js',
			'dependencies' => [
				'wikibase.view.__namespace',
			]
		],

		'wikibase.view.ToolbarFactory' => $moduleTemplate + [
			'scripts' => 'ToolbarFactory.js',
			'dependencies' => [
				'jquery.wikibase.addtoolbar',
				'jquery.wikibase.edittoolbar',
				'jquery.wikibase.removetoolbar',
				'wikibase.view.__namespace',
			]
		],

		'wikibase.view.ToolbarViewController' => $moduleTemplate + [
			'scripts' => 'ToolbarViewController.js',
			'dependencies' => [
				'util.inherit',
				'wikibase.view.__namespace',
				'wikibase.view.ViewController',
			],
			'messages' => [
				'wikibase-save-inprogress',
				'wikibase-publish-inprogress',
			]
		],

		'wikibase.view.ControllerViewFactory' => $moduleTemplate + [
			'scripts' => 'ControllerViewFactory.js',
			'dependencies' => [
				'mediawiki.cookie',
				'mediawiki.user',
				'wikibase.view.__namespace',
				'wikibase.view.ToolbarViewController',
				'wikibase.view.ViewFactory'
			]
		],

		'wikibase.view.ReadModeViewFactory' => $moduleTemplate + [
			'scripts' => 'ReadModeViewFactory.js',
			'dependencies' => [
				'wikibase.view.__namespace',
				'wikibase.view.ViewFactory'
			],
		],

		'wikibase.view.ViewFactoryFactory' => $moduleTemplate + [
			'scripts' => 'ViewFactoryFactory.js',
			'dependencies' => [
				'wikibase.view.__namespace',
				'wikibase.view.ReadModeViewFactory',
				'wikibase.view.ControllerViewFactory'
			],
		],

		'wikibase.view.ViewFactory' => $moduleTemplate + [
			'scripts' => [
				'ViewFactory.js'
			],
			'dependencies' => [
				'jquery.util.EventSingletonManager',
				'jquery.wikibase.entitytermsview',
				'jquery.wikibase.itemview',
				'jquery.wikibase.listview', // For ListItemAdapter
				'jquery.wikibase.propertyview',
				'jquery.wikibase.sitelinkgroupview',
				'jquery.wikibase.sitelinklistview',
				'jquery.wikibase.statementgrouplistview',
				'jquery.wikibase.statementgroupview',
				'jquery.wikibase.statementlistview',
				'jquery.wikibase.statementview',
				'wikibase.datamodel.MultiTerm',
				'wikibase.datamodel.Term',
				'wikibase.utilities.ClaimGuidGenerator',
				'wikibase.view.__namespace',
				'wikibase.ValueViewBuilder'
			],
			'messages' => [
				'wikibase-entitytermsview-input-help-message',
			]
		],
	];

	return $modules;
} );
