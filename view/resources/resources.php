<?php

use Wikibase\View\Module\TemplateModule;

/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
return call_user_func( function() {
	$moduleTemplate = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => 'Wikibase/view/resources',
	];

	$moduleBaseTemplate = [
		'localBasePath' => dirname( __DIR__, 1 ),
		'remoteExtPath' => 'Wikibase/view',
	];

	$modules = [
		'jquery.ui.EditableTemplatedWidget' => $moduleTemplate + [
			'scripts' => [
				'jquery/ui/jquery.ui.TemplatedWidget.js',
				'jquery/ui/jquery.ui.closeable.js',
				'jquery/ui/jquery.ui.EditableTemplatedWidget.js',
			],
			'styles' => [
				'jquery/ui/jquery.ui.closeable.css',
			],
			'dependencies' => [
				'wikibase.templates',
				'jquery.ui.widget',
				'util.inherit',
			],
			'targets' => [ 'desktop', 'mobile' ],
		],

		'jquery.wikibase.entityselector' => $moduleTemplate + [
			'scripts' => [
				'jquery/wikibase/jquery.wikibase.entityselector.js',
			],
			'styles' => [
				'jquery/wikibase/themes/default/jquery.wikibase.entityselector.css',
			],
			'dependencies' => [
				'jquery.event.special.eachchange',
				'jquery.throttle-debounce',
				'jquery.ui.suggester',
				'jquery.ui.widget',
			],
			'messages' => [
				'wikibase-entityselector-more',
				'wikibase-entityselector-notfound',
			],
		],

		'jquery.wikibase.entityview' => $moduleTemplate + [
			'scripts' => [
				'jquery/wikibase/jquery.wikibase.entityview.js',
			],
			'styles' => [
				'jquery/wikibase/themes/default/jquery.wikibase.entityview.css',
			],
			'dependencies' => [
				'jquery.ui.EditableTemplatedWidget',
			],
			'targets' => [ 'desktop', 'mobile' ],
		],

		'jquery.wikibase.toolbar.styles' => $moduleTemplate + [
			'styles' => [
				'jquery/wikibase/toolbar/themes/default/jquery.wikibase.toolbar.css',
				'jquery/wikibase/toolbar/themes/default/jquery.wikibase.toolbarbutton.css',
			],
		],

		// Common styles independent from JavaScript being enabled or disabled.
		'wikibase.common' => $moduleTemplate + [
			'styles' => [
				// Order must be hierarchical, do not order alphabetically
				'wikibase/wikibase.less',
				'jquery/wikibase/themes/default/jquery.wikibase.aliasesview.css',
				'jquery/wikibase/themes/default/jquery.wikibase.descriptionview.css',
				'jquery/wikibase/themes/default/jquery.wikibase.entityview.css',
				'jquery/wikibase/themes/default/jquery.wikibase.entitytermsview.css',
				'jquery/wikibase/themes/default/jquery.wikibase.entitytermsforlanguagelistview.css',
				'jquery/wikibase/themes/default/jquery.wikibase.entitytermsforlanguageview.css',
				'jquery/wikibase/themes/default/jquery.wikibase.labelview.css',
				'jquery/wikibase/themes/default/jquery.wikibase.sitelinkgrouplistview.css',
				'jquery/wikibase/themes/default/jquery.wikibase.sitelinkgroupview.css',
				'jquery/wikibase/themes/default/jquery.wikibase.sitelinklistview.css',
				'jquery/wikibase/themes/default/jquery.wikibase.sitelinkview.css',
				'jquery/wikibase/themes/default/jquery.wikibase.statementgroupview.css',
			]
		],

		'wikibase.mobile' => $moduleTemplate + [
			'styles' => [
				'wikibase/wikibase.mobile.css',
				'jquery/wikibase/themes/default/jquery.wikibase.statementview.RankSelector.css',
			],
			'targets' => 'mobile'
		],

		'wikibase.templates' => $moduleTemplate + [
			'class' => TemplateModule::class,
			'scripts' => 'wikibase/templates.js',
			'dependencies' => [
				'jquery.getAttrs'
			],
			'targets' => [ 'desktop', 'mobile' ],
		],

		'wikibase.ValueFormatterFactory' => $moduleTemplate + [
			'scripts' => [
				'wikibase/wikibase.ValueFormatterFactory.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase',
			],
		],

		'wikibase.entityChangers.EntityChangersFactory' => $moduleTemplate + [
			'scripts' => [
				'wikibase/entityChangers/namespace.js',

				'wikibase/entityChangers/AliasesChanger.js',
				'wikibase/entityChangers/StatementsChanger.js',
				'wikibase/entityChangers/StatementsChangerState.js',
				'wikibase/entityChangers/DescriptionsChanger.js',
				'wikibase/entityChangers/EntityTermsChanger.js',
				'wikibase/entityChangers/LabelsChanger.js',
				'wikibase/entityChangers/SiteLinksChanger.js',
				'wikibase/entityChangers/SiteLinkSetsChanger.js',

				'wikibase/entityChangers/EntityChangersFactory.js',
			],
			'dependencies' => [
				'wikibase',
				'wikibase.api.RepoApiError',
				'wikibase.datamodel', // for AliasesChanger.js
				'wikibase.serialization',
			]
		],

		'wikibase.utilities.ClaimGuidGenerator' => $moduleTemplate + [
			'scripts' => [
				'wikibase/utilities/wikibase.utilities.GuidGenerator.js',
				'wikibase/utilities/wikibase.utilities.ClaimGuidGenerator.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.utilities',
			],
			'targets' => [ 'desktop', 'mobile' ],
		],

		'wikibase.utilities' => $moduleTemplate + [
			'scripts' => [
				'wikibase/utilities/wikibase.utilities.js',
				'wikibase/utilities/wikibase.utilities.ui.js',
			],
			'styles' => [
				'wikibase/utilities/wikibase.utilities.ui.css',
			],
			'dependencies' => [
				'wikibase',
				'mediawiki.language',
				'mediawiki.jqueryMsg'
			],
			'targets' => [ 'desktop', 'mobile' ],
		],

		'wikibase.view.__namespace' => $moduleTemplate + [
			'scripts' => [
				'wikibase/view/namespace.js'
			],
			'dependencies' => [
				'wikibase'
			]
		],

		'wikibase.view.ToolbarFactory' => $moduleTemplate + [
			'scripts' => [
				'jquery/wikibase/toolbar/jquery.wikibase.toolbaritem.js',
				'jquery/wikibase/toolbar/jquery.wikibase.toolbarbutton.js', // uses toolbaritem
				'jquery/wikibase/toolbar/jquery.wikibase.toolbar.js',
				'jquery/wikibase/toolbar/jquery.wikibase.singlebuttontoolbar.js',
				'jquery/wikibase/toolbar/jquery.wikibase.addtoolbar.js',
				'jquery/wikibase/toolbar/jquery.wikibase.edittoolbar.js',
				'jquery/wikibase/toolbar/jquery.wikibase.removetoolbar.js',
				'wikibase/view/ToolbarFactory.js',
			],
			'styles' => [
				'jquery/wikibase/toolbar/themes/default/jquery.wikibase.toolbaritem.css',
				'jquery/wikibase/toolbar/themes/default/jquery.wikibase.edittoolbar.css',
			],
			'dependencies' => [
				'jquery.ui.EditableTemplatedWidget', // for jquery.wikibase.toolbaritem
				'jquery.wikibase.toolbar.styles',
				'jquery.wikibase.wbtooltip',
				'wikibase.api.RepoApiError',
				'wikibase.view.__namespace',
			],
			'messages' => [
				'wikibase-add',
				'wikibase-cancel',
				'wikibase-edit',
				'wikibase-remove',
				'wikibase-remove-inprogress',
				'wikibase-save',
				'wikibase-publish',
			],
		],

		'wikibase.view.ReadModeViewFactory' => $moduleTemplate + [
			'scripts' => 'wikibase/view/ReadModeViewFactory.js',
			'dependencies' => [
				'wikibase.view.__namespace',
				'wikibase.view.ControllerViewFactory'
			],
		],
		'wikibase.view.ControllerViewFactory' => $moduleBaseTemplate + [
			'packageFiles' => [
				'resources/wikibase/view/ControllerViewFactory.js',

				'resources/wikibase/view/ViewController.js',
				'resources/wikibase/view/ToolbarViewController.js',
				'resources/wikibase/view/ViewFactory.js',
				'resources/jquery/jquery.util.EventSingletonManager.js',
				'resources/wikibase/wikibase.ValueViewBuilder.js',
				'resources/jquery/wikibase/jquery.wikibase.pagesuggester.js',
				'resources/jquery/wikibase/snakview/snakview.ViewState.js',
				'resources/jquery/wikibase/snakview/snakview.variations.js',
				'resources/jquery/wikibase/snakview/snakview.variations.Variation.js',
				'resources/jquery/wikibase/snakview/snakview.variations.NoValue.js',
				'resources/jquery/wikibase/snakview/snakview.variations.SomeValue.js',
				'resources/jquery/wikibase/snakview/snakview.variations.Value.js',
				'resources/jquery/wikibase/snakview/snakview.js',
				'resources/jquery/wikibase/snakview/snakview.SnakTypeSelector.js',
				'resources/jquery/wikibase/jquery.wikibase.aliasesview.js',
				'resources/jquery/wikibase/jquery.wikibase.badgeselector.js',
				'resources/jquery/wikibase/jquery.wikibase.descriptionview.js',
				'resources/jquery/wikibase/jquery.wikibase.entitytermsforlanguagelistview.js',
				'resources/jquery/wikibase/jquery.wikibase.entitytermsforlanguageview.js',
				'resources/jquery/wikibase/jquery.wikibase.entitytermsview.js',
				'resources/jquery/wikibase/jquery.wikibase.entityview.js',
				'resources/jquery/wikibase/jquery.wikibase.itemview.js',
				'resources/jquery/wikibase/jquery.wikibase.labelview.js',
				'resources/jquery/wikibase/jquery.wikibase.listview.js',
				'resources/jquery/wikibase/jquery.wikibase.listview.ListItemAdapter.js',
				'resources/jquery/wikibase/jquery.wikibase.pagesuggester.js',
				'resources/jquery/wikibase/jquery.wikibase.propertyview.js',
				'resources/jquery/wikibase/jquery.wikibase.referenceview.js',
				'resources/jquery/wikibase/jquery.wikibase.sitelinkgrouplistview.js',
				'resources/jquery/wikibase/jquery.wikibase.sitelinkgroupview.js',
				'resources/jquery/wikibase/jquery.wikibase.sitelinklistview.js',
				'resources/jquery/wikibase/jquery.wikibase.sitelinkview.js',
				'resources/jquery/wikibase/jquery.wikibase.snaklistview.js',
				'resources/jquery/wikibase/jquery.wikibase.statementgrouplabelscroll.js',
				'resources/jquery/wikibase/jquery.wikibase.statementgrouplistview.js',
				'resources/jquery/wikibase/jquery.wikibase.statementgroupview.js',
				'resources/jquery/wikibase/jquery.wikibase.statementlistview.js',
				'resources/jquery/wikibase/jquery.wikibase.statementview.js',
				'resources/jquery/wikibase/jquery.wikibase.statementview.RankSelector.js',
				'resources/jquery/ui/jquery.ui.tagadata.js',
				'resources/jquery/jquery.sticknode.js',
				'resources/jquery/jquery.removeClassByRegex.js',
				'lib/wikibase-data-values-value-view/lib/jquery.ui/jquery.ui.toggler.js',

			],
			'styles' => [
				'resources/jquery/wikibase/themes/default/jquery.wikibase.badgeselector.css',
				'resources/jquery/wikibase/themes/default/jquery.wikibase.sitelinkview.css',
				'resources/jquery/wikibase/themes/default/jquery.wikibase.sitelinklistview.css',
				'resources/jquery/wikibase/themes/default/jquery.wikibase.sitelinkgroupview.mw-collapsible.css',
				'resources/jquery/wikibase/themes/default/jquery.wikibase.sitelinkgroupview.css',
				'resources/jquery/wikibase/themes/default/jquery.wikibase.sitelinkgrouplistview.css',
				'resources/jquery/wikibase/themes/default/jquery.wikibase.labelview.css',
				'resources/jquery/wikibase/themes/default/jquery.wikibase.descriptionview.css',
				'resources/jquery/ui/jquery.ui.tagadata.css',
				'resources/jquery/wikibase/themes/default/jquery.wikibase.aliasesview.css',
				'resources/jquery/wikibase/themes/default/jquery.wikibase.entitytermsforlanguageview.css',
				'resources/jquery/wikibase/themes/default/jquery.wikibase.entitytermsforlanguagelistview.css',
				'resources/jquery/wikibase/themes/default/jquery.wikibase.entitytermsview.css',
				'resources/jquery/wikibase/snakview/themes/default/snakview.SnakTypeSelector.css',
				'resources/jquery/wikibase/themes/default/jquery.wikibase.statementview.RankSelector.css',
				'lib/wikibase-data-values-value-view/lib/jquery.ui/jquery.ui.toggler.css',
				'resources/jquery/wikibase/themes/default/jquery.wikibase.entityview.css',
			],
			'dependencies' => [
				'dataValues',
				'dataValues.DataValue', // For snakview
				'jquery.animateWithEvent',
				'jquery.event.special.eachchange',
				'jquery.ui.position',
				'jquery.ui.widget',
				'jquery.ui.core',
				'jquery.ui.EditableTemplatedWidget',
				'jquery.ui.menu',
				'jquery.ui.suggester',
				'jquery.ui.tabs',
				'jquery.util.getDirectionality',
				'jquery.event.special.eachchange',
				'jquery.inputautoexpand',
				'jquery.throttle-debounce',
				'jquery.wikibase.entityselector',
				'jquery.wikibase.siteselector',
				'wikibase.buildErrorOutput',
				'wikibase.getLanguageNameByCode',
				'wikibase.sites',
				'wikibase.templates',
				'wikibase.datamodel',
				'wikibase.serialization',
				'wikibase.utilities', // wikibase.utilities.ui
				'wikibase.utilities.ClaimGuidGenerator',
				'wikibase.view.__namespace',
				'wikibase',
				'jquery.valueview',
				'mediawiki.api',
				'mediawiki.cookie',
				'mediawiki.jqueryMsg', // for {{plural}} and {{gender}} support in messages
				'mediawiki.legacy.shared', // For snakview
				'mediawiki.user',
				'mediawiki.util',
				'mw.config.values.wbRefTabsEnabled',
				'mw.config.values.wbRepo',
				'oojs-ui',
				'util.highlightSubstring',
				'util.inherit',
				'wikibase.tainted-ref.init',
			],
			'messages' => [
				'parentheses',
				'wikibase-addqualifier',
				'wikibase-addreference',
				'wikibase-badgeselector-badge-placeholder-title',
				'wikibase-claimview-snak-tooltip',
				'wikibase-claimview-snak-new-tooltip',
				'wikibase-entitytermsforlanguagelistview-aliases',
				'wikibase-entitytermsforlanguagelistview-description',
				'wikibase-entitytermsforlanguagelistview-label',
				'wikibase-entitytermsforlanguagelistview-language',
				'wikibase-entitytermsforlanguagelistview-less',
				'wikibase-entitytermsforlanguagelistview-more',
				'wikibase-entitytermsview-input-help-message',
				'wikibase-aliases-separator',
				'wikibase-aliases-input-help-message',
				'wikibase-alias-edit-placeholder',
				'wikibase-description-edit-placeholder',
				'wikibase-description-edit-placeholder-language-aware',
				'wikibase-description-empty',
				'wikibase-statementgrouplistview-add',
				'wikibase-description-empty',
				'wikibase-entitytermsview-entitytermsforlanguagelistview-configure-link-label',
				'wikibase-entitytermsview-entitytermsforlanguagelistview-configure-link',
				'wikibase-entitytermsview-entitytermsforlanguagelistview-toggler',
				'wikibase-label-edit-placeholder',
				'wikibase-label-edit-placeholder-language-aware',
				'wikibase-label-empty',
				'wikibase-label-input-help-message',
				'wikibase-outdated-client-script',
				'wikibase-referenceview-tabs-manual',
				'wikibase-refresh-for-missing-datatype',
				'wikibase-remove',
				'wikibase-sitelink-site-edit-placeholder',
				'wikibase-sitelink-page-edit-placeholder',
				'wikibase-sitelinkgroupview-input-help-message',
				'wikibase-sitelinks-counter',
				'wikibase-snakview-property-input-placeholder',
				'wikibase-snakview-choosesnaktype',
				'wikibase-snakview-snaktypeselector-value',
				'wikibase-snakview-snaktypeselector-somevalue',
				'wikibase-snakview-snaktypeselector-novalue',
				'wikibase-snakview-variation-datavaluetypemismatch',
				'wikibase-snakview-variation-datavaluetypemismatch-details',
				'wikibase-snakview-variation-nonewvaluefordeletedproperty',
				'wikibase-snakview-variations-novalue-label',
				'wikibase-snakview-variations-somevalue-label',
				'wikibase-statementgrouplistview-add-tooltip',
				'wikibase-statementlistview-add',
				'wikibase-statementlistview-add-tooltip',
				'wikibase-statementview-rank-preferred',
				'wikibase-statementview-rank-tooltip-preferred',
				'wikibase-statementview-rank-normal',
				'wikibase-statementview-rank-tooltip-normal',
				'wikibase-statementview-rank-deprecated',
				'wikibase-statementview-rank-tooltip-deprecated',
				'wikibase-statementview-references-counter',
				// For ToolbarViewController:
				'wikibase-save-inprogress',
				'wikibase-publish-inprogress',
			],
		],
	];

	return $modules;
} );
