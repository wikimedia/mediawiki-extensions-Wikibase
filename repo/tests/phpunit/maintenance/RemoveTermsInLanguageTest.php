<?php

namespace Wikibase\Repo\Tests\Maintenance;

use MediaWiki\Tests\Maintenance\MaintenanceBaseTestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\Maintenance\RemoveTermsInLanguage;
use Wikibase\Repo\WikibaseRepo;

// files in maintenance/ are not autoloaded to avoid accidental usage, so load explicitly
require_once __DIR__ . '/../../../maintenance/removeTermsInLanguage.php';

/**
 * @covers \Wikibase\Repo\Maintenance\RemoveTermsInLanguage
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class RemoveTermsInLanguageTest extends MaintenanceBaseTestCase {

	protected function getMaintenanceClass() {
		return RemoveTermsInLanguage::class;
	}

	/**
	 * @throws StorageException
	 */
	public function testTermsOfLanguageAreRemoved() {
		$entityId = $this->storeNewItem();
		$entityLookup = WikibaseRepo::getEntityLookup();

		$this->maintenance->loadWithArgv( [ "--entity-id=" . $entityId, "--language=en" ] );
		$this->maintenance->execute();
		/**
		 * @var Item $entity
		 */
		$entity = $entityLookup->getEntity( $entityId );

		$this->assertFalse( $entity->isEmpty() );
		$this->assertFalse( $entity->getLabels()->hasTermForLanguage( "en" ) );
		$this->assertFalse( $entity->getDescriptions()->hasTermForLanguage( "en" ) );
		$this->assertFalse( $entity->getAliasGroups()->hasGroupForLanguage( "en" ) );
	}

	/**
	 * @return \Wikibase\DataModel\Entity\ItemId
	 * @throws StorageException
	 */
	protected function storeNewItem() {
		$testUser = $this->getTestUser()->getUser();

		$store = WikibaseRepo::getEntityStore();

		$item = new Item();
		$item->setLabel( "en", "en-label" );
		$item->setDescription( "en", "en-description" );
		$item->setAliases( "en", [ "en-alias1", "en-alias2" ] );
		$item->setLabel( 'de', 'de-label' );
		$item->setDescription( 'de', 'de-desc' );
		$item->setDescription( 'es', 'es-desc' );
		$item->setAliases( 'pt', [ 'AA', 'BB' ] );

		try {
			$store->saveEntity( $item, 'testing', $testUser, EDIT_NEW );
		} catch ( StorageException $e ) {
			throw new StorageException( "Unable to save new entity" );
		}

		return $item->getId();
	}

}
