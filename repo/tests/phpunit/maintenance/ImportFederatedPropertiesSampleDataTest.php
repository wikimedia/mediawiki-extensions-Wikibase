<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests\Maintenance;

use MediaWiki\Tests\Maintenance\MaintenanceBaseTestCase;
use User;
use Wikibase\Repo\Maintenance\ImportFederatedPropertiesSampleData;
use Wikibase\Repo\WikibaseRepo;

// files in maintenance/ are not autoloaded to avoid accidental usage, so load explicitly
require_once __DIR__ . '/../../../maintenance/importFederatedPropertiesSampleData.php';

/**
 * @covers \Wikibase\Repo\Maintenance\ImportFederatedPropertiesSampleData
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ImportFederatedPropertiesSampleDataTest extends MaintenanceBaseTestCase {

	protected function getMaintenanceClass() {
		return ImportFederatedPropertiesSampleData::class;
	}

	public function testStoreEntityWithTermData() {
		$entityStore = WikibaseRepo::getEntityStore();
		$user = User::newSystemUser( User::MAINTENANCE_SCRIPT_USER, [ 'steal' => true ] );

		$maintenance = new ImportFederatedPropertiesSampleData();
		$item = $maintenance->storeNewItemWithTermData( [ 'somelabel', 'somedescription' ], $entityStore, $user );

		$this->assertFalse( $item->isEmpty() );
		$this->assertTrue( $item->getLabels()->hasTermForLanguage( "en" ) );
		$this->assertTrue( $item->getDescriptions()->hasTermForLanguage( "en" ) );
		$this->assertEquals( 'somelabel', $item->getLabels()->getByLanguage( 'en' )->getText() );
		$this->assertEquals( 'somedescription', $item->getDescriptions()->getByLanguage( 'en' )->getText() );
	}

	/**
	 * @param $dataFile
	 * @param $expectedDataLines
	 */
	public function testGetDataToImport() {
		$dataFile = __DIR__ . '/../data/maintenance/federatedPropertiesTestDataFile.tsv';
		$expectedDataLines = [
			'Acacia saligna (Labill.) H.L.Wendl.	Golden wreath wattle	taxon',
			'Acridotheres tristis Linnaeus, 1766	Common or Indian myna	taxon',
			'Ailanthus altissima (Mill.) Swingle	Tree of heaven	taxon',
			'Alopochen aegyptiaca Linnaeus, 1766	Egyptian goose	taxon',
			'Alternanthera philoxeroides (Mart.) Griseb.	Alligator weed	taxon',
			'',
		];
		$maintenance = new ImportFederatedPropertiesSampleData();
		$actualDataLines = $maintenance->getDataToImport( $dataFile );

		$this->assertEquals( $expectedDataLines, $actualDataLines );
	}
}
