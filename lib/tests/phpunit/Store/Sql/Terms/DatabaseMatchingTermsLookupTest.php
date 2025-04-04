<?php

namespace Wikibase\Lib\Tests\Store\Sql\Terms;

use MediaWikiIntegrationTestCase;
use Psr\Log\NullLogger;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Lib\Rdbms\TermsDomainDb;
use Wikibase\Lib\Store\Sql\Terms\DatabaseItemTermStoreWriter;
use Wikibase\Lib\Store\Sql\Terms\DatabaseMatchingTermsLookup;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermInLangIdsAcquirer;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermInLangIdsResolver;
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
	 * @var TermsDomainDb
	 */
	private $termsDb;

	protected function setUp(): void {
		// We can't use the mediawiki integration test since we union temp tables.
		$this->sqliteDb = $this->setUpNewDb();
		$this->termsDb = $this->getTermsDomainDb( $this->sqliteDb );
	}

	private function setUpNewDb() {
		$db = DatabaseSqlite::newStandaloneInstance( ':memory:' );
		$db->sourceFile(
			__DIR__ . '/../../../../../../repo/sql/sqlite/term_store.sql' );

		return $db;
	}

	private static function getTestItems() {
		$item0 = new Item( new ItemId( 'Q10' ) );
		$item0->setLabel( 'en', 'kittens' );

		$item1 = new Item( new ItemId( 'Q11' ) );
		$item1->setLabel( 'nl', 'mittens' );
		$item1->setLabel( 'de', 'Mittens' );
		$item1->setLabel( 'fr', 'kittens love mittens' );

		$item2 = new Item( new ItemId( 'Q22' ) );
		$item2->setLabel( 'sv', 'kittens should have mittens' );
		$item2->setLabel( 'en', 'KITTENS should have mittens' );

		$item3 = new Item( new ItemId( 'Q33' ) );
		$item3->setAliases( 'en', [ 'kittens' ] );

		return [ $item0, $item1, $item2, $item3 ];
	}

	/** @see testGetMatchingTerms */
	public static function provideGetMatchingTerms() {
		[ $item0, $item1, $item2, $item3 ] = self::getTestItems();

		yield 'EXACT MATCH not prefix, case sensitive' => [
			'entities' => [ $item0, $item1, $item2 ],
			'criteria' => [
				new TermIndexSearchCriteria( [
					'termText' => 'Mittens',
				] ),
			],
			'termTypes' => TermTypes::TYPE_LABEL,
			'entityTypes' => null,
			'options' => [
				'prefixSearch' => false,
				'caseSensitive' => true,
			],
			'expectedTermKeys' => [
				'Q11/label.de:Mittens',
			],
		];
		yield 'EXACT MATCH not prefix, case sensitive, labels OR aliases' => [
			'entities' => [ $item0, $item1, $item2, $item3 ],
			'criteria' => [
				new TermIndexSearchCriteria( [
					'termText' => 'kittens',
				] ),
			],
			'termTypes' => [ TermTypes::TYPE_LABEL, TermTypes::TYPE_ALIAS ],
			'entityTypes' => null,
			'options' => [
				'prefixSearch' => false,
				'caseSensitive' => true,
			],
			'expectedTermKeys' => [
				'Q10/label.en:kittens',
				'Q33/alias.en:kittens',
			],
		];
		yield 'EXACT MATCH not prefix, case sensitive, labels OR aliases, LIMIT 1' => [
			'entities' => [ $item0, $item1, $item2, $item3 ],
			'criteria' => [
				new TermIndexSearchCriteria( [
					'termText' => 'kittens',
				] ),
			],
			'termTypes' => [ TermTypes::TYPE_LABEL, TermTypes::TYPE_ALIAS ],
			'entityTypes' => null,
			'options' => [
				'prefixSearch' => false,
				'caseSensitive' => true,
				'LIMIT' => 1,
			],
			'expectedTermKeys' => [
				'Q10/label.en:kittens',
			],
		];
		yield 'EXACT MATCH not prefix, case sensitive, labels OR aliases, LIMIT 1, OFFSET 1' => [
			'entities' => [ $item0, $item1, $item2, $item3 ],
			'criteria' => [
				new TermIndexSearchCriteria( [
					'termText' => 'kittens',
				] ),
			],
			'termTypes' => [ TermTypes::TYPE_LABEL, TermTypes::TYPE_ALIAS ],
			'entityTypes' => null,
			'options' => [
				'prefixSearch' => false,
				'caseSensitive' => true,
				'LIMIT' => 1,
				'OFFSET' => 1,
			],
			'expectedTermKeys' => [
				'Q33/alias.en:kittens',
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
		$composer = new EntityIdComposer( [
			'item' => function ( $uniquePart ) {
				return new ItemId( 'Q' . $uniquePart );
			},
			'property' => function ( $uniquePart ) {
				return new NumericPropertyId( 'P' . $uniquePart );
			},
		] );
		return new DatabaseMatchingTermsLookup(
			$this->termsDb,
			$composer,
			new NullLogger()
		);
	}

	private function getItemTermStoreWriter() {
		$logger = new NullLogger();

		return new DatabaseItemTermStoreWriter(
			$this->termsDb,
			$this->getServiceContainer()->getJobQueueGroup(),
			new DatabaseTermInLangIdsAcquirer( $this->termsDb, $logger ),
			new DatabaseTermInLangIdsResolver( $this->termsDb, $logger ),
			new StringNormalizer()
		);
	}

}
