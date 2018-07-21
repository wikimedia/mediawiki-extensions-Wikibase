<?php

// Temporary script to be used as long as MediaWiki extension classes
// cannot be loaded with PSR-4-compliant autoloading.

namespace Wikibase\Build;

use AutoloadGenerator;
use Maintenance;

require_once getenv( 'MW_INSTALL_PATH' ) !== false
	? getenv( 'MW_INSTALL_PATH' ) . '/maintenance/Maintenance.php'
	: __DIR__ . '/../../../maintenance/Maintenance.php';

/**
 * Generates Wikibase autoload info
 */
class GenerateWikibaseAutoload extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Generates Wikibase autoload data';
	}

	public function execute() {
		$this->generateAutoloadForComponent( 'data-access', [ 'src' ] );

		$this->generateAutoloadForComponent(
			'lib',
			[ 'includes', 'maintenance' ],
			[
				'tests/phpunit/EntityRevisionLookupTestCase.php',
				'tests/phpunit/MockPropertyLabelResolver.php',
				'tests/phpunit/MockRepository.php',
				'tests/phpunit/Changes/ChangeRowTest.php',
				'tests/phpunit/Changes/EntityChangeTest.php',
				'tests/phpunit/Changes/MockRepoClientCentralIdLookup.php',
				'tests/phpunit/Changes/TestChanges.php',
				'tests/phpunit/Store/EntityInfoBuilderTestCase.php',
				'tests/phpunit/Store/EntityTermLookupTest.php',
				'tests/phpunit/Store/GenericEntityInfoBuilder.php',
				'tests/phpunit/Store/HttpUrlPropertyOrderProviderTestMockHttp.php',
				'tests/phpunit/Store/MockChunkAccess.php',
				'tests/phpunit/Store/MockPropertyInfoLookup.php',
				'tests/phpunit/Store/MockTermIndex.php',
				'tests/phpunit/Store/TermIndexTestCase.php',
				'tests/phpunit/Store/WikiTextPropertyOrderProviderTestHelper.php',
			]
		);

		$this->generateAutoloadForComponent(
			'repo',
			[ 'includes', 'maintenance' ],
			[
				'tests/phpunit/includes/BabelUserLanguageLookupDouble.php',
				'tests/phpunit/includes/EntityModificationTestHelper.php',
				'tests/phpunit/includes/NewItem.php',
				'tests/phpunit/includes/NewStatement.php',
				'tests/phpunit/includes/PermissionsHelper.php',
				'tests/phpunit/includes/Actions/ActionTestCase.php',
				'tests/phpunit/includes/Api/ApiFormatTestCase.php',
				'tests/phpunit/includes/Api/ApiModuleTestHelper.php',
				'tests/phpunit/includes/Api/EntityTestHelper.php',
				'tests/phpunit/includes/Api/ModifyTermTestCase.php',
				'tests/phpunit/includes/Api/PermissionsTestCase.php',
				'tests/phpunit/includes/Api/TermTestHelper.php',
				'tests/phpunit/includes/Api/WikibaseApiTestCase.php',
				'tests/phpunit/includes/ChangeOp/ChangeOpTestMockProvider.php',
				'tests/phpunit/includes/ChangeOp/StatementListProviderDummy.php',
				'tests/phpunit/includes/ChangeOp/Deserialization/AliasChangeOpDeserializationTester.php',
				'tests/phpunit/includes/ChangeOp/Deserialization/ChangeOpDeserializationAssert.php',
				'tests/phpunit/includes/ChangeOp/Deserialization/ClaimsChangeOpDeserializationTester.php',
				'tests/phpunit/includes/ChangeOp/Deserialization/DescriptionsChangeOpDeserializationTester.php',
				'tests/phpunit/includes/ChangeOp/Deserialization/LabelsChangeOpDeserializationTester.php',
				'tests/phpunit/includes/Content/EntityContentTestCase.php',
				'tests/phpunit/includes/Content/EntityHandlerTestCase.php',
				'tests/phpunit/includes/LinkedData/EntityDataTestProvider.php',
				'tests/phpunit/includes/Rdf/NTriplesRdfTestHelper.php',
				'tests/phpunit/includes/Rdf/RdfBuilderTestData.php',
				'tests/phpunit/includes/Search/Elastic/Fields/SearchFieldTestCase.php',
				'tests/phpunit/includes/Search/Elastic/Fields/WikibaseNumericFieldTestCase.php',
				'tests/phpunit/includes/Store/MockEntityIdPager.php',
				'tests/phpunit/includes/Specials/HtmlAssertionHelpers.php',
				'tests/phpunit/includes/Specials/SpecialModifyTermTestCase.php',
				'tests/phpunit/includes/Specials/SpecialNewEntityTestCase.php',
				'tests/phpunit/includes/Specials/SpecialWikibaseRepoPageTestBase.php',
				'tests/phpunit/includes/Validators/TestValidator.php',
				'tests/phpunit/maintenance/MockAddUnits.php',
			]
		);

		$this->generateAutoloadForComponent(
			'client',
			[ 'includes', 'maintenance' ],
			[
				'tests/phpunit/MockClientStore.php',
				'tests/phpunit/includes/Changes/MockPageUpdater.php',
				'tests/phpunit/includes/DataAccess/WikibaseDataAccessTestItemSetUpHelper.php',
				'tests/phpunit/includes/DataAccess/Scribunto/Scribunto_LuaWikibaseLibraryTestCase.php',
				'tests/phpunit/includes/Usage/UsageAccumulatorContractTester.php',
				'tests/phpunit/includes/Usage/UsageLookupContractTester.php',
				'tests/phpunit/includes/Usage/UsageTrackerContractTester.php',
			]
		);

		$this->generateAutoloadForComponent(
			'view',
			[ 'src' ],
			[
				'tests/phpunit/EntityViewTestCase.php',
			]
		);

		echo "Done.\n\n";
	}

	/**
	 * @param string $componentDir
	 * @param string[] $dirs
	 * @param string[] $files
	 */
	private function generateAutoloadForComponent( $componentDir, array $dirs, array $files = [] ) {
		$base = __DIR__ . '/../' . $componentDir;
		$generator = new AutoloadGenerator( $base );
		foreach ( $dirs as $componentDir ) {
			$generator->readDir( $base . '/' . $componentDir );
		}
		foreach ( glob( $base . '/*.php' ) as $file ) {
			$generator->readFile( realpath( $file ) );
		}
		foreach ( $files as $file ) {
			$generator->readFile( realpath( $base . '/' . $file ) );
		}

		$target = $generator->getTargetFileInfo();

		file_put_contents(
			$target['filename'],
			$generator->getAutoload( basename( __DIR__ ) . '/' . basename( __FILE__ ) )
		);
	}

}

$maintClass = GenerateWikibaseAutoload::class;
require_once RUN_MAINTENANCE_IF_MAIN;
