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

	$modules = [
		// Also used by WikibaseLexeme.
		// TODO: Rename to "jquery.ui.TemplatedWidget" or merge into a higher-level
		// module bundle that is shared between WikibaseRepo and WikibaseLexeme.
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

		// Used by both WikibaseView and by WikibaseRepo.
		// TODO: Create a common bundle for shared dependencies like this.
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

		'jquery.wikibase.listview' => $moduleTemplate + [
			'scripts' => [
				'jquery/wikibase/jquery.wikibase.listview.js',
				'jquery/wikibase/jquery.wikibase.listview.ListItemAdapter.js',
			],
			'dependencies' => [
				'jquery.ui.EditableTemplatedWidget',
				'jquery.ui.widget',
			],
		],

		'jquery.wikibase.referenceview' => $moduleTemplate + [
			'scripts' => [
				'jquery/jquery.removeClassByRegex.js',
				'jquery/wikibase/jquery.wikibase.referenceview.js',
			],
			'dependencies' => [
				'jquery.ui.EditableTemplatedWidget',
				'jquery.wikibase.listview',
				'jquery.ui.tabs',
				'mw.config.values.wbRefTabsEnabled',
				'wikibase.datamodel',
			],
			'messages' => [
				'wikibase-referenceview-tabs-manual',
			],
		],

		'jquery.wikibase.statementview' => $moduleTemplate + [
			'scripts' => [
				'jquery/wikibase/snakview/snakview.variations.js',
				'jquery/wikibase/snakview/snakview.variations.Variation.js',
				'jquery/wikibase/snakview/snakview.variations.NoValue.js',
				'jquery/wikibase/snakview/snakview.variations.SomeValue.js',
				'jquery/wikibase/snakview/snakview.variations.Value.js',
				'jquery/wikibase/snakview/snakview.ViewState.js',
				'jquery/wikibase/snakview/snakview.js',
				'jquery/wikibase/snakview/snakview.SnakTypeSelector.js',
				'jquery/wikibase/jquery.wikibase.snaklistview.js',
				'jquery/wikibase/jquery.wikibase.statementview.js',
				'jquery/wikibase/jquery.wikibase.statementview.RankSelector.js',
			],
			'styles' => [
				'jquery/wikibase/snakview/themes/default/snakview.SnakTypeSelector.css',
			],
			'dependencies' => [
				'jquery.event.special.eachchange',
				'jquery.ui.EditableTemplatedWidget',
				'jquery.ui.menu',
				'jquery.ui.position',
				'jquery.ui.toggler',
				'util.inherit',
				'jquery.wikibase.entityselector',
				'jquery.wikibase.listview',
				'jquery.wikibase.referenceview',
				'jquery.wikibase.statementview.RankSelector.styles',
				'wikibase.datamodel',
				'wikibase.serialization.SnakDeserializer',
				'wikibase.serialization.SnakSerializer',
				'wikibase.utilities',
				'dataValues',
				'dataValues.DataValue', // For snakview
				'mediawiki.legacy.shared', // For snakview
				'mw.config.values.wbRepo',
			],
			'messages' => [
				'wikibase-addqualifier',
				'wikibase-addreference',
				'wikibase-outdated-client-script',
				'wikibase-refresh-for-missing-datatype',
				'wikibase-claimview-snak-tooltip',
				'wikibase-claimview-snak-new-tooltip',
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
			'styles' => [
				'jquery/wikibase/themes/default/jquery.wikibase.statementview.RankSelector.css',
			],
			'targets' => [ 'desktop', 'mobile' ],
		],

		'jquery.wikibase.toolbar.styles' => $moduleTemplate + [
			'styles' => [
				'jquery/wikibase/toolbar/themes/default/jquery.wikibase.toolbar.css',
			],
		],

		'jquery.wikibase.toolbarbutton.styles' => $moduleTemplate + [
			'styles' => [
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
				'wikibase/wikibase.mobile.css'
			],
			'dependencies' => [
				'jquery.wikibase.statementview.RankSelector.styles',
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
				'wikibase.datamodel.MultiTerm', // for AliasesChanger.js
				'wikibase.serialization.StatementDeserializer', // for EntityChangersFactory.js
				'wikibase.serialization.StatementSerializer', // for EntityChangersFactory.js
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
				'jquery.wikibase.toolbarbutton.styles',
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

		'wikibase.view.ControllerViewFactory' => $moduleTemplate + [
			'scripts' => [
				'wikibase/view/ViewController.js',
				'wikibase/view/ToolbarViewController.js',
				'wikibase/view/ControllerViewFactory.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.view.__namespace',
				'mediawiki.cookie',
				'mediawiki.user',
				'wikibase.view.ViewFactory'
			],
			'messages' => [
				// For ToolbarViewController:
				'wikibase-save-inprogress',
				'wikibase-publish-inprogress',
			],
		],

		'wikibase.view.ReadModeViewFactory' => $moduleTemplate + [
			'scripts' => 'wikibase/view/ReadModeViewFactory.js',
			'dependencies' => [
				'wikibase.view.__namespace',
				'wikibase.view.ViewFactory'
			],
		],

		'wikibase.view.ViewFactory' => $moduleTemplate + [
			'packageFiles' => [
				'wikibase/view/ViewFactory.js',

				'jquery/jquery.util.EventSingletonManager.js',
				'wikibase/wikibase.ValueViewBuilder.js',

				'jquery/wikibase/jquery.wikibase.pagesuggester.js',
				'jquery/wikibase/jquery.wikibase.badgeselector.js',
				'jquery/wikibase/jquery.wikibase.sitelinkview.js',
				'jquery/wikibase/jquery.wikibase.sitelinklistview.js',
				'jquery/wikibase/jquery.wikibase.sitelinkgroupview.js',
				'jquery/wikibase/jquery.wikibase.sitelinkgrouplistview.js',
				'jquery/wikibase/jquery.wikibase.propertyview.js',
				'jquery/wikibase/jquery.wikibase.labelview.js',
				'jquery/wikibase/jquery.wikibase.itemview.js',
				'jquery/wikibase/jquery.wikibase.descriptionview.js',
				'jquery/wikibase/jquery.wikibase.aliasesview.js',
				'jquery/wikibase/jquery.wikibase.entitytermsforlanguageview.js',
				'jquery/wikibase/jquery.wikibase.entitytermsforlanguagelistview.js',
				'jquery/wikibase/jquery.wikibase.entitytermsview.js',
				'jquery/wikibase/jquery.wikibase.statementgroupview.js',
				'jquery/wikibase/jquery.wikibase.statementlistview.js',
				'jquery/wikibase/jquery.wikibase.statementgrouplabelscroll.js',
				'jquery/wikibase/jquery.wikibase.statementgrouplistview.js',
				'jquery/ui/jquery.ui.tagadata.js',
				'jquery/jquery.sticknode.js',
			],
			'styles' => [
				'jquery/wikibase/themes/default/jquery.wikibase.badgeselector.css',
				'jquery/wikibase/themes/default/jquery.wikibase.sitelinkview.css',
				'jquery/wikibase/themes/default/jquery.wikibase.sitelinklistview.css',
				'jquery/wikibase/themes/default/jquery.wikibase.sitelinkgroupview.mw-collapsible.css',
				'jquery/wikibase/themes/default/jquery.wikibase.sitelinkgroupview.css',
				'jquery/wikibase/themes/default/jquery.wikibase.sitelinkgrouplistview.css',
				'jquery/wikibase/themes/default/jquery.wikibase.labelview.css',
				'jquery/wikibase/themes/default/jquery.wikibase.descriptionview.css',
				'jquery/ui/jquery.ui.tagadata.css',
				'jquery/wikibase/themes/default/jquery.wikibase.aliasesview.css',
				'jquery/wikibase/themes/default/jquery.wikibase.entitytermsforlanguageview.css',
				'jquery/wikibase/themes/default/jquery.wikibase.entitytermsforlanguagelistview.css',
				'jquery/wikibase/themes/default/jquery.wikibase.entitytermsview.css',
			],
			'dependencies' => [
				'jquery.ui.position',
				'jquery.ui.widget',
				'jquery.ui.core',
				'jquery.ui.EditableTemplatedWidget',
				'jquery.ui.menu',
				'jquery.ui.suggester',
				'jquery.ui.toggler',
				'jquery.util.getDirectionality',
				'jquery.event.special.eachchange',
				'jquery.inputautoexpand',
				'jquery.throttle-debounce',
				'jquery.wikibase.entityview',
				'jquery.wikibase.listview',
				'jquery.wikibase.siteselector',
				'jquery.wikibase.statementview',
				'wikibase.buildErrorOutput',
				'wikibase.getLanguageNameByCode',
				'wikibase.sites',
				'wikibase.templates',
				'wikibase.datamodel',
				'wikibase.utilities', // wikibase.utilities.ui
				'wikibase.utilities.ClaimGuidGenerator',
				'wikibase.view.__namespace',
				'wikibase',
				'jquery.valueview',
				'mediawiki.api',
				'mediawiki.cookie',
				'mediawiki.jqueryMsg', // for {{plural}} and {{gender}} support in messages
				'mediawiki.user',
				'mediawiki.util',
				'oojs-ui',
				'util.highlightSubstring',
			],
			'messages' => [
				'parentheses',
				'wikibase-badgeselector-badge-placeholder-title',
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
				'wikibase-remove',
				'wikibase-sitelink-site-edit-placeholder',
				'wikibase-sitelink-page-edit-placeholder',
				'wikibase-sitelinkgroupview-input-help-message',
				'wikibase-sitelinks-counter',
				'wikibase-statementgrouplistview-add-tooltip',
				'wikibase-statementlistview-add',
				'wikibase-statementlistview-add-tooltip',
			]
		],
	];

	return $modules;
} );
