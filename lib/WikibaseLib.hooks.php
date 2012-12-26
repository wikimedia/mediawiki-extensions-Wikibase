<?php

namespace Wikibase;

/**
 * File defining the hook handlers for the WikibaseLib extension.
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
final class LibHooks {

	/**
	 * Adds default settings.
	 * Setting name (string) => setting value (mixed)
	 *
	 * @param array &$settings
	 *
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public static function onWikibaseDefaultSettings( array &$settings ) {
		$settings = array_merge(
			$settings,
			array(
				// alternative: application/vnd.php.serialized
				'serializationFormat' => CONTENT_FORMAT_JSON,

				'pollDefaultInterval' => 1,
				'pollDefaultLimit' => 100,
				'pollContinueInterval' => 0,

				'useChangesTable' => true, // whether changes get recorded to wb_changes

				'itemPrefix' => 'q',
				'propertyPrefix' => 'p',
				'queryPrefix' => 'y', // TODO: find a more suiting prefix, perhaps use 'q' and use 'i' for items then

				'siteLinkGroup' => 'wikipedia',

				'changesDatabase' => false, // local by default. Set to something LBFactory understands.

				'changeHandlers' => array(
					'wikibase-item~add' => 'Wikibase\ItemChange',
					'wikibase-property~add' => 'Wikibase\EntityChange',
					'wikibase-query~add' => 'Wikibase\EntityChange',

					'wikibase-item~update' => 'Wikibase\ItemChange',
					'wikibase-property~update' => 'Wikibase\EntityChange',
					'wikibase-query~update' => 'Wikibase\EntityChange',

					'wikibase-item~remove' => 'Wikibase\ItemChange',
					'wikibase-property~remove' => 'Wikibase\EntityChange',
					'wikibase-query~remove' => 'Wikibase\EntityChange',

					'wikibase-item~refresh' => 'Wikibase\ItemChange',
					'wikibase-property~refresh' => 'Wikibase\EntityChange',
					'wikibase-query~refresh' => 'Wikibase\EntityChange',

					'wikibase-item~restore' => 'Wikibase\ItemChange',
					'wikibase-property~restore' => 'Wikibase\EntityChange',
					'wikibase-query~restore' => 'Wikibase\EntityChange',
				),
				'dataTypes' => array(
					'wikibase-item',

					'commonsMedia',
					'geo-coordinate',
					'quantity',
					'monolingual-text',
					'multilingual-text',
				),
				'entityNamespaces' => array()
			)
		);

		return true;
	}

	/**
	 * Hook to add PHPUnit test cases.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UnitTestsList
	 *
	 * @since 0.2 (as registerUnitTests in 0.1)
	 *
	 * @param array $files
	 *
	 * @return boolean
	 */
	public static function registerPhpUnitTests( array &$files ) {
		// @codeCoverageIgnoreStart
		$testFiles = array(
			'changes/ChangeRow',
			'changes/DiffChange',
			'changes/EntityChange',

			'claim/ClaimAggregate',
			'claim/ClaimListAccess',
			'claim/ClaimObject',
			'claim/Claims',

			'entity/EntityFactory',
			'entity/EntityId',

			'hasharray/HashArrayWithoutDuplicates',
			'hasharray/HashArrayWithDuplicates',

			'item/ItemDiff',
			'item/ItemMultilangTexts',
			'item/ItemNewEmpty',
			'item/ItemNewFromArray',
			'item/Item',

			'property/PropertyDiff',
			'property/Property',

			'query/Query',

			'reference/ReferenceList',
			'reference/ReferenceObject',

			'serializers/ByPropertyListSerializer',
			'serializers/ClaimSerializer',
			'serializers/ClaimsSerializer',
			'serializers/ItemSerializer',
			'serializers/PropertySerializer',
			'serializers/ReferenceSerializer',
			'serializers/SerializationOptions',
			'serializers/Serializer',
			'serializers/SnakSerializer',

			'snak/PropertyValueSnak',
			'snak/SnakList',
			'snak/Snak',

			'statement/StatementObject',

			'store/SiteLinkLookup',
			'store/SiteLinkTable',
			'store/WikiPageEntityLookup',

			'ByPropertyIdArray',
			'ChangeNotifier',
			'ChangeHandler',
			'ChangesTable',
			'ReferencedEntitiesFinder',
			'HashableObjectStorage',
			'Template',
			'TemplateRegistry',
			'LibHooks',
			'MapValueHasher',
			'SettingsArray',
			'SiteLink',
			'Utils',
			'Term',
		);

		foreach ( $testFiles as $file ) {
			$files[] = __DIR__ . '/tests/phpunit/' . $file . 'Test.php';
		}

		return true;
		// @codeCoverageIgnoreEnd
	}

	/**
	 * Add new javascript testing modules. This is called after the addition of MediaWiki core test suites.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ResourceLoaderTestModules
	 *
	 * @since 0.2 (in repo as RepoHooks::onResourceLoaderTestModules in 0.1)
	 *
	 * @param array &$testModules
	 * @param \ResourceLoader &$resourceLoader
	 *
	 * @return boolean
	 */
	public static function registerQUnitTests( array &$testModules, \ResourceLoader &$resourceLoader ) {
		$testModules['qunit']['wikibase.tests'] = array(
			'scripts' => array(
				'tests/qunit/templates.tests.js',
				'tests/qunit/wikibase.tests.js',

				'tests/qunit/wikibase.datamodel/Wikibase.claim.tests.js',
				'tests/qunit/wikibase.datamodel/Wikibase.reference.tests.js',
				'tests/qunit/wikibase.datamodel/Wikibase.snak.tests.js',

				'tests/qunit/wikibase.Site.tests.js',

				'tests/qunit/wikibase.store/wikibase.Api.tests.js',

				'tests/qunit/wikibase.ui.AliasesEditTool.tests.js',
				'tests/qunit/wikibase.ui.DescriptionEditTool.tests.js',
				'tests/qunit/wikibase.ui.LabelEditTool.tests.js',
				'tests/qunit/wikibase.ui.SiteLinksEditTool.tests.js',
				'tests/qunit/wikibase.ui.PropertyEditTool.tests.js',
				'tests/qunit/wikibase.ui.PropertyEditTool.EditableAliases.tests.js',
				'tests/qunit/wikibase.ui.PropertyEditTool.EditableDescription.tests.js',
				'tests/qunit/wikibase.ui.PropertyEditTool.EditableLabel.tests.js',
				'tests/qunit/wikibase.ui.PropertyEditTool.EditableSiteLink.tests.js',
				'tests/qunit/wikibase.ui.PropertyEditTool.EditableValue.tests.js',
				'tests/qunit/wikibase.ui.PropertyEditTool.EditableValue.AutocompleteInterface.tests.js',
				'tests/qunit/wikibase.ui.PropertyEditTool.EditableValue.Interface.tests.js',
				'tests/qunit/wikibase.ui.PropertyEditTool.EditableValue.SiteIdInterface.tests.js',
				'tests/qunit/wikibase.ui.PropertyEditTool.EditableValue.SitePageInterface.tests.js',
				'tests/qunit/wikibase.ui.PropertyEditTool.EditableValue.ListInterface.tests.js',
				'tests/qunit/wikibase.ui.Toolbar.tests.js',
				'tests/qunit/wikibase.ui.Toolbar.EditGroup.tests.js',
				'tests/qunit/wikibase.ui.Toolbar.Group.tests.js',
				'tests/qunit/wikibase.ui.Toolbar.Label.tests.js',
				'tests/qunit/wikibase.ui.Toolbar.Button.tests.js',
				'tests/qunit/wikibase.ui.Tooltip.tests.js',

				'tests/qunit/wikibase.utilities/wikibase.utilities.inherit.tests.js',
				'tests/qunit/wikibase.utilities/wikibase.utilities.newExtension.tests.js',
				'tests/qunit/wikibase.utilities/wikibase.utilities.ObservableObject.tests.js',
				'tests/qunit/wikibase.utilities/wikibase.utilities.ui.StatableObject.tests.js',
				'tests/qunit/wikibase.utilities/wikibase.utilities.jQuery.tests.js',
				'tests/qunit/wikibase.utilities/wikibase.utilities.jQuery.PersistentPromisor.tests.js',
				'tests/qunit/wikibase.utilities/wikibase.utilities.jQuery.NativeEventHandler.tests.js',
				'tests/qunit/wikibase.utilities/wikibase.utilities.jQuery.ui.tests.js',
				'tests/qunit/wikibase.utilities/wikibase.utilities.jQuery.ui.tagadata.tests.js',

				'tests/qunit/jquery/jquery.eachchange.tests.js',
				'tests/qunit/jquery/jquery.inputAutoExpand.tests.js',
				'tests/qunit/jquery.ui/jquery.ui.suggester.tests.js',
				'tests/qunit/jquery.ui/jquery.ui.entityselector.tests.js',
				'tests/qunit/jquery.wikibase/jquery.wikibase.siteselector.tests.js',

			),
			'dependencies' => array(
				'wikibase.tests.qunit.testrunner',
				'wikibase',
				'wikibase.store',
				'wikibase.utilities',
				'wikibase.utilities.jQuery',
				'wikibase.ui.Toolbar',
				'wikibase.ui.PropertyEditTool',
				'jquery.ui.suggester',
				'jquery.ui.entityselector',
				'jquery.client'
			),
			'localBasePath' => __DIR__,
			'remoteExtPath' => 'Wikibase/lib',
		);

		return true;
	}
}
