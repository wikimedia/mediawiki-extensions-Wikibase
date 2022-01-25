<?php

namespace Wikibase\Lib\Tests\Store\Sql\Terms;

use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use Psr\Log\NullLogger;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\Lib\Rdbms\DomainDb;
use Wikibase\Lib\Store\Sql\Terms\DatabaseItemTermStoreWriter;
use Wikibase\Lib\Store\Sql\Terms\DatabaseMatchingTermsLookup;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermInLangIdsAcquirer;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermInLangIdsResolver;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTypeIdsStore;
use Wikibase\Lib\Store\TermIndexSearchCriteria;
use Wikibase\Lib\StringNormalizer;
use Wikibase\Lib\TermIndexEntry;
use Wikibase\Lib\Tests\Rdbms\LocalRepoDbTestHelper;
use Wikimedia\Rdbms\DatabaseSqlite;
use Wikimedia\Rdbms\IDatabase;

/**
 * @covers \Wikibase\Lib\Store\Sql\Terms\DatabaseMatchingTermsLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DatabaseMatchingTermsLookupTest extends MediaWikiIntegrationTestCase {

	use LocalRepoDbTestHelper;

	/**
	 * @var IDatabase
	 */
	private $sqliteDb;

	/**
	 * @var DomainDb
	 */
	private $repoDb;

	protected function setUp(): void {
		// We can't use the mediawiki integration test since we union temp tables.
		$this->sqliteDb = $this->setUpNewDb();
		$this->repoDb = $this->getRepoDomainDb( $this->sqliteDb );
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

	/** @see testGetMatchingTerms */
	public function provideGetMatchingTerms() {
		[ $item0, $item1, $item2 ] = $this->getTestItems();

		yield 'EXACT MATCH not prefix, case sensitive' => [
			'entities' => [ $item0, $item1, $item2 ],
			'criteria' => [
				new TermIndexSearchCriteria( [
					'termText' => 'Mittens',
				] ),
			],
			'termTypes' => null,
			'entityTypes' => null,
			'options' => [
				'prefixSearch' => false,
				'caseSensitive' => true,
			],
			'expectedTermKeys' => [
				'Q11/label.de:Mittens',
			],
		];
		yield 'prefix, case sensitive' => [
			'entities' => [ $item0, $item1, $item2 ],
			'criteria' => [
				new TermIndexSearchCriteria( [
					'termText' => 'Mitte',
				] ),
			],
			'termTypes' => null,
			'entityTypes' => null,
			'options' => [
				'prefixSearch' => true,
				'caseSensitive' => true,
			],
			'expectedTermKeys' => [
				'Q11/label.de:Mittens',
			],
		];
		yield 'prefixSearch and not caseSensitive' => [
			'entities' => [ $item0, $item1, $item2 ],
			'criteria' => [
				new TermIndexSearchCriteria( [
					'termText' => 'KiTTeNS',
				] ),
			],
			'termTypes' => null,
			'entityTypes' => null,
			'options' => [
				'prefixSearch' => true,
				'caseSensitive' => false,
			],
			'expectedTermKeys' => [
				'Q11/label.fr:kittens love mittens',
				'Q22/label.en:KITTENS should have mittens',
				// If not asking for top terms the below would normally also be expected
				//'Q22/label.sv:kittens should have mittens',
				'Q10/label.en:kittens',
			],
		];
		yield 'prefixSearch and not caseSensitive LIMIT 1' => [
			'entities' => [ $item0, $item1, $item2 ],
			'criteria' => [
				new TermIndexSearchCriteria( [
					'termText' => 'KiTTeNS',
				] ),
			],
			'termTypes' => null,
			'entityTypes' => null,
			'options' => [
				'prefixSearch' => true,
				'caseSensitive' => false,
				'LIMIT' => 1,
			],
			'expectedTermKeys' => [
				'Q11/label.fr:kittens love mittens',
			],
		];
	}

	/** @dataProvider provideGetMatchingTerms */
	public function testGetMatchingTerms(
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

		foreach ( $entities as $entity ) {
			/** @var Item $entity */
			$store->storeTerms( $entity->getId(), $entity->getFingerprint() );
		}

		$actual = $lookup->getMatchingTerms( $criteria, $termTypes, $entityTypes, $options );

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
			$this->repoDb,
			MediaWikiServices::getInstance()->getMainWANObjectCache(),
			new NullLogger()
		);

		$composer = new EntityIdComposer( [
			'item' => function ( $repositoryName, $uniquePart ) {
				return ItemId::newFromRepositoryAndNumber( $repositoryName, $uniquePart );
			},
			'property' => function ( $repositoryName, $uniquePart ) {
				return NumericPropertyId::newFromRepositoryAndNumber( $repositoryName, $uniquePart );
			},
		] );
		return new DatabaseMatchingTermsLookup(
			$this->repoDb,
			$store,
			$store,
			$composer,
			new NullLogger()
		);
	}

	private function getItemTermStoreWriter() {
		$logger = new NullLogger();
		$typeIdsStore = new DatabaseTypeIdsStore(
			$this->repoDb,
			MediaWikiServices::getInstance()->getMainWANObjectCache()
		);

		return new DatabaseItemTermStoreWriter( $this->repoDb,
			$this->getServiceContainer()->getJobQueueGroup(),
			new DatabaseTermInLangIdsAcquirer( $this->repoDb, $typeIdsStore, $logger ),
			new DatabaseTermInLangIdsResolver( $typeIdsStore, $typeIdsStore,
				$this->repoDb, $logger ), new StringNormalizer()
		);
	}

}
