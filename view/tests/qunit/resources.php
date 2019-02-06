<?php

/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */

return [
	'wikibase.view.tests' => [
		'localBasePath' => __DIR__,
		'remoteExtPath' => 'Wikibase/view/tests/qunit',
		'scripts' => [
			'getMockListItemAdapter.js',
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
			'jquery/wikibase/jquery.wikibase.sitelinkgrouplistview.tests.js',
			'jquery/wikibase/jquery.wikibase.sitelinkgroupview.tests.js',
			'jquery/wikibase/jquery.wikibase.sitelinklistview.tests.js',
			'jquery/wikibase/jquery.wikibase.sitelinkview.tests.js',
			'jquery/wikibase/jquery.wikibase.snaklistview.tests.js',
			'jquery/wikibase/jquery.wikibase.statementview.RankSelector.tests.js',
			'jquery/wikibase/jquery.wikibase.statementview.tests.js',
			'jquery/jquery.removeClassByRegex.tests.js',
			'jquery/jquery.sticknode.tests.js',
			'jquery/jquery.util.EventSingletonManager.tests.js',
			'jquery/jquery.util.getDirectionality.tests.js',
			'wikibase/entityChangers/AliasesChanger.tests.js',
			'wikibase/entityChangers/StatementsChanger.tests.js',
			'wikibase/entityChangers/DescriptionsChanger.tests.js',
			'wikibase/entityChangers/EntityTermsChanger.tests.js',
			'wikibase/entityChangers/LabelsChanger.tests.js',
			'wikibase/entityChangers/SiteLinksChanger.tests.js',
			'wikibase/entityChangers/SiteLinkSetsChanger.tests.js',
			'wikibase/entityIdFormatter/testEntityIdHtmlFormatter.js',
			'wikibase/entityIdFormatter/DataValueBasedEntityIdHtmlFormatter.tests.js',
			'wikibase/entityIdFormatter/DataValueBasedEntityIdPlainFormatter.tests.js',
			'wikibase/store/store.CachingEntityStore.tests.js',
			'wikibase/store/store.CombiningEntityStore.tests.js',
			'wikibase/utilities/ClaimGuidGenerator.tests.js',
			'wikibase/utilities/GuidGenerator.tests.js',
			'wikibase/view/testViewController.js',
			'wikibase/view/ToolbarViewController.tests.js',
			'wikibase/view/ViewFactory.tests.js',
			'wikibase/view/ViewFactoryFactory.tests.js',
			'wikibase/view/ToolbarFactory.tests.js',
			'wikibase/wikibase.WikibaseContentLanguages.tests.js',
			'wikibase/wikibase.getLanguageNameByCode.tests.js',
			'wikibase/templates.tests.js',
			'wikibase/wikibase.ValueViewBuilder.tests.js',
		],
		'dependencies' => [
			'dataValues.values',
			'jquery.ui.closeable',
			'jquery.ui.EditableTemplatedWidget',
			'jquery.ui.TemplatedWidget',
			'jquery.util.EventSingletonManager',
			'jquery.util.getDirectionality',
			'jquery.wikibase.addtoolbar',
			'jquery.wikibase.edittoolbar',
			'jquery.wikibase.entityselector',
			'jquery.wikibase.entityview',
			'jquery.wikibase.itemview',
			'jquery.wikibase.labelview',
			'jquery.wikibase.listview',
			'jquery.wikibase.pagesuggester',
			'jquery.wikibase.propertyview',
			'jquery.wikibase.referenceview',
			'jquery.wikibase.removetoolbar',
			'jquery.wikibase.singlebuttontoolbar',
			'jquery.wikibase.sitelinkgrouplistview',
			'jquery.wikibase.sitelinkgroupview',
			'jquery.wikibase.sitelinklistview',
			'jquery.wikibase.sitelinkview',
			'jquery.wikibase.snaklistview',
			'jquery.wikibase.snakview',
			'jquery.wikibase.statementview',
			'jquery.wikibase.toolbar',
			'jquery.wikibase.toolbarbutton',
			'jquery.wikibase.toolbaritem',
			'test.sinonjs',
			'wikibase.datamodel.Claim',
			'wikibase.datamodel.EntityId',
			'wikibase.datamodel.Fingerprint',
			'wikibase.datamodel.Item',
			'wikibase.datamodel.MultiTerm',
			'wikibase.datamodel.Property',
			'wikibase.datamodel.PropertyNoValueSnak',
			'wikibase.datamodel.PropertySomeValueSnak',
			'wikibase.datamodel.PropertyValueSnak',
			'wikibase.datamodel.Reference',
			'wikibase.datamodel.ReferenceList',
			'wikibase.datamodel.SiteLink',
			'wikibase.datamodel.SiteLinkSet',
			'wikibase.datamodel.SnakList',
			'wikibase.datamodel.Statement',
			'wikibase.datamodel.StatementGroup',
			'wikibase.datamodel.StatementGroupSet',
			'wikibase.datamodel.StatementList',
			'wikibase.datamodel.Term',
			'wikibase.datamodel',
			'wikibase.entityChangers.AliasesChanger',
			'wikibase.entityChangers.DescriptionsChanger',
			'wikibase.entityChangers.EntityTermsChanger',
			'wikibase.entityChangers.LabelsChanger',
			'wikibase.entityChangers.SiteLinksChanger',
			'wikibase.entityChangers.SiteLinkSetsChanger',
			'wikibase.entityChangers.StatementsChanger',
			'wikibase.entityIdFormatter.__namespace',
			'wikibase.entityIdFormatter.DataValueBasedEntityIdHtmlFormatter',
			'wikibase.entityIdFormatter.DataValueBasedEntityIdPlainFormatter',
			'wikibase.entityIdFormatter.EntityIdHtmlFormatter',
			'wikibase.entityIdFormatter.EntityIdPlainFormatter',
			'wikibase.experts.modules',
			'wikibase.getLanguageNameByCode',
			'wikibase.serialization.SnakDeserializer',
			'wikibase.serialization.SnakSerializer',
			'wikibase.serialization.StatementDeserializer',
			'wikibase.serialization.StatementSerializer',
			'wikibase.store.CachingEntityStore',
			'wikibase.store.CombiningEntityStore',
			'wikibase.store.EntityStore',
			'wikibase.templates',
			'wikibase.tests.qunit.testrunner',
			'wikibase.tests',
			'wikibase.utilities.ClaimGuidGenerator',
			'wikibase.utilities.GuidGenerator',
			'wikibase.view.ToolbarFactory',
			'wikibase.view.ToolbarViewController',
			'wikibase.view.ViewController',
			'wikibase.view.ViewFactory',
			'wikibase.view.ViewFactoryFactory',
			'wikibase.WikibaseContentLanguages',
		],
	],
];
