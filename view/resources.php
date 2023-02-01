<?php

use Wikibase\Repo\WikibaseRepo;
use Wikibase\View\Module\TemplateModule;
use Wikibase\View\Termbox\TermboxModule;

/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
return call_user_func( function() {
	$moduleTemplate = [
		'localBasePath' => __DIR__ . '/resources',
		'remoteExtPath' => 'Wikibase/view/resources',
	];

	$moduleBaseTemplate = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => 'Wikibase/view',
	];

	$wikibaseDatavaluesSrcPaths = [
		'localBasePath' => __DIR__ . '/lib/wikibase-data-values/src',
		'remoteExtPath' => 'Wikibase/view/lib/wikibase-data-values/src',
	];
	$wikibaseDatavaluesPaths = [
		'localBasePath' => __DIR__ . '/lib/wikibase-data-values',
		'remoteExtPath' => 'Wikibase/view/lib/wikibase-data-values',
	];

	$wikibaseDatavaluesValueviewLibPaths = [
		'localBasePath' => __DIR__ . '/lib/wikibase-data-values-value-view/lib',
		'remoteExtPath' => 'Wikibase/view/lib/wikibase-data-values-value-view/lib',
	];
	$wikibaseDatavaluesValueviewSrcPaths = [
		'localBasePath' => __DIR__ . '/lib/wikibase-data-values-value-view/src',
		'remoteExtPath' => 'Wikibase/view/lib/wikibase-data-values-value-view/src',
	];
	$wikibaseDatavaluesValueviewPaths = [
		'localBasePath' => __DIR__ . '/lib/wikibase-data-values-value-view',
		'remoteExtPath' => 'Wikibase/view/lib/wikibase-data-values-value-view',
	];

	$wikibaseTermboxPaths = [
		'localBasePath' => __DIR__ . '/lib/wikibase-termbox',
		'remoteExtPath' => 'Wikibase/view/lib/wikibase-termbox',
	];

	$wikibaseApiPaths = [
		'localBasePath' => __DIR__ . '/../lib/resources/wikibase-api/src',
		'remoteExtPath' => 'Wikibase/lib/resources/wikibase-api/src',
	];

	$modules = [
		'wikibase' => $moduleTemplate + [
			'scripts' => [
				'wikibase.js',
			],
			'targets' => [ 'desktop', 'mobile' ],
		],

		'jquery.wikibase.entityselector' => $moduleTemplate + [
			'targets' => [ 'desktop' ],
			'scripts' => [
				'jquery/wikibase/jquery.wikibase.entityselector.js',
			],
			'styles' => [
				'jquery/wikibase/themes/default/jquery.wikibase.entityselector.css',
			],
			'dependencies' => [
				'jquery.event.special.eachchange',
				'jquery.ui.suggester',
				'jquery.ui',
			],
			'messages' => [
				'wikibase-entityselector-more',
				'wikibase-entityselector-notfound',
			],
		],

		'jquery.wikibase.toolbar.styles' => $moduleTemplate + [
			'targets' => [ 'desktop' ],
			'styles' => [
				'jquery/wikibase/toolbar/themes/default/jquery.wikibase.toolbar.css',
				'jquery/wikibase/toolbar/themes/default/jquery.wikibase.toolbarbutton.css',
			],
		],

		// Common styles independent from JavaScript being enabled or disabled.

		// all targets (desktop+mobile)
		'wikibase.alltargets' => $moduleTemplate + [
			'targets' => [ 'desktop', 'mobile' ],
			'styles' => [
				'wikibase/wikibase.badgedisplay.less',
				'wikibase/wikibase.itemlink.less',
			],
		],

		// desktop-only (though some of these should be on mobile too, see T326428)
		'wikibase.desktop' => $moduleTemplate + [
			'targets' => [ 'desktop' ],
			'styles' => [
				'wikibase/wikibase.less',
				'jquery/wikibase/themes/default/jquery.wikibase.aliasesview.css',
				'jquery/wikibase/themes/default/jquery.wikibase.descriptionview.css',
				'jquery/wikibase/themes/default/jquery.wikibase.entityview.less',
				'jquery/wikibase/themes/default/jquery.wikibase.entitytermsview.css',
				'jquery/wikibase/themes/default/jquery.wikibase.entitytermsforlanguagelistview.css',
				'jquery/wikibase/themes/default/jquery.wikibase.entitytermsforlanguageview.css',
				'jquery/wikibase/themes/default/jquery.wikibase.labelview.css',
				'jquery/wikibase/themes/default/jquery.wikibase.sitelinkgrouplistview.css',
				'jquery/wikibase/themes/default/jquery.wikibase.sitelinkgroupview.css',
				'jquery/wikibase/themes/default/jquery.wikibase.sitelinklistview.css',
				'jquery/wikibase/themes/default/jquery.wikibase.sitelinkview.css',
				'jquery/wikibase/themes/default/jquery.wikibase.statementgroupview.css',
			],
		],

		// mobile-only
		'wikibase.mobile' => $moduleTemplate + [
			'styles' => [
				'wikibase/wikibase.mobile.css',
				'jquery/wikibase/themes/default/jquery.wikibase.statementview.RankSelector.css',
			],
			'targets' => 'mobile',
		],

		// deprecated: this is effectively wikibase.alltargets + wikibase.desktop, use those instead
		'wikibase.common' => $moduleTemplate + [
			'targets' => [ 'desktop' ],
			'styles' => [
				// Order must be hierarchical, do not order alphabetically
				'wikibase/wikibase.less',
				'wikibase/wikibase.itemlink.less',
				'jquery/wikibase/themes/default/jquery.wikibase.aliasesview.css',
				'jquery/wikibase/themes/default/jquery.wikibase.descriptionview.css',
				'jquery/wikibase/themes/default/jquery.wikibase.entityview.less',
				'jquery/wikibase/themes/default/jquery.wikibase.entitytermsview.css',
				'jquery/wikibase/themes/default/jquery.wikibase.entitytermsforlanguagelistview.css',
				'jquery/wikibase/themes/default/jquery.wikibase.entitytermsforlanguageview.css',
				'jquery/wikibase/themes/default/jquery.wikibase.labelview.css',
				'jquery/wikibase/themes/default/jquery.wikibase.sitelinkgrouplistview.css',
				'jquery/wikibase/themes/default/jquery.wikibase.sitelinkgroupview.css',
				'jquery/wikibase/themes/default/jquery.wikibase.sitelinklistview.css',
				'wikibase/wikibase.badgedisplay.less',
				'jquery/wikibase/themes/default/jquery.wikibase.sitelinkview.css',
				'jquery/wikibase/themes/default/jquery.wikibase.statementgroupview.css',
			],
		],

		// end of common styles independent from JavaScript being enabled or disabled

		'wikibase.templates' => $moduleTemplate + [
			'class' => TemplateModule::class,
			'scripts' => 'wikibase/templates.js',
			'targets' => [ 'desktop', 'mobile' ],
		],

		'wikibase.entityChangers.EntityChangersFactory' => $moduleTemplate + [
			 // T326405
			'targets' => [ 'desktop' ],
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
				'wikibase.api.RepoApi',
				'wikibase.datamodel', // for AliasesChanger.js
				'wikibase.serialization',
			],
		],

		'wikibase.utilities.ClaimGuidGenerator' => $moduleTemplate + [
			'packageFiles' => [
				'wikibase/utilities/wikibase.utilities.ClaimGuidGenerator.js',

				'wikibase/utilities/wikibase.utilities.GuidGenerator.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase',
			],
			'targets' => [ 'desktop', 'mobile' ],
		],

		'wikibase.view.__namespace' => $moduleTemplate + [
			 // T326405
			'targets' => [ 'desktop' ],
			'scripts' => [
				'wikibase/view/namespace.js',
			],
			'dependencies' => [
				'wikibase',
			],
		],

		'wikibase.view.ReadModeViewFactory' => $moduleTemplate + [
			 // T326405
			'targets' => [ 'desktop' ],
			'scripts' => 'wikibase/view/ReadModeViewFactory.js',
			'dependencies' => [
				'wikibase.view.__namespace',
				'wikibase.view.ControllerViewFactory',
			],
		],
		'wikibase.view.ControllerViewFactory' => $moduleBaseTemplate + [
			 // T326405
			'targets' => [ 'desktop' ],
			'packageFiles' => [
				'resources/wikibase/view/ControllerViewFactory.js',

				'resources/jquery/ui/jquery.ui.TemplatedWidget.js',
				'resources/jquery/ui/jquery.ui.closeable.js',
				'resources/jquery/ui/jquery.ui.EditableTemplatedWidget.js',
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
				'resources/jquery/wikibase/jquery.wikibase.statementgrouplistview.js',
				'resources/jquery/wikibase/jquery.wikibase.statementgroupview.js',
				'resources/jquery/wikibase/jquery.wikibase.statementlistview.js',
				'resources/jquery/wikibase/jquery.wikibase.statementview.js',
				'resources/jquery/wikibase/jquery.wikibase.statementview.RankSelector.js',
				'resources/jquery/wikibase/jquery.wikibase.siteselector.js',
				'resources/jquery/ui/jquery.ui.tagadata.js',
				'resources/jquery/jquery.removeClassByRegex.js',
				'lib/wikibase-data-values-value-view/lib/jquery.ui/jquery.ui.toggler.js',
				'resources/wikibase/utilities/wikibase.utilities.ui.js',

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
				'resources/jquery/wikibase/themes/default/jquery.wikibase.entityview.less',
				'resources/wikibase/utilities/wikibase.utilities.ui.css',
				'resources/jquery/ui/jquery.ui.closeable.css',
			],
			'dependencies' => [
				'dataValues.DataValue', // For snakview
				'jquery.animateWithEvent',
				'jquery.event.special.eachchange',
				'jquery.spinner', //For snakview
				'jquery.ui',
				'jquery.ui.suggester',
				'jquery.util.getDirectionality',
				'jquery.event.special.eachchange',
				'jquery.inputautoexpand',
				'jquery.wikibase.entityselector',
				'wikibase.buildErrorOutput',
				'wikibase.getLanguageNameByCode',
				'wikibase.sites',
				'wikibase.templates',
				'wikibase.datamodel',
				'wikibase.serialization',
				'wikibase.utilities.ClaimGuidGenerator',
				'wikibase.view.__namespace',
				'wikibase',
				'jquery.valueview',
				'mediawiki.api',
				'mediawiki.cookie',
				'mediawiki.jqueryMsg', // for {{plural}} and {{gender}} support in messages
				'mediawiki.language',
				'mediawiki.user',
				'mediawiki.util',
				'mw.config.values.wbRefTabsEnabled',
				'mw.config.values.wbRepo',
				'oojs-ui',
				'util.highlightSubstring',
				'util.inherit',
				// the tainted-ref dependency is temporarily moved to ControllerViewFactory.js (T298001),
				// because tainted-ref is (transitively) es6-only and we don’t want this module to be es6-only yet;
				// this can probably be reverted at some point, once we fully drop Wikibase IE11 support
				// 'wikibase.tainted-ref',
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
		'wikibase.datamodel' => [
			'packageFiles' => [
				'index.js',

				'List.js',
				'Group.js',
				'Map.js',
				'Set.js',
				'GroupableCollection.js',
				'FingerprintableEntity.js',
				'Claim.js',
				'Entity.js',
				'EntityId.js',
				'Fingerprint.js',
				'Item.js',
				'MultiTerm.js',
				'MultiTermMap.js',
				'Property.js',
				'PropertyNoValueSnak.js',
				'PropertySomeValueSnak.js',
				'PropertyValueSnak.js',
				'Reference.js',
				'ReferenceList.js',
				'SiteLink.js',
				'SiteLinkSet.js',
				'Snak.js',
				'SnakList.js',
				'Statement.js',
				'StatementGroup.js',
				'StatementGroupSet.js',
				'StatementList.js',
				'Term.js',
				'TermMap.js',
			],
			'dependencies' => [
				'util.inherit',
				'dataValues.DataValue',
			],
			'targets' => [ 'desktop', 'mobile' ],
			'localBasePath' => __DIR__ . '/lib/wikibase-data-model/src',
			'remoteExtPath' => 'Wikibase/view/lib/wikibase-data-model/src',
		],

		'dataValues' => $wikibaseDatavaluesSrcPaths + [
			'scripts' => [
				'dataValues.js',
			],
			'targets' => [ 'desktop', 'mobile' ],
		],

		'jquery.animateWithEvent' => $wikibaseDatavaluesValueviewLibPaths + [
			 // T326405
			'targets' => [ 'desktop' ],
			'packageFiles' => [
				'jquery/jquery.animateWithEvent.js',
				'jquery/jquery.AnimationEvent.js',
				'jquery/jquery.PurposedCallbacks.js',
			],
		],

		'jquery.inputautoexpand' => $wikibaseDatavaluesValueviewLibPaths + [
			 // T326405
			'targets' => [ 'desktop' ],
			'scripts' => [
				'jquery/jquery.inputautoexpand.js',
			],
			'styles' => [
				'jquery/jquery.inputautoexpand.css',
			],
			'dependencies' => [
				'jquery.event.special.eachchange',
			],
		],

		'jquery.ui.commonssuggester' => $wikibaseDatavaluesValueviewLibPaths + [
			 // T326405
			'targets' => [ 'desktop' ],
			'scripts' => [
				'jquery.ui/jquery.ui.commonssuggester.js',
			],
			'styles' => [
				'jquery.ui/jquery.ui.commonssuggester.css',
			],
			'dependencies' => [
				'jquery.ui.suggester',
				'jquery.ui',
				'util.highlightSubstring',
			],
		],

		'jquery.ui.languagesuggester' => $wikibaseDatavaluesValueviewLibPaths + [
			 // T326405
			'targets' => [ 'desktop' ],
			'scripts' => [
				'jquery.ui/jquery.ui.languagesuggester.js',
			],
			'dependencies' => [
				'jquery.ui.suggester',
				'jquery.ui',
			],
		],

		'util.ContentLanguages' => $wikibaseDatavaluesValueviewLibPaths + [
			'scripts' => [
				'util/util.ContentLanguages.js',
			],
			'dependencies' => [
				'util.inherit',
			],
			'targets' => [ 'desktop', 'mobile' ],
		],

		'util.Extendable' => $wikibaseDatavaluesValueviewLibPaths + [
			 // T326405
			'targets' => [ 'desktop' ],
			'scripts' => [
				'util/util.Extendable.js',
			],
		],

		'util.MessageProvider' => $wikibaseDatavaluesValueviewLibPaths + [
			 // T326405
			'targets' => [ 'desktop' ],
			'scripts' => [
				'util/util.MessageProvider.js',
			],
		],

		'util.MessageProviders' => $wikibaseDatavaluesValueviewLibPaths + [
			 // T326405
			'targets' => [ 'desktop' ],
			'scripts' => [
				'util/util.HashMessageProvider.js',
				'util/util.CombiningMessageProvider.js',
				'util/util.PrefixingMessageProvider.js',
			],
		],

		'util.Notifier' => $wikibaseDatavaluesValueviewLibPaths + [
			 // T326405
			'targets' => [ 'desktop' ],
			'scripts' => [
				'util/util.Notifier.js',
			],
		],

		'util.highlightSubstring' => $wikibaseDatavaluesValueviewLibPaths + [
			 // T326405
			'targets' => [ 'desktop' ],
			'scripts' => [
				'util/util.highlightSubstring.js',
			],
		],

		'jquery.ui.suggester' => $wikibaseDatavaluesValueviewLibPaths + [
			 // T326405
			'targets' => [ 'desktop' ],
			'scripts' => [
				'jquery.ui/jquery.ui.suggester.js',
				'jquery.ui/jquery.ui.ooMenu.js',
				'jquery.util/jquery.util.getscrollbarwidth.js',
			],
			'styles' => [
				'jquery.ui/jquery.ui.suggester.css',
				'jquery.ui/jquery.ui.ooMenu.css',
			],
			'dependencies' => [
				'jquery.ui',
				'util.inherit',
			],
		],

		'jquery.event.special.eachchange' => $wikibaseDatavaluesValueviewLibPaths + [
			 // T326405
			'targets' => [ 'desktop' ],
			'scripts' => [
				'jquery.event/jquery.event.special.eachchange.js',
			],
		],

		'dataValues.DataValue' => $wikibaseDatavaluesSrcPaths + [
			'scripts' => [
				'DataValue.js',
			],
			'dependencies' => [
				'dataValues',
				'util.inherit',
			],
			'targets' => [ 'desktop', 'mobile' ],
		],

		'dataValues.values' => $wikibaseDatavaluesPaths + [
			'scripts' => [
				// Note: The order here is relevant, scripts should be places after the ones they
				//  depend on.
				'lib/globeCoordinate/globeCoordinate.js',
				'lib/globeCoordinate/globeCoordinate.GlobeCoordinate.js',
				'src/values/BoolValue.js',
				'src/values/DecimalValue.js',
				'src/values/GlobeCoordinateValue.js',
				'src/values/MonolingualTextValue.js',
				'src/values/MultilingualTextValue.js',
				'src/values/StringValue.js',
				'src/values/NumberValue.js',
				'src/values/TimeValue.js',
				'src/values/QuantityValue.js',
				'src/values/UnknownValue.js',
				'src/values/UnDeserializableValue.js',
			],
			'dependencies' => [
				'dataValues.DataValue',
				'dataValues.TimeValue',
				'util.inherit',
			],
			'targets' => [ 'desktop', 'mobile' ],
		],

		'dataValues.TimeValue' => $wikibaseDatavaluesSrcPaths + [
			'scripts' => [
				'values/TimeValue.js',
			],
			'dependencies' => [
				'dataValues.DataValue',
				'util.inherit',
			],
			'targets' => [ 'desktop', 'mobile' ],
		],

		'valueFormatters' => $wikibaseDatavaluesSrcPaths + [
			 // T326405
			'targets' => [ 'desktop' ],
			'scripts' => [
				'valueFormatters/valueFormatters.js',
				'valueFormatters/formatters/ValueFormatter.js',
			],
			'dependencies' => [
				'util.inherit',
			],
		],

		'valueParsers' => $wikibaseDatavaluesSrcPaths + [
			 // T326405
			'targets' => [ 'desktop' ],
			'scripts' => [
				'valueParsers/valueParsers.js',
			],
		],

		'valueParsers.ValueParserStore' => $wikibaseDatavaluesSrcPaths + [
			 // T326405
			'targets' => [ 'desktop' ],
			'scripts' => [
				'valueParsers/ValueParserStore.js',
			],
			'dependencies' => [
				'valueParsers',
			],
		],

		'valueParsers.parsers' => $wikibaseDatavaluesSrcPaths + [
			 // T326405
			'targets' => [ 'desktop' ],
			'scripts' => [
				'valueParsers/parsers/ValueParser.js',
				'valueParsers/parsers/NullParser.js',
				// we do not use any of the actual parsers (StringParser etc.);
				// instead, we use the PHP parsers via the wbparsevalue API
				// (wired up in repo/resources/parsers/getStore.js)
			],
			'dependencies' => [
				'dataValues.values',
				'util.inherit',
				'valueParsers',
			],
		],

		'wikibase.serialization' => [
			'packageFiles' => [
				'index.js',

				'Deserializers/Deserializer.js',
				'Deserializers/SnakDeserializer.js',
				'Deserializers/StatementGroupSetDeserializer.js',
				'Deserializers/StatementGroupDeserializer.js',
				'Deserializers/StatementListDeserializer.js',
				'Deserializers/StatementDeserializer.js',
				'Deserializers/ClaimDeserializer.js',
				'Deserializers/TermDeserializer.js',
				'Serializers/ClaimSerializer.js',
				'Deserializers/ReferenceListDeserializer.js',
				'Deserializers/ReferenceDeserializer.js',
				'Deserializers/SnakListDeserializer.js',
				'Serializers/TermMapSerializer.js',
				'Serializers/TermSerializer.js',
				'Deserializers/TermMapDeserializer.js',
				'Deserializers/EntityDeserializer.js',
				'StrategyProvider.js',
				'Deserializers/ItemDeserializer.js',
				'Deserializers/PropertyDeserializer.js',
				'Deserializers/SiteLinkSetDeserializer.js',
				'Deserializers/SiteLinkDeserializer.js',
				'Deserializers/FingerprintDeserializer.js',
				'Deserializers/MultiTermMapDeserializer.js',
				'Deserializers/MultiTermDeserializer.js',
				'Serializers/StatementSerializer.js',
				'Serializers/StatementListSerializer.js',
				'Serializers/ReferenceListSerializer.js',
				'Serializers/ReferenceSerializer.js',
				'Serializers/Serializer.js',
				'Serializers/SnakListSerializer.js',
				'Serializers/SnakSerializer.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel',
				'dataValues',
				'dataValues.values',
			],
			'targets' => [ 'desktop', 'mobile' ],
			'localBasePath' => __DIR__ . '/lib/wikibase-serialization/src',
			'remoteExtPath' => 'Wikibase/view/lib/wikibase-serialization/src',
		],

		'util.inherit' => $wikibaseDatavaluesPaths + [
			'scripts' => [
				'lib/util/util.inherit.js',
			],
			'targets' => [ 'desktop', 'mobile' ],
		],

		// Loads the actual valueview widget into jQuery.valueview.valueview and maps
		// jQuery.valueview to jQuery.valueview.valueview without losing any properties.
		'jquery.valueview' => $wikibaseDatavaluesValueviewSrcPaths + [
			 // T326405
			'targets' => [ 'desktop' ],
			'packageFiles' => [
				'jquery.valueview.js',
				'jquery.valueview.valueview.js',
				'jquery.valueview.ViewState.js',
			],
			'styles' => [
				'jquery.valueview.valueview.css',
			],
			'dependencies' => [
				'dataValues.DataValue',
				'jquery.ui',
				'jquery.valueview.experts.EmptyValue',
				'jquery.valueview.ExpertStore',
				'util.Notifier',
				'valueFormatters',
				'valueParsers.ValueParserStore',
			],
		],

		'jquery.valueview.Expert' => $wikibaseDatavaluesValueviewSrcPaths + [
			 // T326405
			'targets' => [ 'desktop' ],
			'scripts' => [
				'jquery.valueview.experts.js',
				'jquery.valueview.Expert.js',
			],
			'dependencies' => [
				'util.inherit',
				'util.MessageProviders',
				'util.Notifier',
				'util.Extendable',
			],
		],

		'jquery.valueview.experts.CommonsMediaType' => $wikibaseDatavaluesValueviewSrcPaths + [
			 // T326405
			'targets' => [ 'desktop' ],
			'scripts' => [
				'experts/CommonsMediaType.js',
			],
			'dependencies' => [
				'jquery.event.special.eachchange',
				'jquery.ui.commonssuggester',
				'jquery.valueview.experts.StringValue',
				'jquery.valueview.Expert',
			],
		],

		'jquery.valueview.experts.GeoShape' => $wikibaseDatavaluesValueviewSrcPaths + [
			 // T326405
			'targets' => [ 'desktop' ],
			'scripts' => [
				'experts/GeoShape.js',
			],
			'dependencies' => [
				'jquery.event.special.eachchange',
				'jquery.ui.commonssuggester',
				'jquery.valueview.experts.StringValue',
				'jquery.valueview.Expert',
			],
		],

		'jquery.valueview.experts.TabularData' => $wikibaseDatavaluesValueviewSrcPaths + [
			 // T326405
			'targets' => [ 'desktop' ],
			'scripts' => [
				'experts/TabularData.js',
			],
			'dependencies' => [
				'jquery.event.special.eachchange',
				'jquery.ui.commonssuggester',
				'jquery.valueview.experts.StringValue',
				'jquery.valueview.Expert',
			],
		],

		'jquery.valueview.experts.EmptyValue' => $wikibaseDatavaluesValueviewSrcPaths + [
			 // T326405
			'targets' => [ 'desktop' ],
			'scripts' => [
				'experts/EmptyValue.js',
			],
			'styles' => [
				'experts/EmptyValue.css',
			],
			'dependencies' => [
				'jquery.valueview.Expert',
			],
			'messages' => [
				'valueview-expert-emptyvalue-empty',
			],
		],

		'jquery.valueview.experts.GlobeCoordinateInput' => $wikibaseDatavaluesValueviewSrcPaths + [
			 // T326405
			'targets' => [ 'desktop' ],
			'scripts' => [
				'experts/GlobeCoordinateInput.js',
			],
			'styles' => [
				'experts/GlobeCoordinateInput.css',
			],
			'dependencies' => [
				'jquery.valueview.ExpertExtender',
				'jquery.valueview.experts.StringValue',
				'jquery.valueview.Expert',
				'util.MessageProvider',
			],
			'messages' => [
				'valueview-expert-globecoordinateinput-precision',
				'valueview-expert-globecoordinateinput-nullprecision',
				'valueview-expert-globecoordinateinput-customprecision',
				'valueview-expert-globecoordinateinput-precisionlabel-arcminute',
				'valueview-expert-globecoordinateinput-precisionlabel-arcsecond',
				'valueview-expert-globecoordinateinput-precisionlabel-tenth-of-arcsecond',
				'valueview-expert-globecoordinateinput-precisionlabel-hundredth-of-arcsecond',
				'valueview-expert-globecoordinateinput-precisionlabel-thousandth-of-arcsecond',
				'valueview-expert-globecoordinateinput-precisionlabel-tenthousandth-of-arcsecond',
			],
		],

		'jquery.valueview.experts.MonolingualText' => $wikibaseDatavaluesValueviewSrcPaths + [
			 // T326405
			'targets' => [ 'desktop' ],
			'packageFiles' => [
				'experts/MonolingualText.js',
				'ExpertExtender/ExpertExtender.LanguageSelector.js',
			],
			'dependencies' => [
				'jquery.valueview.Expert',
				'jquery.valueview.ExpertExtender',
				'jquery.valueview.experts.StringValue',
				'jquery.event.special.eachchange',
				'jquery.ui.languagesuggester',
				'util.MessageProviders',
			],
			'messages' => [
				'valueview-expertextender-languageselector-languagetemplate',
				'valueview-expertextender-languageselector-label',
			],
		],

		'jquery.valueview.experts.QuantityInput' => $wikibaseDatavaluesValueviewPaths + [
			 // T326405
			'targets' => [ 'desktop' ],
			'packageFiles' => [
				'src/experts/QuantityInput.js',
				'src/ExpertExtender/ExpertExtender.UnitSelector.js',
				'lib/jquery.ui/jquery.ui.unitsuggester.js',
			],
			'styles' => [
				'lib/jquery.ui/jquery.ui.unitsuggester.css',
			],
			'dependencies' => [
				'jquery.ui.suggester',
				'jquery.ui',
				'jquery.valueview.Expert',
				'jquery.valueview.ExpertExtender',
				'jquery.valueview.experts.StringValue',
			],
			'messages' => [
				'valueview-expertextender-unitsuggester-label',
			],
		],

		'jquery.valueview.experts.StringValue' => $wikibaseDatavaluesValueviewSrcPaths + [
			 // T326405
			'targets' => [ 'desktop' ],
			'scripts' => [
				'experts/StringValue.js',
				'../lib/jquery/jquery.focusAt.js',
			],
			'dependencies' => [
				'jquery.event.special.eachchange',
				'jquery.inputautoexpand',
				'jquery.valueview.Expert',
			],
		],

		'jquery.valueview.experts.TimeInput' => $wikibaseDatavaluesValueviewSrcPaths + [
			 // T326405
			'targets' => [ 'desktop' ],
			'scripts' => [
				'experts/TimeInput.js',
			],
			'styles' => [
				'experts/TimeInput.css',
			],
			'dependencies' => [
				'dataValues.TimeValue',
				'jquery.valueview.ExpertExtender',
				'jquery.valueview.Expert',
				'util.MessageProvider',
			],
			'messages' => [
				'valueview-expert-timeinput-calendar',
				'valueview-expert-timeinput-precision',
				'valueview-expert-timeinput-precision-year1g',
				'valueview-expert-timeinput-precision-year100m',
				'valueview-expert-timeinput-precision-year10m',
				'valueview-expert-timeinput-precision-year1m',
				'valueview-expert-timeinput-precision-year100k',
				'valueview-expert-timeinput-precision-year10k',
				'valueview-expert-timeinput-precision-year1k',
				'valueview-expert-timeinput-precision-year100',
				'valueview-expert-timeinput-precision-year10',
				'valueview-expert-timeinput-precision-year',
				'valueview-expert-timeinput-precision-month',
				'valueview-expert-timeinput-precision-day',
				'valueview-expert-timeinput-precision-hour',
				'valueview-expert-timeinput-precision-minute',
				'valueview-expert-timeinput-precision-second',
				'valueview-expert-timevalue-calendar-gregorian',
				'valueview-expert-timevalue-calendar-julian',
			],
		],

		'jquery.valueview.experts.UnDeserializableValue' => $wikibaseDatavaluesValueviewSrcPaths + [
			 // T326405
			'targets' => [ 'desktop' ],
			'scripts' => [
				'experts/UnDeserializableValue.js',
			],
			'dependencies' => [
				'jquery.valueview.Expert',
			],
		],

		'jquery.valueview.ExpertStore' => $wikibaseDatavaluesValueviewSrcPaths + [
			 // T326405
			'targets' => [ 'desktop' ],
			'scripts' => [
				'experts/UnsupportedValue.js',
				'jquery.valueview.ExpertStore.js',
			],
			'styles' => [
				'experts/UnsupportedValue.css',
			],
			'dependencies' => [
				'jquery.valueview.Expert',
			],
			'messages' => [
				'valueview-expert-unsupportedvalue-unsupporteddatatype',
				'valueview-expert-unsupportedvalue-unsupporteddatavalue',
			],
		],

		'jquery.valueview.ExpertExtender' => $wikibaseDatavaluesValueviewSrcPaths + [
			 // T326405
			'targets' => [ 'desktop' ],
			'scripts' => [
				'ExpertExtender/ExpertExtender.js',
				'../lib/jquery.ui/jquery.ui.inputextender.js',
				'ExpertExtender/ExpertExtender.Container.js',
				'ExpertExtender/ExpertExtender.Listrotator.js',
				'../lib/jquery.ui/jquery.ui.listrotator.js',
				'ExpertExtender/ExpertExtender.Preview.js',
				'../lib/jquery.ui/jquery.ui.preview.js',
			],
			'styles' => [
				'../lib/jquery.ui/jquery.ui.inputextender.css',
				'../lib/jquery.ui/jquery.ui.listrotator.css',
				'ExpertExtender/ExpertExtender.Preview.css',
				'../lib/jquery.ui/jquery.ui.preview.css',
			],
			'dependencies' => [
				'jquery.animateWithEvent',
				'jquery.event.special.eachchange',
				'jquery.valueview',
				'util.Extendable',
				'jquery.ui',
				'util.MessageProviders',
			],
			'messages' => [
				'valueview-listrotator-manually',
				'valueview-preview-label',
				'valueview-preview-novalue',
			],
		],

		'wikibase.termbox' => $wikibaseTermboxPaths + [
			'class' => TermboxModule::class,
			'packageFiles' => [
				'dist/wikibase.termbox.main.js',
				[
					'name' => 'dist/config.json',
					'callback' => function () {
						return [
							'tags' => WikibaseRepo::getSettings()->getSetting( 'termboxTags' ),
						];
					},
				],
			],
			'es6' => true,
			'targets' => [ 'mobile', 'desktop' ],
			'dependencies' => [
				'wikibase.termbox.styles',
				'wikibase.getLanguageNameByCode',
				'wikibase.WikibaseContentLanguages',
				'wikibase.getUserLanguages',
				'mw.config.values.wbRepo',
				'vue',
				'vuex',
			],
			// 'messages' are declared by ./resources.json via TermboxModule.
		],

		'wikibase.termbox.styles' => $wikibaseTermboxPaths + [
			'styles' => [
				'dist/wikibase.termbox.main.css',
			],
			'skinStyles' => [
				'minerva' => '../../resources/wikibase/termbox/minerva.less',
			],
			'targets' => [
				'mobile', 'desktop',
			],
		],

		'wikibase.tainted-ref' => [
			 // T326405
			'targets' => [ 'desktop' ],
			'scripts' => [
				'tainted-ref.init.js',
				'tainted-ref.common.js',
			],
			'styles' => [
				'tainted-ref.app.css',
			],
			'dependencies' => [
				'vue',
			],
			'messages' => [
				'wikibase-tainted-ref-popper-text',
				'wikibase-tainted-ref-popper-title',
				'wikibase-tainted-ref-popper-help-link-title',
				'wikibase-tainted-ref-popper-help-link-text',
				'wikibase-tainted-ref-popper-remove-warning',
				'wikibase-tainted-ref-tainted-icon-title',
			],
			'remoteExtPath' => 'Wikibase/view/lib/wikibase-tainted-ref/dist',
			'localBasePath' => __DIR__ . '/lib/wikibase-tainted-ref/dist',
			'es6' => true,
		],
		'jquery.wikibase.wbtooltip' => $moduleTemplate + [
			 // T326405
			'targets' => [ 'desktop' ],
			'scripts' => [
				'jquery/wikibase/jquery.wikibase.wbtooltip.js',
			],
			'styles' => [
				'jquery/wikibase/themes/default/jquery.wikibase.wbtooltip.css',
			],
			'dependencies' => [
				'jquery.tipsy',
				'jquery.ui',
				'wikibase.buildErrorOutput',
			],
		],
		'wikibase.buildErrorOutput' => $moduleTemplate + [
			 // T326405
			'targets' => [ 'desktop' ],
			'scripts' => [
				'wikibase/wikibase.buildErrorOutput.js',
			],
			'dependencies' => [
				'wikibase',
			],
		],

		'wikibase.api.RepoApi' => $wikibaseApiPaths + [
			'scripts' => [
				'namespace.js',
				'RepoApi.js',
				'getLocationAgnosticMwApi.js',
				'RepoApiError.js',
			],
			'dependencies' => [
				'mediawiki.api',
				'mediawiki.user',
				'mediawiki.ForeignApi',
			],
			'messages' => [
				'wikibase-error-unexpected',
				'wikibase-error-unknown',
				'wikibase-error-save-generic',
				'wikibase-error-remove-generic',
				'wikibase-error-save-timeout',
				'wikibase-error-remove-timeout',
				'wikibase-error-ui-no-external-page',
				'wikibase-error-ui-edit-conflict',
			],
			'targets' => [
				'desktop',
				'mobile',
			],
		],

		'wikibase.api.ValueCaller' => $wikibaseApiPaths + [
			 // T326405
			'targets' => [ 'desktop' ],
			'scripts' => [
				'namespace.js',
				'ParseValueCaller.js',
				'FormatValueCaller.js',
			],
			'dependencies' => [
				'wikibase.api.RepoApi',
				'dataValues.DataValue',
			],
		],
	];

	return $modules;
} );
