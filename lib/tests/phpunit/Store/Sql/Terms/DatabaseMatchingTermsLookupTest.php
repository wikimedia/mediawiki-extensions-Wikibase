<?php

namespace Wikibase\Lib\Tests\Store\Sql\Terms;

use JobQueueGroup;
use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use Psr\Log\NullLogger;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\Lib\Store\Sql\Terms\DatabaseItemTermStoreWriter;
use Wikibase\Lib\Store\Sql\Terms\DatabaseMatchingTermsLookup;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermInLangIdsAcquirer;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermInLangIdsResolver;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTypeIdsStore;
use Wikibase\Lib\StringNormalizer;
use Wikibase\Lib\Tests\Store\Sql\Terms\Util\FakeLBFactory;
use Wikibase\Lib\Tests\Store\Sql\Terms\Util\FakeLoadBalancer;
use Wikimedia\Rdbms\DatabaseSqlite;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\ILBFactory;

/**
 * @covers \Wikibase\Lib\Store\Sql\Terms\DatabaseTermInLangIdsAcquirer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DatabaseMatchingTermsLookupTest extends MediaWikiIntegrationTestCase {
	/**
	 * @var IDatabase
	 */
	private $sqliteDb;

	/**
	 * @var ILBFactory
	 */
	private $lbFactory;

	protected function setUp(): void {
		// We can't use the mediawiki integration test since we union temp tables.
		$this->sqliteDb = $this->setUpNewDb();
		$loadBalancer = new FakeLoadBalancer( [
			'dbr' => $this->sqliteDb
		] );
		$this->lbFactory = new FakeLBFactory( [
			'lb' => $loadBalancer
		] );
	}

	private function setUpNewDb() {
		$db = DatabaseSqlite::newStandaloneInstance( ':memory:' );
		$db->sourceFile(
			__DIR__ . '/../../../../../../repo/sql/sqlite/term_store.sql' );

		return $db;
	}

	private function getTestItems() {
		$item0 = new Item( new ItemId( 'Q10' ) );
		$item0->setLabel( 'en', 'kittens' );

		$item1 = new Item( new ItemId( 'Q11' ) );
		$item1->setLabel( 'nl', 'mittens' );
		$item1->setLabel( 'de', 'Mittens' );
		$item1->setLabel( 'fr', 'kittens love mittens' );

		$item2 = new Item( new ItemId( 'Q22' ) );
		$item2->setLabel( 'sv', 'kittens should have mittens' );
		$item2->setLabel( 'en', 'KITTENS should have mittens' );

		return [ $item0, $item1, $item2 ];
	}

	private function getMatchingTermsLookup() {
		$store = new DatabaseTypeIdsStore(
			$this->lbFactory->getMainLB(),
			MediaWikiServices::getInstance()->getMainWANObjectCache(),
			false,
			new NullLogger()
		);

		$composer = new EntityIdComposer( [
			'item' => function ( $repositoryName, $uniquePart ) {
				return ItemId::newFromRepositoryAndNumber( $repositoryName, $uniquePart );
			},
			'property' => function ( $repositoryName, $uniquePart ) {
				return PropertyId::newFromRepositoryAndNumber( $repositoryName, $uniquePart );
			},
		] );
		return new DatabaseMatchingTermsLookup(
			$this->lbFactory->getMainLB(),
			$store,
			$store,
			$composer,
			new NullLogger()
		);
	}

	private function getItemTermStoreWriter() {
		$logger = new NullLogger();
		$typeIdsStore = new DatabaseTypeIdsStore(
			$this->lbFactory->getMainLB(),
			MediaWikiServices::getInstance()->getMainWANObjectCache()
		);

		return new DatabaseItemTermStoreWriter( $this->lbFactory->getMainLB(),
			JobQueueGroup::singleton(),
			new DatabaseTermInLangIdsAcquirer( $this->lbFactory, $typeIdsStore, $logger ),
			new DatabaseTermInLangIdsResolver( $typeIdsStore, $typeIdsStore,
				$this->lbFactory->getMainLB(), false, $logger ), new StringNormalizer()
		);
	}

}
