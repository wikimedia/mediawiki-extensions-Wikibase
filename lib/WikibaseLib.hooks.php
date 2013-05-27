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
	 * Hook to add PHPUnit test cases.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UnitTestsList
	 *
	 * @since 0.2
	 *
	 * @param string[] $files
	 *
	 * @return boolean
	 */
	public static function registerPhpUnitTests( array &$files ) {
		// @codeCoverageIgnoreStart
		$testFiles = array(
			'changes/ChangeRow',
			'changes/DiffChange',
			'changes/EntityChange',

			'claim/ClaimDifference',
			'claim/ClaimDifferenceVisualizer',
			'claim/ClaimDiffer',

			'entity/EntityFactory',

			'formatters/EntityIdFormatter',
			'formatters/EntityIdLabelFormatter',

			'parsers/EntityIdParser',

			'serializers/ByPropertyListSerializer',
			'serializers/ByPropertyListUnserializer',
			'serializers/ClaimSerializer',
			'serializers/ClaimsSerializer',
			'serializers/ItemSerializer',
			'serializers/PropertySerializer',
			'serializers/ReferenceSerializer',
			'serializers/SerializationOptions',
			'serializers/SerializerFactory',
			'serializers/Serializer',
			'serializers/SnakSerializer',

			'store/ChunkCache',
			'store/SiteLinkLookup',
			'store/SiteLinkTable',
			'store/WikiPageEntityLookup',
			'store/CachingEntityLoader',
			'store/EntityUsageIndex',

			'store/TermPropertyLabelResolver',

			'util/HttpAcceptParser',
			'util/HttpAcceptNegotiator',

			'ChangesTable',
			'ClaimDifference',
			'ClaimGuidValidator',
			'ReferencedEntitiesFinder',
			'EntityRetrievingDataTypeLookup',
			'InMemoryDataTypeLookup',
			'Template',
			'TemplateRegistry',
			'LibHooks',
			'MockRepository',
			'PropertyNotFoundException',
			'SettingsArray',
			'SnakFormatter',

			'TermsToClaimsTranslator',
			'TypedValueFormatter',
			'Term',
			'Utils',
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

				'tests/qunit/parsers/EntityIdParser.tests.js',

				'tests/qunit/wikibase.datamodel/Wikibase.claim.tests.js',
				'tests/qunit/wikibase.datamodel/Wikibase.reference.tests.js',
				'tests/qunit/wikibase.datamodel/Wikibase.snak.tests.js',
				'tests/qunit/wikibase.datamodel/Wikibase.SnakList.tests.js',
				'tests/qunit/wikibase.datamodel/wikibase.Statement.tests.js',
				'tests/qunit/wikibase.datamodel/datamodel.Entity.tests.js',
				'tests/qunit/wikibase.datamodel/datamodel.Item.tests.js',
				'tests/qunit/wikibase.datamodel/datamodel.Property.tests.js',

				'tests/qunit/wikibase.Site.tests.js',

				'tests/qunit/wikibase.RepoApi/wikibase.RepoApi.tests.js',
				'tests/qunit/wikibase.RepoApi/wikibase.RepoApiError.tests.js',

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

				'tests/qunit/wikibase.utilities/wikibase.utilities.ClaimGuidGenerator.tests.js',
				'tests/qunit/wikibase.utilities/wikibase.utilities.GuidGenerator.tests.js',
				'tests/qunit/wikibase.utilities/wikibase.utilities.newExtension.tests.js',
				'tests/qunit/wikibase.utilities/wikibase.utilities.ObservableObject.tests.js',
				'tests/qunit/wikibase.utilities/wikibase.utilities.ui.StatableObject.tests.js',
				'tests/qunit/wikibase.utilities/wikibase.utilities.jQuery.tests.js',
				'tests/qunit/wikibase.utilities/wikibase.utilities.jQuery.PersistentPromisor.tests.js',
				'tests/qunit/wikibase.utilities/wikibase.utilities.jQuery.NativeEventHandler.tests.js',
				'tests/qunit/wikibase.utilities/wikibase.utilities.jQuery.NativeEventHandler.testsOnObject.js',
				'tests/qunit/wikibase.utilities/wikibase.utilities.jQuery.NativeEventHandler.testsOnWidget.js',
				'tests/qunit/wikibase.utilities/wikibase.utilities.jQuery.ui.tagadata.tests.js',

				'tests/qunit/jquery.wikibase/jquery.wikibase.entityselector.tests.js',
				'tests/qunit/jquery.wikibase/jquery.wikibase.siteselector.tests.js',

			),
			'dependencies' => array(
				'wikibase.tests.qunit.testrunner',
				'wikibase',
				'wikibase.AbstractedRepoApi',
				'wikibase.parsers',
				'wikibase.store',
				'wikibase.utilities',
				'wikibase.utilities.ClaimGuidGenerator',
				'wikibase.utilities.GuidGenerator',
				'wikibase.utilities.jQuery',
				'wikibase.ui.Toolbar',
				'wikibase.ui.PropertyEditTool',
				'jquery.ui.suggester',
				'jquery.wikibase.entityselector',
				'jquery.nativeEventHandler',
				'jquery.client',
				'jquery.eachchange',
			),
			'localBasePath' => __DIR__,
			'remoteExtPath' => 'Wikibase/lib',
		);

		return true;
	}
}
