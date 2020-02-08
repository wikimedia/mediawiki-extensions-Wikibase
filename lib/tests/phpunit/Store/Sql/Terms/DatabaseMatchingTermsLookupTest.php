<?php

namespace Wikibase\Lib\Tests\Store\Sql\Terms;

use JobQueueGroup;
use MediaWiki\MediaWikiServices;
use Psr\Log\NullLogger;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\Lib\Store\Sql\Terms\DatabaseItemTermStoreWriter;
use Wikibase\Lib\Store\Sql\Terms\DatabaseMatchingTermsLookup;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermInLangIdsAcquirer;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermInLangIdsResolver;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTypeIdsStore;
use Wikibase\Lib\Store\TermIndexSearchCriteria;
use Wikibase\Lib\Tests\Store\Sql\Terms\Util\FakeLBFactory;
use Wikibase\Lib\Tests\Store\Sql\Terms\Util\FakeLoadBalancer;
use Wikibase\StringNormalizer;
use Wikibase\TermIndexEntry;
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
class DatabaseMatchingTermsLookupTest extends \MediaWikiIntegrationTestCase {
	/**
	 * @var IDatabase
	 */
	private $sqliteDb;

	/**
	 * @var ILBFactory
	 */
	private $lbFactory;

	public function setUp() : void {
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
			__DIR__ . '/../../../../../../repo/sql/AddNormalizedTermsTablesDDL.sql' );

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

	/**
	 * @see testGetTopMatchingTerms
	 */
	public function provideGetTopMatchingTerms() {
		list( $item0, $item1, $item2 ) = $this->getTestItems();

		return [
			'EXACT MATCH not prefix, case sensitive' => [
				[ // $entities
					$item0, $item1, $item2
				],
				[ // $criteria
					new TermIndexSearchCriteria( [
						'termText' => 'Mittens',
					] ),
				],
				null, // $termTypes
				null, // $entityTypes
				[ // $options
					'prefixSearch' => false,
					'caseSensitive' => true,
				],
				[ // $expectedTermKeys
					'Q11/label.de:Mittens',
				],
			],
			'prefix, case sensitive' => [
				[ // $entities
					$item0, $item1, $item2
				],
				[ // $criteria
					new TermIndexSearchCriteria( [
						'termText' => 'Mitte',
					] ),
				],
				null, // $termTypes
				null, // $entityTypes
				[ // $options
					'prefixSearch' => true,
					'caseSensitive' => true,
				],
				[ // $expectedTermKeys
					'Q11/label.de:Mittens',
				],
			],
			'prefixSearch and not caseSensitive' => [
				[ // $entities
					$item0, $item1, $item2
				],
				[ // $criteria
					new TermIndexSearchCriteria( [
						'termText' => 'KiTTeNS',
					] ),
				],
				null, // $termTypes
				null, // $entityTypes
				[ // $options
					'prefixSearch' => true,
					'caseSensitive' => false,
				],
				[ // $expectedTermKeys
					'Q11/label.fr:kittens love mittens',
					'Q22/label.en:KITTENS should have mittens',
					// If not asking for top terms the below would normally also be expected
					//'Q22/label.sv:kittens should have mittens',
					'Q10/label.en:kittens',
				],
			],
			'prefixSearch and not caseSensitive LIMIT 1' => [
				[ // $entities
					$item0, $item1, $item2
				],
				[ // $criteria
					new TermIndexSearchCriteria( [
						'termText' => 'KiTTeNS',
					] ),
				],
				null, // $termTypes
				null, // $entityTypes
				[ // $options
					'prefixSearch' => true,
					'caseSensitive' => false,
					'LIMIT' => 1,
				],
				[ // $expectedTermKeys
					'Q11/label.fr:kittens love mittens',
				],
			],
		];
	}

	/**
	 * @dataProvider provideGetTopMatchingTerms
	 */
	public function testGetTopMatchingTerms(
		array $entities,
		array $criteria,
		$termTypes,
		$entityTypes,
		array $options,
		array $expectedTermKeys
	) {
		if ( $options['caseSensitive'] === false ) {
			$this->markTestSkipped( 'Case insensitive search is not supported yet: T242644' );
		}
		$lookup = $this->getMatchingTermsLookup();
		$store = $this->getItemTermStoreWriter();

		foreach ( $entities as $entitiy ) {
			/** @var Item $entitiy */
			$store->storeTerms( $entitiy->getId(), $entitiy->getFingerprint() );
		}

		$actual = $lookup->getTopMatchingTerms( $criteria, $termTypes, $entityTypes, $options );

		$this->assertIsArray( $actual );

		$actualTermKeys = array_map( [ $this, 'getTermKey' ], $actual );
		$this->assertEquals( $expectedTermKeys, $actualTermKeys );
	}

	private function getTermKey( TermIndexEntry $term ) {
		$key = '';
		if ( $term->getEntityId() !== null ) {
			$key .= $term->getEntityId()->getSerialization();
		}

		$key .= '/';
		if ( $term->getTermType() !== null ) {
			$key .= $term->getTermType();
		}

		$key .= '.';
		if ( $term->getLanguage() !== null ) {
			$key .= $term->getLanguage();
		}

		$key .= ':';
		if ( $term->getText() !== null ) {
			$key .= $term->getText();
		}

		return $key;
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
				$this->lbFactory->getMainLB(), false, $logger ), new StringNormalizer(),
			$this->getItemSource()
		);
	}

	private function getItemSource() {
		return new EntitySource( 'test', false, [ 'item' => [ 'namespaceId' => 10, 'slot' => 'main' ] ], '', '', '', '' );
	}

}
