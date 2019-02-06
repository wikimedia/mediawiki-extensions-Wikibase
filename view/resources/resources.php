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
		'jquery.util.EventSingletonManager' => $moduleTemplate + [
			'scripts' => [
				'jquery/jquery.util.EventSingletonManager.js',
			],
			'dependencies' => [
				'jquery.throttle-debounce',
			],
		],

		'jquery.ui.closeable' => $moduleTemplate + [
			'scripts' => [
				'jquery/ui/jquery.ui.closeable.js',
			],
			'styles' => [
				'jquery/ui/jquery.ui.closeable.css',
			],
			'dependencies' => [
				'jquery.ui.TemplatedWidget',
			],
		],

		'jquery.ui.EditableTemplatedWidget' => $moduleTemplate + [
			'scripts' => [
				'jquery/ui/jquery.ui.EditableTemplatedWidget.js',
			],
			'dependencies' => [
				'jquery.ui.closeable',
				'jquery.ui.TemplatedWidget',
				'util.inherit',
			],
		],

		'jquery.ui.TemplatedWidget' => $moduleTemplate + [
			'scripts' => [
				'jquery/ui/jquery.ui.TemplatedWidget.js',
			],
			'dependencies' => [
				'wikibase.templates',
				'jquery.ui.widget',
				'util.inherit',
			],
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
				'jquery/wikibase/jquery.wikibase.entityview.js',
			],
			'styles' => [
				'jquery/wikibase/themes/default/jquery.wikibase.entityview.css',
			],
			'dependencies' => [
				'jquery.ui.TemplatedWidget',
			],
		],

		'jquery.wikibase.entitytermsview' => $moduleTemplate + [
			'scripts' => [
				'jquery/wikibase/jquery.wikibase.entitytermsview.js',
			],
			'styles' => [
				'jquery/wikibase/themes/default/jquery.wikibase.entitytermsview.css',
			],
			'dependencies' => [
				'jquery.ui.closeable',
				'jquery.ui.EditableTemplatedWidget',
				'jquery.ui.toggler',
				'jquery.wikibase.entitytermsforlanguagelistview',
				'mediawiki.api',
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
				'jquery/wikibase/jquery.wikibase.entitytermsforlanguagelistview.js',
			],
			'styles' => [
				'jquery/wikibase/themes/default/jquery.wikibase.entitytermsforlanguagelistview.css',
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
				'jquery/wikibase/jquery.wikibase.descriptionview.js',
				'jquery/ui/jquery.ui.tagadata.js',
				'jquery/wikibase/jquery.wikibase.aliasesview.js',
				'jquery/wikibase/jquery.wikibase.entitytermsforlanguageview.js',
			],
			'styles' => [
				'jquery/wikibase/themes/default/jquery.wikibase.descriptionview.css',
				'jquery/ui/jquery.ui.tagadata.css',
				'jquery/wikibase/themes/default/jquery.wikibase.aliasesview.css',
				'jquery/wikibase/themes/default/jquery.wikibase.entitytermsforlanguageview.css',
			],
			'dependencies' => [
				'jquery.event.special.eachchange',
				'jquery.inputautoexpand',
				'jquery.ui.widget',
				'jquery.ui.core',
				'jquery.ui.EditableTemplatedWidget',
				'jquery.util.getDirectionality',
				'wikibase.datamodel.MultiTerm',
				'jquery.wikibase.labelview',
				'wikibase.datamodel.Term',
				'wikibase.getLanguageNameByCode',
				'wikibase.templates',
			],
			'messages' => [
				'wikibase-aliases-input-help-message',
				'wikibase-alias-edit-placeholder',
				'wikibase-description-edit-placeholder',
				'wikibase-description-edit-placeholder-language-aware',
				'wikibase-description-empty',
			],
			],
		],

		'jquery.wikibase.itemview' => $moduleTemplate + [
			'scripts' => [
				'jquery/wikibase/jquery.wikibase.itemview.js',
			],
			'dependencies' => [
				'jquery.wikibase.entityview',
				'jquery.wikibase.sitelinkgrouplistview',
			],
		],

		'jquery.wikibase.labelview' => $moduleTemplate + [
			'scripts' => [
				'jquery/wikibase/jquery.wikibase.labelview.js'
			],
			'styles' => [
				'jquery/wikibase/themes/default/jquery.wikibase.labelview.css',
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
				'jquery/wikibase/jquery.wikibase.listview.js',
				'jquery/wikibase/jquery.wikibase.listview.ListItemAdapter.js',
			],
			'dependencies' => [
				'jquery.ui.TemplatedWidget',
				'jquery.ui.widget',
			],
		],

		'jquery.wikibase.pagesuggester' => $moduleTemplate + [
			'scripts' => [
				'jquery/wikibase/jquery.wikibase.pagesuggester.js',
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
				'jquery/wikibase/jquery.wikibase.propertyview.js',
			],
			'dependencies' => [
				'jquery.wikibase.entityview',
				'wikibase.templates',
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
				'wikibase.datamodel',
			],
		],

		'jquery.wikibase.sitelinkgrouplistview' => $moduleTemplate + [
			'scripts' => [
				'jquery/wikibase/jquery.wikibase.sitelinkgrouplistview.js'
			],
			'styles' => [
				'jquery/wikibase/themes/default/jquery.wikibase.sitelinkgrouplistview.css',
			],
			'dependencies' => [
				'jquery.ui.TemplatedWidget',
				'jquery.wikibase.listview',
				'wikibase.sites',
			],
		],

		'jquery.wikibase.sitelinkgroupview' => $moduleTemplate + [
			'scripts' => [
				'jquery/jquery.sticknode.js',
				'jquery/wikibase/jquery.wikibase.sitelinkgroupview.js'
			],
			'styles' => [
				'jquery/wikibase/themes/default/jquery.wikibase.sitelinkgroupview.css',
			],
			'dependencies' => [
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
				'jquery/wikibase/themes/default/jquery.wikibase.sitelinkgroupview.mw-collapsible.css',
			],
		],

		'jquery.wikibase.sitelinklistview' => $moduleTemplate + [
			'scripts' => [
				'jquery/wikibase/jquery.wikibase.sitelinklistview.js',
			],
			'styles' => [
				'jquery/wikibase/themes/default/jquery.wikibase.sitelinklistview.css',
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
				'jquery/wikibase/jquery.wikibase.badgeselector.js',
				'jquery/wikibase/jquery.wikibase.sitelinkview.js',
			],
			'styles' => [
				'jquery/wikibase/themes/default/jquery.wikibase.badgeselector.css',
				'jquery/wikibase/themes/default/jquery.wikibase.sitelinkview.css',
			],
			'dependencies' => [
				'jquery.ui.EditableTemplatedWidget',
				'jquery.ui.menu',
				'jquery.util.EventSingletonManager',
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
				'jquery/wikibase/jquery.wikibase.snaklistview.js',
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
				'jquery/wikibase/jquery.wikibase.statementview.js',
				'jquery/wikibase/jquery.wikibase.statementview.RankSelector.js',
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
			'styles' => [
				'jquery/wikibase/themes/default/jquery.wikibase.statementview.RankSelector.css',
			],
			'targets' => [ 'desktop', 'mobile' ],
		],

		'jquery.wikibase.snakview' => $moduleTemplate + [
			'scripts' => [
				'jquery/wikibase/snakview/snakview.variations.js',
				'jquery/wikibase/snakview/snakview.variations.Variation.js',
				'jquery/wikibase/snakview/snakview.variations.NoValue.js',
				'jquery/wikibase/snakview/snakview.variations.SomeValue.js',
				'jquery/wikibase/snakview/snakview.variations.Value.js',
				'jquery/wikibase/snakview/snakview.ViewState.js',
				'jquery/wikibase/snakview/snakview.js',
				'jquery/wikibase/snakview/snakview.SnakTypeSelector.js',
			],
			'styles' => [
				'jquery/wikibase/snakview/themes/default/snakview.SnakTypeSelector.css',
			],
			'dependencies' => [
				'dataValues.DataValue',
				'jquery.event.special.eachchange',
				'jquery.ui.EditableTemplatedWidget',
				'jquery.ui.position',
				'jquery.wikibase.entityselector',
				'mediawiki.legacy.shared',
				'mw.config.values.wbRepo',
				'wikibase.datamodel',
				'wikibase.serialization.SnakDeserializer',
				'wikibase.serialization.SnakSerializer',
				'dataValues',
				'util.inherit',
			],
			'messages' => [
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
			],
		],

		'jquery.wikibase.addtoolbar' => $moduleTemplate + [
			'scripts' => [
				'jquery/wikibase/toolbar/jquery.wikibase.addtoolbar.js',
			],
			'dependencies' => [
				'jquery.wikibase.singlebuttontoolbar',
			],
			'messages' => [
				'wikibase-add',
			],
		],

		'jquery.wikibase.edittoolbar' => $moduleTemplate + [
			'scripts' => [
				'jquery/wikibase/toolbar/jquery.wikibase.edittoolbar.js',
			],
			'styles' => [
				'jquery/wikibase/toolbar/themes/default/jquery.wikibase.edittoolbar.css',
			],
			'dependencies' => [
				'jquery.wikibase.toolbar',
				'jquery.wikibase.toolbarbutton',
				'jquery.wikibase.wbtooltip',
				'wikibase.api.RepoApiError',
			],
			'messages' => [
				'wikibase-cancel',
				'wikibase-edit',
				'wikibase-remove',
				'wikibase-remove-inprogress',
				'wikibase-save',
				'wikibase-publish',
			],
		],

		'jquery.wikibase.removetoolbar' => $moduleTemplate + [
			'scripts' => [
				'jquery/wikibase/toolbar/jquery.wikibase.removetoolbar.js',
			],
			'dependencies' => [
				'jquery.wikibase.singlebuttontoolbar',
			],
			'messages' => [
				'wikibase-remove',
			],
		],

		'jquery.wikibase.singlebuttontoolbar' => $moduleTemplate + [
			'scripts' => [
				'jquery/wikibase/toolbar/jquery.wikibase.singlebuttontoolbar.js',
			],
			'dependencies' => [
				'jquery.wikibase.toolbar',
				'jquery.wikibase.toolbarbutton',
			],
		],

		'jquery.wikibase.toolbar' => $moduleTemplate + [
			'scripts' => [
				'jquery/wikibase/toolbar/jquery.wikibase.toolbar.js',
			],
			'dependencies' => [
				'jquery.wikibase.toolbaritem',
				'jquery.wikibase.toolbar.styles',
			],
		],

		'jquery.wikibase.toolbar.styles' => $moduleTemplate + [
			'styles' => [
				'jquery/wikibase/toolbar/themes/default/jquery.wikibase.toolbar.css',
			],
		],

		'jquery.wikibase.toolbarbutton' => $moduleTemplate + [
			'scripts' => [
				'jquery/wikibase/toolbar/jquery.wikibase.toolbarbutton.js',
			],
			'dependencies' => [
				'jquery.wikibase.toolbaritem',
				'jquery.wikibase.toolbarbutton.styles',
			],
		],

		'jquery.wikibase.toolbarbutton.styles' => $moduleTemplate + [
			'styles' => [
				'jquery/wikibase/toolbar/themes/default/jquery.wikibase.toolbarbutton.css',
			],
		],

		'jquery.wikibase.toolbaritem' => $moduleTemplate + [
			'scripts' => [
				'jquery/wikibase/toolbar/jquery.wikibase.toolbaritem.js',
			],
			'styles' => [
				'jquery/wikibase/toolbar/themes/default/jquery.wikibase.toolbaritem.css',
			],
			'dependencies' => [
				'jquery.ui.TemplatedWidget',
			],
		],

		// Common styles independent from JavaScript being enabled or disabled.
		//
		// FIXME: Registered for WikibaseClient, but only loaded by WikibaseRepo.
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

		// FIXME: Registered for WikibaseClient, but only loaded by WikibaseRepo.
		'wikibase.mobile' => $moduleTemplate + [
			'styles' => [
				'wikibase/wikibase.mobile.css'
			],
			'dependencies' => [
				'jquery.wikibase.statementview.RankSelector.styles',
			],
			'targets' => 'mobile'
		],

		// FIXME: Never loaded. Only used by wikibase.ui.entityViewInit (WikibaseRepo).
		'wikibase.RevisionStore' => $moduleTemplate + [
			'scripts' => [
				'wikibase/wikibase.RevisionStore.js',
			],
			'dependencies' => [
				'wikibase'
			]
		],

		'wikibase.templates' => $moduleTemplate + [
			'class' => TemplateModule::class,
			'scripts' => 'wikibase/templates.js',
			'dependencies' => [
				'jquery.getAttrs'
			]
		],

		// FIXME: Never loaded. Only used by wikibase.formatters.ApiValueFormatterFactory (WikibaseRepo).
		'wikibase.ValueFormatterFactory' => $moduleTemplate + [
			'scripts' => [
				'wikibase/wikibase.ValueFormatterFactory.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase',
			],
		],

		'wikibase.entityChangers.__namespace' => $moduleTemplate + [
			'scripts' => [
				'wikibase/entityChangers/namespace.js',
			],
			'dependencies' => [
				'wikibase',
			],
		],

		'wikibase.entityChangers.AliasesChanger' => $moduleTemplate + [
			'scripts' => [
				'wikibase/entityChangers/AliasesChanger.js',
			],
			'dependencies' => [
				'wikibase.datamodel.MultiTerm',
				'wikibase.entityChangers.__namespace',
				'wikibase.api.RepoApiError',
			],
		],

		'wikibase.entityChangers.StatementsChanger' => $moduleTemplate + [
			'scripts' => [
				'wikibase/entityChangers/StatementsChanger.js',
			],
			'dependencies' => [
				'wikibase.entityChangers.__namespace',
				'wikibase.api.RepoApiError',
			],
		],

		'wikibase.entityChangers.DescriptionsChanger' => $moduleTemplate + [
			'scripts' => [
				'wikibase/entityChangers/DescriptionsChanger.js',
			],
			'dependencies' => [
				'wikibase.entityChangers.__namespace',
				'wikibase.api.RepoApiError',
			]
		],

		'wikibase.entityChangers.EntityChangersFactory' => $moduleTemplate + [
			'scripts' => [
				'wikibase/entityChangers/EntityChangersFactory.js',
			],
			'dependencies' => [
				'wikibase.entityChangers.__namespace',
				'wikibase.entityChangers.AliasesChanger',
				'wikibase.entityChangers.DescriptionsChanger',
				'wikibase.entityChangers.EntityTermsChanger',
				'wikibase.entityChangers.LabelsChanger',
				'wikibase.entityChangers.SiteLinkSetsChanger',
				'wikibase.entityChangers.StatementsChanger',
				'wikibase.serialization.StatementDeserializer',
				'wikibase.serialization.StatementSerializer',
			]
		],

		'wikibase.entityChangers.EntityTermsChanger' => $moduleTemplate + [
			'scripts' => [
				'wikibase/entityChangers/EntityTermsChanger.js',
			],
			'dependencies' => [
				'wikibase.entityChangers.__namespace',
				'wikibase.api.RepoApiError',
			]
		],

		'wikibase.entityChangers.LabelsChanger' => $moduleTemplate + [
			'scripts' => [
				'wikibase/entityChangers/LabelsChanger.js',
			],
			'dependencies' => [
				'wikibase.entityChangers.__namespace',
				'wikibase.api.RepoApiError',
			]
		],

		'wikibase.entityChangers.SiteLinksChanger' => $moduleTemplate + [
			'scripts' => [
				'wikibase/entityChangers/SiteLinksChanger.js',
			],
			'dependencies' => [
				'wikibase.entityChangers.__namespace',
				'wikibase.api.RepoApiError',
			]
		],

		'wikibase.entityChangers.SiteLinkSetsChanger' => $moduleTemplate + [
			'scripts' => [
				'wikibase/entityChangers/SiteLinkSetsChanger.js',
			],
			'dependencies' => [
				'wikibase.entityChangers.__namespace',
				'wikibase.entityChangers.SiteLinksChanger',
				'wikibase.api.RepoApiError',
			]
		],

		'wikibase.entityIdFormatter.__namespace' => $moduleTemplate + [
			'scripts' => [
				'wikibase/entityIdFormatter/namespace.js'
			],
			'dependencies' => [
				'wikibase.view.__namespace',
			]
		],
		'wikibase.entityIdFormatter.CachingEntityIdHtmlFormatter' => $moduleTemplate + [
			'scripts' => [
				'wikibase/entityIdFormatter/CachingEntityIdHtmlFormatter.js'
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.entityIdFormatter.__namespace',
				'wikibase.entityIdFormatter.EntityIdHtmlFormatter',
			]
		],
		'wikibase.entityIdFormatter.CachingEntityIdPlainFormatter' => $moduleTemplate + [
			'scripts' => [
				'wikibase/entityIdFormatter/CachingEntityIdPlainFormatter.js'
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.entityIdFormatter.__namespace',
				'wikibase.entityIdFormatter.EntityIdPlainFormatter',
			]
		],
		'wikibase.entityIdFormatter.DataValueBasedEntityIdHtmlFormatter' => $moduleTemplate + [
			'scripts' => [
				'wikibase/entityIdFormatter/DataValueBasedEntityIdHtmlFormatter.js'
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.entityIdFormatter.__namespace',
				'wikibase.entityIdFormatter.EntityIdHtmlFormatter',
			]
		],
		'wikibase.entityIdFormatter.DataValueBasedEntityIdPlainFormatter' => $moduleTemplate + [
			'scripts' => [
				'wikibase/entityIdFormatter/DataValueBasedEntityIdPlainFormatter.js'
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.entityIdFormatter.__namespace',
				'wikibase.entityIdFormatter.EntityIdPlainFormatter',
			]
		],
		'wikibase.entityIdFormatter.EntityIdHtmlFormatter' => $moduleTemplate + [
			'scripts' => [
				'wikibase/entityIdFormatter/EntityIdHtmlFormatter.js'
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.entityIdFormatter.__namespace',
			]
		],
		'wikibase.entityIdFormatter.EntityIdPlainFormatter' => $moduleTemplate + [
			'scripts' => [
				'wikibase/entityIdFormatter/EntityIdPlainFormatter.js'
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.entityIdFormatter.__namespace',
			]
		],

		// FIXME: Never loaded. Only used by wikibase.ui.entityViewInit (WikibaseRepo).
		'wikibase.store.ApiEntityStore' => $moduleTemplate + [
			'scripts' => [
				'wikibase/store/store.ApiEntityStore.js',
			],
			'dependencies' => [
				'wikibase.store',
				'wikibase.store.EntityStore',
			],
		],

		'wikibase.store.CachingEntityStore' => $moduleTemplate + [
			'scripts' => [
				'wikibase/store/store.CachingEntityStore.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.store',
				'wikibase.store.EntityStore',
			],
		],

		'wikibase.store.CombiningEntityStore' => $moduleTemplate + [
			'scripts' => [
				'wikibase/store/store.CombiningEntityStore.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.store',
				'wikibase.store.EntityStore',
			],
		],

		'wikibase.store.EntityStore' => $moduleTemplate + [
			'scripts' => [
				'wikibase/store/store.EntityStore.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.store',
			],
		],

		'wikibase.store' => $moduleTemplate + [
			'scripts' => [
				'wikibase/store/store.js',
			],
			'dependencies' => [
				'wikibase',
			],
		],

		'wikibase.utilities.ClaimGuidGenerator' => $moduleTemplate + [
			'scripts' => [
				'wikibase/utilities/wikibase.utilities.ClaimGuidGenerator.js',
			],
			'dependencies' => [
				'wikibase.utilities.GuidGenerator',
			],
		],

		'wikibase.utilities.GuidGenerator' => $moduleTemplate + [
			'scripts' => [
				'wikibase/utilities/wikibase.utilities.GuidGenerator.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.utilities',
			],
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
		],

		'wikibase.view.__namespace' => $moduleTemplate + [
			'scripts' => [
				'wikibase/view/namespace.js'
			],
			'dependencies' => [
				'wikibase'
			]
		],

		'wikibase.view.ViewController' => $moduleTemplate + [
			'scripts' => 'wikibase/view/ViewController.js',
			'dependencies' => [
				'util.inherit',
				'wikibase.view.__namespace',
			]
		],

		'wikibase.view.StructureEditorFactory' => $moduleTemplate + [
			'scripts' => 'wikibase/view/StructureEditorFactory.js',
			'dependencies' => [
				'wikibase.view.__namespace',
			]
		],

		'wikibase.view.ToolbarFactory' => $moduleTemplate + [
			'scripts' => 'wikibase/view/ToolbarFactory.js',
			'dependencies' => [
				'jquery.wikibase.addtoolbar',
				'jquery.wikibase.edittoolbar',
				'jquery.wikibase.removetoolbar',
				'wikibase.view.__namespace',
			]
		],

		'wikibase.view.ToolbarViewController' => $moduleTemplate + [
			'scripts' => 'wikibase/view/ToolbarViewController.js',
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
			'scripts' => 'wikibase/view/ControllerViewFactory.js',
			'dependencies' => [
				'mediawiki.cookie',
				'mediawiki.user',
				'wikibase.view.__namespace',
				'wikibase.view.ToolbarViewController',
				'wikibase.view.ViewFactory'
			]
		],

		'wikibase.view.ReadModeViewFactory' => $moduleTemplate + [
			'scripts' => 'wikibase/view/ReadModeViewFactory.js',
			'dependencies' => [
				'wikibase.view.__namespace',
				'wikibase.view.ViewFactory'
			],
		],

		'wikibase.view.ViewFactoryFactory' => $moduleTemplate + [
			'scripts' => 'wikibase/view/ViewFactoryFactory.js',
			'dependencies' => [
				'wikibase.view.__namespace',
				'wikibase.view.ReadModeViewFactory',
				'wikibase.view.ControllerViewFactory'
			],
		],

		'wikibase.view.ViewFactory' => $moduleTemplate + [
			'scripts' => [
				'jquery/wikibase/jquery.wikibase.statementgroupview.js',
				'jquery/wikibase/jquery.wikibase.statementlistview.js',
				'jquery/wikibase/jquery.wikibase.statementgrouplabelscroll.js',
				'jquery/wikibase/jquery.wikibase.statementgrouplistview.js',
				'wikibase/wikibase.ValueViewBuilder.js',
				'wikibase/view/ViewFactory.js'
			],
			'dependencies' => [
				'jquery.ui.position',
				'jquery.ui.widget',
				'jquery.ui.TemplatedWidget',
				'jquery.util.EventSingletonManager',
				'jquery.wikibase.entitytermsview',
				'jquery.wikibase.itemview',
				'jquery.wikibase.listview', // For ListItemAdapter
				'jquery.wikibase.propertyview',
				'jquery.wikibase.sitelinkgroupview',
				'jquery.wikibase.sitelinklistview',
				'jquery.wikibase.statementview',
				'wikibase.datamodel.MultiTerm',
				'wikibase.datamodel.StatementGroup',
				'wikibase.datamodel.StatementGroupSet',
				'wikibase.datamodel.StatementList',
				'wikibase.datamodel.Term',
				'wikibase.utilities.ClaimGuidGenerator',
				'wikibase.view.__namespace',
				'wikibase',
				'jquery.valueview',
			],
			'messages' => [
				'wikibase-entitytermsview-input-help-message',
				'wikibase-aliases-separator',
				'wikibase-statementgrouplistview-add',
				'wikibase-statementgrouplistview-add-tooltip',
				'wikibase-statementlistview-add',
				'wikibase-statementlistview-add-tooltip',
			]
		],
	];

	return $modules;
} );
