<?php
/**
 * File for Wikibase resourceloader modules.
 * When included this returns an array with all the modules introduced by Wikibase.
 *
 * @since 0.2
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {

	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' =>  'Wikibase/lib/resources',
	);

	return array(
		// common styles independent from JavaScript being enabled or disabled
		'wikibase.common' => $moduleTemplate + array(
			'styles' => array(
				'wikibase.css',
				'wikibase.ui.Toolbar.css'
			)
		),

		'wikibase.sites' => $moduleTemplate + array(
			'class' => 'Wikibase\SitesModule',
		),

		'wikibase.repoAccess' => $moduleTemplate + array(
			'class' => 'Wikibase\RepoAccessModule',
		),

		'wikibase' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.js',
				'wikibase.Site.js',
				'wikibase.RevisionStore.js'
			),
			'dependencies' => array(
				'wikibase.common',
				'wikibase.sites',
				'wikibase.templates'
			),
			'messages' => array(
				'special-createitem',
				'wb-special-createitem-new-item-notification'
			)
		),

		'wikibase.parsers' => $moduleTemplate + array(
			'scripts' => array(
				'parsers/EntityIdParser.js',
			),
			'dependencies' => array(
				'valueParsers.ValueParser',
				'valueParsers.api',
				'wikibase.datamodel',
			),
		),

		'wikibase.datamodel' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.datamodel/datamodel.entities/wikibase.Entity.js',
				'wikibase.datamodel/datamodel.entities/wikibase.Item.js',
				'wikibase.datamodel/datamodel.entities/wikibase.Property.js',
				'wikibase.datamodel/wikibase.EntityId.js',
				'wikibase.datamodel/wikibase.Snak.js',
				'wikibase.datamodel/wikibase.SnakList.js',
				'wikibase.datamodel/wikibase.PropertyValueSnak.js',
				'wikibase.datamodel/wikibase.PropertySomeValueSnak.js',
				'wikibase.datamodel/wikibase.PropertyNoValueSnak.js',
				'wikibase.datamodel/wikibase.Reference.js',
				'wikibase.datamodel/wikibase.Claim.js',
				'wikibase.datamodel/wikibase.Statement.js',
			),
			'dependencies' => array(
				'wikibase',
				'wikibase.utilities',
				'dataValues.values', // DataValues extension
				'dataTypes', // DataTypes extension
			)
		),

		'wikibase.serialization' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.serialization/serialization.js',
				'wikibase.serialization/serialization.Serializer.js',
				'wikibase.serialization/serialization.Unserializer.js',
				'wikibase.serialization/serialization.SerializerFactory.js',
			),
			'dependencies' => array(
				'wikibase',
				'wikibase.utilities',
			)
		),

		'wikibase.serialization.entities' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.serialization/serialization.EntityUnserializer.js',
				'wikibase.serialization/serialization.EntityUnserializer.propertyExpert.js',
			),
			'dependencies' => array(
				'wikibase.serialization',
				'wikibase.datamodel',
			)
		),

		'wikibase.serialization.fetchedcontent' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.serialization/serialization.FetchedContentUnserializer.js',
			),
			'dependencies' => array(
				'wikibase.serialization',
				'wikibase.store.FetchedContent',
			)
		),

		'wikibase.store' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.store/store.js',
				'wikibase.store/store.FetchedContent.js',
				'wikibase.store/wikibase.EntityStore.js', // TODO: get rid of this
				'wikibase.store/wikibase.RepoApi.js', // TODO: move
			),
			'dependencies' => array(
				'jquery.json',
				'user.tokens',
				'wikibase.datamodel',
				'wikibase.serialization.entities',
				'wikibase.repoAccess',
				'wikibase.RepoApiError',
				'mediawiki.Title',
			)
		),

		'wikibase.store.FetchedContent' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.store/store.FetchedContent.js',
			),
			'dependencies' => array(
				'wikibase.store',
				'mediawiki.Title',
			)
		),

		'wikibase.RepoApiError' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.store/wikibase.RepoApiError.js',
			),
			'messages' => array(
				'wikibase-error-unexpected',
				'wikibase-error-save-generic',
				'wikibase-error-remove-generic',
				'wikibase-error-save-timeout',
				'wikibase-error-remove-timeout',
				'wikibase-error-ui-client-error',
				'wikibase-error-ui-no-external-page',
				'wikibase-error-ui-cant-edit',
				'wikibase-error-ui-no-permissions',
				'wikibase-error-ui-link-exists',
				'wikibase-error-ui-session-failure',
				'wikibase-error-ui-edit-conflict',
				'wikibase-error-ui-edit-conflict',
			),
			'dependencies' => array(
				'wikibase',
				'wikibase.utilities',
			)
		),

		'wikibase.utilities' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.utilities/wikibase.utilities.js',
				'wikibase.utilities/wikibase.utilities.ObservableObject.js',
				'wikibase.utilities/wikibase.utilities.ui.js',
				'wikibase.utilities/wikibase.utilities.ui.StatableObject.js',
			),
			'styles' => array(
				'wikibase.utilities/wikibase.utilities.ui.css',
			),
			'dependencies' => array(
				'wikibase',
				'jquery.tipsy',
				'mediawiki.language',
			),
			'messages' => array(
				'wikibase-ui-pendingquantitycounter-nonpending',
				'wikibase-ui-pendingquantitycounter-pending',
				'wikibase-ui-pendingquantitycounter-pending-pendingsubpart'
			)
		),

		'wikibase.utilities.jQuery' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.utilities/wikibase.utilities.js',
				'wikibase.utilities/wikibase.utilities.jQuery.js',
			),
			'dependencies' => array(
				'wikibase.utilities'
			)
		),

		'wikibase.utilities.jQuery.ui.tagadata' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.utilities/wikibase.utilities.jQuery.ui.tagadata/wikibase.utilities.jQuery.ui.tagadata.js',
			),
			'styles' => array(
				'wikibase.utilities/wikibase.utilities.jQuery.ui.tagadata/wikibase.utilities.jQuery.ui.tagadata.css',
			),
			'dependencies' => array(
				'jquery.eachchange',
				'jquery.effects.blind',
				'jquery.inputAutoExpand',
				'jquery.ui.widget'
			)
		),

		'wikibase.tests.qunit.testrunner' => $moduleTemplate + array(
			'scripts' => '../tests/qunit/data/testrunner.js',
			'dependencies' => array(
				'mediawiki.tests.qunit.testrunner',
				'wikibase'
			),
			'position' => 'top'
		),

		'wikibase.ui.Base' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.ui.js',
				'wikibase.ui.Base.js',
			),
			'dependencies' => array(
				'wikibase',
				'wikibase.utilities',
			),
		),

		'wikibase.ui.Tooltip' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.ui.Tooltip.js',
				'wikibase.ui.Tooltip.Extension.js',
			),
			'dependencies' => array(
				'jquery.nativeEventHandler',
				'jquery.tipsy',
				'wikibase',
				'wikibase.RepoApiError',
				'wikibase.ui.Base',
				'wikibase.utilities',
			),
			'messages' => array(
				'wikibase-tooltip-error-details'
			)
		),

		// should be independent from the rest of Wikibase (or only use other stuff that could go into core)
		'wikibase.ui.Toolbar' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.ui.Toolbar.js',
				'wikibase.ui.Toolbar.Group.js',
				'wikibase.ui.Toolbar.Label.js',
				'wikibase.ui.Toolbar.Button.js'
			),
			'dependencies' => array(
				'jquery.nativeEventHandler',
				'jquery.ui.core',
				'mediawiki.legacy.shared',
				'wikibase',
				'wikibase.common',
				'wikibase.ui.Base',
				'wikibase.ui.Tooltip',
				'wikibase.utilities',
				'wikibase.utilities.jQuery'
			),
		),

		'wikibase.ui.PropertyEditTool' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.ui.Toolbar.EditGroup.js', // related to EditableValue, see todo in file
				'wikibase.ui.PropertyEditTool.js',
				'wikibase.ui.PropertyEditTool.EditableValue.js',
				'wikibase.ui.PropertyEditTool.EditableValue.Interface.js',
				'wikibase.ui.PropertyEditTool.EditableValue.AutocompleteInterface.js',
				'wikibase.ui.PropertyEditTool.EditableValue.SitePageInterface.js',
				'wikibase.ui.PropertyEditTool.EditableValue.SiteIdInterface.js',
				'wikibase.ui.PropertyEditTool.EditableValue.ListInterface.js',
				'wikibase.ui.PropertyEditTool.EditableValue.AliasesInterface.js',
				'wikibase.ui.PropertyEditTool.EditableDescription.js',
				'wikibase.ui.PropertyEditTool.EditableLabel.js',
				'wikibase.ui.PropertyEditTool.EditableSiteLink.js',
				'wikibase.ui.PropertyEditTool.EditableAliases.js',
				'wikibase.ui.LabelEditTool.js',
				'wikibase.ui.DescriptionEditTool.js',
				'wikibase.ui.SiteLinksEditTool.js',
				'wikibase.ui.AliasesEditTool.js',
			),
			'styles' => array(
				'wikibase.ui.PropertyEditTool.css'
			),
			'dependencies' => array(
				'jquery.eachchange',
				'jquery.nativeEventHandler',
				'jquery.inputAutoExpand',
				'jquery.tablesorter',
				'jquery.ui.suggester',
				'jquery.wikibase.entityselector',
				'jquery.wikibase.siteselector',
				'mediawiki.api',
				'mediawiki.language',
				'mediawiki.Title',
				'mediawiki.jqueryMsg', // for {{plural}} and {{gender}} support in messages
				'wikibase',
				'wikibase.ui.Base',
				'wikibase.ui.Toolbar',
				'wikibase.utilities',
				'wikibase.utilities.jQuery',
				'wikibase.utilities.jQuery.ui.tagadata',
				'wikibase.store',
			),
			'messages' => array(
				'wikibase-cancel',
				'wikibase-edit',
				'wikibase-save',
				'wikibase-add',
				'wikibase-save-inprogress',
				'wikibase-remove-inprogress',
				'wikibase-label-edit-placeholder',
				'wikibase-description-edit-placeholder',
				'wikibase-aliases-label',
				'wikibase-aliases-input-help-message',
				'wikibase-alias-edit-placeholder',
				'wikibase-sitelink-site-edit-placeholder',
				'wikibase-sitelink-page-edit-placeholder',
				'wikibase-label-input-help-message',
				'wikibase-description-input-help-message',
				'wikibase-sitelinks-input-help-message',
				'wikibase-sitelinks-sitename-columnheading',
				'wikibase-sitelinks-siteid-columnheading',
				'wikibase-sitelinks-link-columnheading',
				'wikibase-remove',
				'wikibase-propertyedittool-full',
				'wikibase-propertyedittool-counter-pending-tooltip',
				'wikibase-propertyedittool-counter-entrieslabel',
				'wikibase-sitelinksedittool-full',
				'wikibase-error-save-generic',
				'wikibase-error-remove-generic',
				'wikibase-error-save-connection',
				'wikibase-error-remove-connection',
				'wikibase-error-save-timeout',
				'wikibase-error-remove-timeout',
				'wikibase-error-autocomplete-connection',
				'wikibase-error-autocomplete-response',
				'wikibase-error-ui-client-error',
				'wikibase-error-ui-no-external-page',
				'wikibase-error-ui-cant-edit',
				'wikibase-error-ui-no-permissions',
				'wikibase-error-ui-link-exists',
				'wikibase-error-ui-session-failure',
				'wikibase-error-ui-edit-conflict',
				'wikibase-restrictionedit-tooltip-message',
				'wikibase-blockeduser-tooltip-message',
				'parentheses',
			)
		),

		'jquery.wikibase.toolbarcontroller' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase/jquery.wikibase.toolbarcontroller/toolbarcontroller.js',
				'jquery.wikibase/jquery.wikibase.toolbarcontroller/toolbarcontroller.definitions.js',
			),
			'dependencies' => array(
				'jquery.wikibase.addtoolbar',
				'jquery.wikibase.edittoolbar',
			)
		),

		'jquery.wikibase.addtoolbar' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase/jquery.wikibase.addtoolbar.js',
			),
			'dependencies' => array(
				'jquery.ui.widget',
				'wikibase.ui.Toolbar',
			),
			'messages' => array(
				'wikibase-add'
			)
		),

		'jquery.wikibase.edittoolbar' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase/jquery.wikibase.edittoolbar.js',
			),
			'dependencies' => array(
				'wikibase.ui.PropertyEditTool', // needs wikibase.ui.Toolbar.EditGroup
			)
		),

		'wikibase.templates' => $moduleTemplate + array(
			'class' => 'Wikibase\TemplateModule',
			'scripts' => 'templates.js'
		),

		'jquery.ui.TemplatedWidget' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.ui/jquery.ui.TemplatedWidget.js'
			),
			'dependencies' => array(
				'wikibase.templates',
				'jquery.ui.widget'
			)
		),

		'jquery.nativeEventHandler' => $moduleTemplate + array(
			'scripts' => array(
				'jquery/jquery.nativeEventHandler.js'
			)
		),

		'jquery.ui.suggester' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.ui/jquery.ui.suggester.js'
			),
			'styles' => array(
				'jquery.ui/themes/default/jquery.ui.suggester.css'
			),
			'dependencies' => array(
				'jquery.ui.autocomplete'
			)
		),

		'jquery.wikibase.siteselector' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase/jquery.wikibase.siteselector.js'
			),
			'dependencies' => array(
				'jquery.ui.suggester',
				'wikibase'
			)
		),

		'jquery.wikibase.listview' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase/jquery.wikibase.listview.js',
				'jquery.wikibase/jquery.wikibase.listview.ListItemAdapter.js'
			),
			'dependencies' => array(
				'jquery.ui.TemplatedWidget',
				'jquery.wikibase.toolbarcontroller',
			)
		),

		'jquery.wikibase.snaklistview' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase/jquery.wikibase.snaklistview.js',
			),
			'dependencies' => array(
				'jquery.wikibase.snakview',
				'wikibase.utilities',
			),
			'messages' => array(
				'wikibase-claimview-snak-tooltip',
				'wikibase-claimview-snak-new-tooltip',
			)
		),

		'jquery.wikibase.snakview' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase/jquery.wikibase.snakview/snakview.js',
				'jquery.wikibase/jquery.wikibase.snakview/snakview.SnakTypeSelector.js',
				'jquery.wikibase/jquery.wikibase.snakview/snakview.ViewState.js',
				'jquery.wikibase/jquery.wikibase.snakview/snakview.variations.js',
				'jquery.wikibase/jquery.wikibase.snakview/snakview.variations.Variation.js',
				'jquery.wikibase/jquery.wikibase.snakview/snakview.variations.Value.js',
				'jquery.wikibase/jquery.wikibase.snakview/snakview.variations.SomeValue.js',
				'jquery.wikibase/jquery.wikibase.snakview/snakview.variations.NoValue.js',
			),
			'dependencies' => array(
				'jquery.eachchange',
				'jquery.nativeEventHandler',
				'jquery.wikibase.entityselector',
				'wikibase.datamodel',
				'dataTypes.jquery.valueview.views',
				'wikibase.jquery.valueview.views',
				'wikibase.store', // required for getting datatype from entityselector selected property
				'mediawiki.legacy.shared',
				'jquery.ui.TemplatedWidget'
			),
			'messages' => array(
				'wikibase-snakview-property-input-placeholder',
				'wikibase-snakview-unsupportedsnaktype',
				'wikibase-snakview-choosesnaktype',
				'wikibase-snakview-variation-unsupporteddatatype',
				'wikibase-snakview-variation-unsupporteddatavalue',
				'wikibase-snakview-variation-datavaluetypemismatch',
				'wikibase-snakview-variation-datavaluetypemismatch-details',
				'wikibase-snakview-variation-nonewvaluefordeletedproperty',
				'datatypes-type-wikibase-item',
				'wikibase-snakview-variations-somevalue-label',
				'wikibase-snakview-variations-novalue-label',
				'wikibase-snakview-snaktypeselector-value',
				'wikibase-snakview-snaktypeselector-somevalue',
				'wikibase-snakview-snaktypeselector-novalue'
			)
		),

		'jquery.wikibase.claimview' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase/jquery.wikibase.claimview.js'
			),
			'dependencies' => array(
				'jquery.wikibase.snakview'
			),
			'messages' => array(
				'wikibase-claimview-snak-tooltip',
				'wikibase-claimview-snak-new-tooltip'
			)
		),

		'jquery.wikibase.referenceview' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase/jquery.wikibase.referenceview.js',
			),
			'dependencies' => array(
				'jquery.wikibase.snaklistview',
				'jquery.wikibase.toolbarcontroller',
			)
		),

		'jquery.wikibase.statementview' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase/jquery.wikibase.statementview.js',
			),
			'dependencies' => array(
				'jquery.wikibase.claimview',
				'jquery.wikibase.listview',
				'jquery.wikibase.referenceview',
				'jquery.wikibase.toolbarcontroller',
				'wikibase.utilities',
			),
			'messages' => array(
				'wikibase-statementview-referencesheading-pendingcountersubject',
				'wikibase-statementview-referencesheading-pendingcountertooltip',
				'wikibase-addreference'
			)
		),

		'jquery.wikibase.claimlistview' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase/jquery.wikibase.claimlistview.js'
			),
			'dependencies' => array(
				'jquery.wikibase.claimview',
				'jquery.wikibase.toolbarcontroller',
				'wikibase.templates',
			),
			'messages' => array(
				'wikibase-label-empty',
				'parentheses',
				'wikibase-deletedentity',
				'wikibase-entity-property',
			)
		),

		'jquery.wikibase.entityview' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase/jquery.wikibase.entityview.js'
			),
			'dependencies' => array(
				'jquery.wikibase.statementview',
				'jquery.wikibase.claimlistview',
				'jquery.wikibase.toolbarcontroller',
				'wikibase.templates'
			)
		),

		'jquery.wikibase.entityselector' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase/jquery.wikibase.entityselector.js'
			),
			'styles' => array(
				'jquery.wikibase/themes/default/jquery.wikibase.entityselector.css'
			),
			'dependencies' => array(
				'jquery.ui.suggester',
				'jquery.ui.resizable',
				'jquery.eachchange'
			),
			'messages' => array(
				'wikibase-aliases-label',
				'wikibase-entityselector-more'
			)
		),

		// jQuery.valueview views for Wikibase specific DataValues/DataTypes
		'wikibase.jquery.valueview.views' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.valueview.views/wikibaseItem.js',
			),
			'styles' => array(
				'jquery.valueview.views/views.css',
			),
			'dependencies' => array(
				'dataTypes.jquery.valueview',
				'wikibase.parsers',
				'jquery.eachchange',
			),
			'messages' => array(
				'parentheses',
				'wikibase-entity-item',
			)
		),

	);
} );
// @codeCoverageIgnoreEnd
