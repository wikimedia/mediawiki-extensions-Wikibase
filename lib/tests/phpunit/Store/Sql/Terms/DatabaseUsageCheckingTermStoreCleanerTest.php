<?php

namespace Wikibase\Lib\Tests\Store\Sql\Terms;

use MediaWikiIntegrationTestCase;
use Wikibase\Lib\Store\Sql\Terms\DatabaseInnerTermStoreCleaner;
use Wikibase\Lib\Store\Sql\Terms\DatabaseUsageCheckingTermStoreCleaner;
use Wikibase\Lib\Tests\Store\Sql\Terms\Util\FakeLoadBalancer;
use Wikibase\Lib\WikibaseSettings;

/**
 * @covers \Wikibase\Lib\Store\Sql\Terms\DatabaseInnerTermStoreCleaner
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class DatabaseUsageCheckingTermStoreCleanerTest extends MediaWikiIntegrationTestCase {

	private $innerCleaner;

	protected function setUp() : void {
		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->markTestSkipped( "Skipping because WikibaseClient doesn't have local term store tables." );
		}

		parent::setUp();
		$this->tablesUsed[] = 'wbt_item_terms';
		$this->tablesUsed[] = 'wbt_property_terms';
		$this->innerCleaner = $this->createMock( DatabaseInnerTermStoreCleaner::class );
	}

	private function getCleaner(): DatabaseUsageCheckingTermStoreCleaner {
		$loadBalancer = new FakeLoadBalancer( [ 'dbr' => $this->db ] );
		return new DatabaseUsageCheckingTermStoreCleaner( $loadBalancer, $this->innerCleaner );
	}

	public function testCleaningUnsharedTermInLangUsesInnerCleaner() {
		$this->innerCleaner->expects( $this->once() )->method( 'cleanTermInLangIds' )->with( $this->db, $this->db, [ 1 ] );
		$cleaner = $this->getCleaner();
		$cleaner->cleanTermInLangIds( [ 1 ] );
	}

	public function testRemovingSharedTermDoesNotGetUndulyDeleted() {
		$stillUsedItemTermInLang = 345342;
		$stillUsedPropertyTermInLang = 3455342;
		$itemId = 12324;
		$propertyId = 756751;
		$termInLangIdToDelete = 546562;
		$this->insertItemTermRow( $itemId, $stillUsedItemTermInLang );
		$this->insertPropertyTermRow( $propertyId, $stillUsedPropertyTermInLang );
		$this->innerCleaner->expects( $this->once() )->method( 'cleanTermInLangIds' )->with( $this->db, $this->db, [ $termInLangIdToDelete ] );
		$cleaner = $this->getCleaner();
		$cleaner->cleanTermInLangIds( [ $termInLangIdToDelete, $stillUsedItemTermInLang, $stillUsedPropertyTermInLang ] );
	}

	private function insertItemTermRow( int $itemid, int $termInLangId ): void {
		$this->db->insert( 'wbt_item_terms', [ 'wbit_item_id' => $itemid, 'wbit_term_in_lang_id' => $termInLangId ] );
	}

	private function insertPropertyTermRow( int $itemid, int $termInLangId ): void {
		$this->db->insert( 'wbt_property_terms', [ 'wbpt_property_id' => $itemid, 'wbpt_term_in_lang_id' => $termInLangId ] );
	}

}
