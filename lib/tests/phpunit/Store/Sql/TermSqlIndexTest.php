<?php

namespace Wikibase\Lib\Tests\Store\Sql;

use MWException;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\Tests\DataAccessSettingsFactory;
use Wikibase\DataAccess\UnusableEntitySource;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\DataModel\Services\EntityId\PrefixMappingEntityIdParser;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\Store\TermIndexSearchCriteria;
use Wikibase\StringNormalizer;
use Wikibase\TermIndexEntry;
use Wikibase\Lib\Store\Sql\TermSqlIndex;
use Wikibase\WikibaseSettings;
use Wikimedia\Assert\ParameterAssertionException;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikibase\Lib\Store\Sql\TermSqlIndex
 *
 * @group Wikibase
 * @group WikibaseStore
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class TermSqlIndexTest extends \MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();

		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->markTestSkipped( "Skipping because a local wb_terms table"
				. " is not available on a WikibaseClient only instance." );
		}

		$this->tablesUsed[] = 'wb_terms';
	}

	public function provideInvalidRepositoryNames() {
		return [
			'repository name containing colon' => [ 'foo:bar' ],
			'non-string as repository name' => [ 12345 ],
		];
	}

	/**
	 * @dataProvider provideInvalidRepositoryNames
	 */
	public function testGivenInvalidRepositoryName_constructorThrowsException( $repositoryName ) {
		$this->setExpectedException( ParameterAssertionException::class );
		new TermSqlIndex(
			new StringNormalizer(),
			new EntityIdComposer( [] ),
			new BasicEntityIdParser(),
			new UnusableEntitySource(),
			DataAccessSettingsFactory::repositoryPrefixBasedFederation(),
			false,
			$repositoryName
		);
	}

	/**
	 * @return TermSqlIndex
	 */
	private function getTermIndex() {
		return new TermSqlIndex(
			new StringNormalizer(),
			new EntityIdComposer( [
				'item' => function( $repositoryName, $uniquePart ) {
					return ItemId::newFromRepositoryAndNumber( $repositoryName, $uniquePart );
				},
				'property' => function( $repositoryName, $uniquePart ) {
					return PropertyId::newFromRepositoryAndNumber( $repositoryName, $uniquePart );
				},
			] ),
			new BasicEntityIdParser(),
			new UnusableEntitySource(),
			DataAccessSettingsFactory::repositoryPrefixBasedFederation()
		);
	}

	private function newTermSqlIndexForSourceBasedFederation() {
		$irrelevantItemNamespaceId = 100;
		$irrelevantPropertyNamespaceId = 200;

		return new TermSqlIndex(
			new StringNormalizer(),
			new EntityIdComposer( [
				'item' => function ( $repositoryName, $uniquePart ) {
					return ItemId::newFromRepositoryAndNumber( $repositoryName, $uniquePart );
				},
				'property' => function ( $repositoryName, $uniquePart ) {
					return PropertyId::newFromRepositoryAndNumber( $repositoryName, $uniquePart );
				},
			] ),
			new BasicEntityIdParser(),
			new EntitySource(
				'testsource',
				false,
				[
					'item' => [ 'namespaceId' => $irrelevantItemNamespaceId, 'slot' => 'main' ],
					'property' => [ 'namespaceId' => $irrelevantPropertyNamespaceId, 'slot' => 'main' ],
				],
				'',
				'',
				'',
				''
			),
			DataAccessSettingsFactory::entitySourceBasedFederation()
		);
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

	public function provideGetMatchingTerms() {
		list( $item0, $item1, $item2 ) = $this->getTestItems();

		return [
			'cross-language match' => [
				[ // $entities
					$item0, $item1
				],
				[ // $criteria
					new TermIndexSearchCriteria( [
						'termText' => 'mittens', // should match
					] ),
					new TermIndexSearchCriteria( [
						'termText' => 'Kittens', // case doesn't match
					] ),
					new TermIndexSearchCriteria( [
						'termText' => 'Mitt', // prefix isn't sufficient
					] ),
					new TermIndexSearchCriteria( [
						'termLanguage' => 'en', // language mismatch
						'termText' => 'Mittens',
					] ),
					new TermIndexSearchCriteria( [
						'termType' => 'alias', // type mismatch
						'termText' => 'Mittens',
					] ),
				],
				null, // $termTypes
				null, // $entityTypes
				[], // $options
				[ // $expectedTermKeys
					'Q11/label.nl:mittens',
				]
			],
			'case insensitive prefix' => [
				[ // $entities
					$item0, $item1
				],
				[ // $criteria
					new TermIndexSearchCriteria( [
						'termLanguage' => 'de', // language mismatch
						'termText' => 'kitt',
					] ),
					new TermIndexSearchCriteria( [
						'termText' => 'mitt', // prefix should match regardless of case
					] ),
				],
				null, // $termTypes
				null, // $entityTypes
				[ // $options
					'caseSensitive' => false,
					'prefixSearch' => true,
				],
				[ // $expectedTermKeys
					'Q11/label.nl:mittens',
					'Q11/label.de:Mittens',
				]
			],
			'restrict by term type' => [
				[ // $entities
					$item0, $item1
				],
				[ // $criteria
					new TermIndexSearchCriteria( [
						'termText' => 'mittens',
					] ),
				],
				TermIndexEntry::TYPE_ALIAS, // $termTypes
				null, // $entityTypes
				[], // $options
				[], // $expectedTermKeys
			],
			'allow multiple term type' => [
				[ // $entities
					$item0, $item1
				],
				[ // $criteria
					new TermIndexSearchCriteria( [
						'termText' => 'mittens',
					] ),
				],
				[ TermIndexEntry::TYPE_ALIAS, TermIndexEntry::TYPE_LABEL ], // $termTypes
				null, // $entityTypes
				[], // $options
				[ // $expectedTermKeys
					'Q11/label.nl:mittens',
				]
			],
			'restrict by entity type' => [
				[ // $entities
					$item0, $item1
				],
				[ // $criteria
					new TermIndexSearchCriteria( [
						'termText' => 'mittens',
					] ),
				],
				null, // $termTypes
				Property::ENTITY_TYPE, // $entityTypes
				[], // $options
				[], // $expectedTermKeys
			],
			'allow multiple entity type' => [
				[ // $entities
					$item0, $item1
				],
				[ // $criteria
					new TermIndexSearchCriteria( [
						'termText' => 'mittens',
					] ),
				],
				null, // $termTypes
				[ Property::ENTITY_TYPE, Item::ENTITY_TYPE ], // $entityTypes
				[], // $options
				[ // $expectedTermKeys
					'Q11/label.nl:mittens',
				]
			],
			'orderByWeight, prefixSearch and caseSensitive options' => [
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
				[
					'orderByWeight' => true,
					'prefixSearch' => true,
					'caseSensitive' => false,
				], // $options
				[ // $expectedTermKeys
					'Q11/label.fr:kittens love mittens',
					'Q22/label.en:KITTENS should have mittens',
					'Q22/label.sv:kittens should have mittens',
					'Q10/label.en:kittens',
				]
			],
		];
	}

	/**
	 * @dataProvider provideGetMatchingTerms
	 */
	public function testGetMatchingTerms(
		array $entities,
		array $criteria,
		$termTypes,
		$entityTypes,
		array $options,
		array $expectedTermKeys
	) {
		$lookup = $this->getTermIndex();

		foreach ( $entities as $entitiy ) {
			$lookup->saveTermsOfEntity( $entitiy );
		}

		$actual = $lookup->getMatchingTerms( $criteria, $termTypes, $entityTypes, $options );

		$this->assertInternalType( 'array', $actual );

		$actualTermKeys = array_map( [ $this, 'getTermKey' ], $actual );

		if ( !array_key_exists( 'orderByWeight', $options ) || $options['orderByWeight'] === false ) {
			$this->assertArrayEquals( $expectedTermKeys, $actualTermKeys, false );
		} else {
			$this->assertArrayEquals( $expectedTermKeys, $actualTermKeys, true );
		}
	}

	/**
	 * @dataProvider provideGetMatchingTerms
	 */
	public function testGetMatchingTerms_entitySourceBasedFederation(
		array $entities,
		array $criteria,
		$termTypes,
		$entityTypes,
		array $options,
		array $expectedTermKeys
	) {
		$lookup = $this->newTermSqlIndexForSourceBasedFederation();

		foreach ( $entities as $entitiy ) {
			$lookup->saveTermsOfEntity( $entitiy );
		}

		$actual = $lookup->getMatchingTerms( $criteria, $termTypes, $entityTypes, $options );

		$this->assertInternalType( 'array', $actual );

		$actualTermKeys = array_map( [ $this, 'getTermKey' ], $actual );

		if ( !array_key_exists( 'orderByWeight', $options ) || $options['orderByWeight'] === false ) {
			$this->assertArrayEquals( $expectedTermKeys, $actualTermKeys, false );
		} else {
			$this->assertArrayEquals( $expectedTermKeys, $actualTermKeys, true );
		}
	}

	public function getTermKey( TermIndexEntry $term ) {
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

	/**
	 * This wraps around provideGetMatchingTerms removing duplicate keys for entityIds that are already expected
	 * @see provideGetMatchingTerms
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
		$lookup = $this->getTermIndex();

		foreach ( $entities as $entitiy ) {
			$lookup->saveTermsOfEntity( $entitiy );
		}

		$actual = $lookup->getTopMatchingTerms( $criteria, $termTypes, $entityTypes, $options );

		$this->assertInternalType( 'array', $actual );

		$actualTermKeys = array_map( [ $this, 'getTermKey' ], $actual );
		$this->assertEquals( $expectedTermKeys, $actualTermKeys );
	}

	/**
	 * @dataProvider provideGetTopMatchingTerms
	 */
	public function testGetTopMatchingTerms_entitySourceBasedFederation(
		array $entities,
		array $criteria,
		$termTypes,
		$entityTypes,
		array $options,
		array $expectedTermKeys
	) {
		$lookup = $this->newTermSqlIndexForSourceBasedFederation();

		foreach ( $entities as $entitiy ) {
			$lookup->saveTermsOfEntity( $entitiy );
		}

		$actual = $lookup->getTopMatchingTerms( $criteria, $termTypes, $entityTypes, $options );

		$this->assertInternalType( 'array', $actual );

		$actualTermKeys = array_map( [ $this, 'getTermKey' ], $actual );
		$this->assertEquals( $expectedTermKeys, $actualTermKeys );
	}

	public function testDeleteTermsForEntity() {
		$lookup = $this->getTermIndex();

		$id = new ItemId( 'Q10' );
		$item = new Item( $id );

		$item->setLabel( 'en', 'abc' );
		$item->setLabel( 'de', 'def' );
		$item->setLabel( 'nl', 'ghi' );
		$item->setDescription( 'en', 'testDeleteTermsForEntity' );
		$item->setAliases( 'fr', [ 'o', '_', 'O' ] );

		$lookup->saveTermsOfEntity( $item );

		$this->assertTermExists( $lookup, 'testDeleteTermsForEntity' );

		$this->assertTrue( $lookup->deleteTermsOfEntity( $item->getId() ) !== false );

		$this->assertNotTermExists( $lookup, 'testDeleteTermsForEntity' );

		$abc = new TermIndexSearchCriteria( [ 'termType' => TermIndexEntry::TYPE_LABEL, 'termText' => 'abc' ] );
		$matchedTerms = $lookup->getMatchingTerms( [ $abc ], [ TermIndexEntry::TYPE_LABEL ], Item::ENTITY_TYPE );
		foreach ( $matchedTerms as $matchedTerm ) {
			if ( $matchedTerm->getEntityId() === $id ) {
				$this->fail( 'Failed to delete term or entity: ' . $id->getSerialization() );
			}
		}
	}

	public function testDeleteTermsForEntity_entitySourceBasedFederation() {
		$lookup = $this->newTermSqlIndexForSourceBasedFederation();

		$id = new ItemId( 'Q10' );
		$item = new Item( $id );

		$item->setLabel( 'en', 'abc' );
		$item->setLabel( 'de', 'def' );
		$item->setLabel( 'nl', 'ghi' );
		$item->setDescription( 'en', 'testDeleteTermsForEntity' );
		$item->setAliases( 'fr', [ 'o', '_', 'O' ] );

		$lookup->saveTermsOfEntity( $item );

		$this->assertTermExists( $lookup, 'testDeleteTermsForEntity' );

		$this->assertTrue( $lookup->deleteTermsOfEntity( $item->getId() ) !== false );

		$this->assertNotTermExists( $lookup, 'testDeleteTermsForEntity' );

		$abc = new TermIndexSearchCriteria( [ 'termType' => TermIndexEntry::TYPE_LABEL, 'termText' => 'abc' ] );
		$matchedTerms = $lookup->getMatchingTerms( [ $abc ], [ TermIndexEntry::TYPE_LABEL ], Item::ENTITY_TYPE );
		foreach ( $matchedTerms as $matchedTerm ) {
			if ( $matchedTerm->getEntityId() === $id ) {
				$this->fail( 'Failed to delete term or entity: ' . $id->getSerialization() );
			}
		}
	}

	public function testSaveTermsOfEntity() {
		$lookup = $this->getTermIndex();

		$item = new Item( new ItemId( 'Q568431314' ) );

		$item->setLabel( 'en', 'abc' );
		$item->setLabel( 'de', 'def' );
		$item->setLabel( 'nl', 'ghi' );
		$item->setDescription( 'en', 'testSaveTermsForEntity' );
		$item->setAliases( 'fr', [ 'o', '_', 'O' ] );

		$this->assertTrue( $lookup->saveTermsOfEntity( $item ) );

		$this->assertTermExists( $lookup,
			'testSaveTermsForEntity',
			TermIndexEntry::TYPE_DESCRIPTION,
			'en',
			Item::ENTITY_TYPE
		);

		$this->assertTermExists( $lookup,
			'ghi',
			TermIndexEntry::TYPE_LABEL,
			'nl',
			Item::ENTITY_TYPE
		);

		$this->assertTermExists( $lookup,
			'o',
			TermIndexEntry::TYPE_ALIAS,
			'fr',
			Item::ENTITY_TYPE
		);

		// save again - this should hit an optimized code path
		// that avoids re-saving the terms if they are the same as before.
		$this->assertTrue( $lookup->saveTermsOfEntity( $item ) );

		$this->assertTermExists( $lookup,
			'testSaveTermsForEntity',
			TermIndexEntry::TYPE_DESCRIPTION,
			'en',
			Item::ENTITY_TYPE
		);

		$this->assertTermExists( $lookup,
			'ghi',
			TermIndexEntry::TYPE_LABEL,
			'nl',
			Item::ENTITY_TYPE
		);

		$this->assertTermExists( $lookup,
			'o',
			TermIndexEntry::TYPE_ALIAS,
			'fr',
			Item::ENTITY_TYPE
		);

		// modify and save again - this should NOT skip saving,
		// and make sure the modified term is in the database.
		$item->setLabel( 'nl', 'xyz' );
		$this->assertTrue( $lookup->saveTermsOfEntity( $item ) );

		$this->assertTermExists( $lookup,
			'testSaveTermsForEntity',
			TermIndexEntry::TYPE_DESCRIPTION,
			'en',
			Item::ENTITY_TYPE
		);

		$this->assertTermExists( $lookup,
			'xyz',
			TermIndexEntry::TYPE_LABEL,
			'nl',
			Item::ENTITY_TYPE
		);

		$this->assertTermExists( $lookup,
			'o',
			TermIndexEntry::TYPE_ALIAS,
			'fr',
			Item::ENTITY_TYPE
		);
	}

	public function testSaveTermsOfEntity_entitySourceBasedFederation() {
		$lookup = $this->newTermSqlIndexForSourceBasedFederation();

		$item = new Item( new ItemId( 'Q568431314' ) );

		$item->setLabel( 'en', 'abc' );
		$item->setLabel( 'de', 'def' );
		$item->setLabel( 'nl', 'ghi' );
		$item->setDescription( 'en', 'testSaveTermsForEntity' );
		$item->setAliases( 'fr', [ 'o', '_', 'O' ] );

		$this->assertTrue( $lookup->saveTermsOfEntity( $item ) );

		$this->assertTermExists( $lookup,
			'testSaveTermsForEntity',
			TermIndexEntry::TYPE_DESCRIPTION,
			'en',
			Item::ENTITY_TYPE
		);

		$this->assertTermExists( $lookup,
			'ghi',
			TermIndexEntry::TYPE_LABEL,
			'nl',
			Item::ENTITY_TYPE
		);

		$this->assertTermExists( $lookup,
			'o',
			TermIndexEntry::TYPE_ALIAS,
			'fr',
			Item::ENTITY_TYPE
		);

		// save again - this should hit an optimized code path
		// that avoids re-saving the terms if they are the same as before.
		$this->assertTrue( $lookup->saveTermsOfEntity( $item ) );

		$this->assertTermExists( $lookup,
			'testSaveTermsForEntity',
			TermIndexEntry::TYPE_DESCRIPTION,
			'en',
			Item::ENTITY_TYPE
		);

		$this->assertTermExists( $lookup,
			'ghi',
			TermIndexEntry::TYPE_LABEL,
			'nl',
			Item::ENTITY_TYPE
		);

		$this->assertTermExists( $lookup,
			'o',
			TermIndexEntry::TYPE_ALIAS,
			'fr',
			Item::ENTITY_TYPE
		);

		// modify and save again - this should NOT skip saving,
		// and make sure the modified term is in the database.
		$item->setLabel( 'nl', 'xyz' );
		$this->assertTrue( $lookup->saveTermsOfEntity( $item ) );

		$this->assertTermExists( $lookup,
			'testSaveTermsForEntity',
			TermIndexEntry::TYPE_DESCRIPTION,
			'en',
			Item::ENTITY_TYPE
		);

		$this->assertTermExists( $lookup,
			'xyz',
			TermIndexEntry::TYPE_LABEL,
			'nl',
			Item::ENTITY_TYPE
		);

		$this->assertTermExists( $lookup,
			'o',
			TermIndexEntry::TYPE_ALIAS,
			'fr',
			Item::ENTITY_TYPE
		);
	}

	public function testUpdateTermsOfEntity() {
		$item = new Item( new ItemId( 'Q568431314' ) );

		// save original set of terms
		$item->setLabel( 'en', 'abc' );
		$item->setLabel( 'de', 'def' );
		$item->setLabel( 'nl', 'ghi' );
		$item->setDescription( 'en', '-abc-' );
		$item->setDescription( 'de', '-def-' );
		$item->setDescription( 'nl', '-ghi-' );
		$item->setAliases( 'en', [ 'ABC', '_', 'X' ] );
		$item->setAliases( 'de', [ 'DEF', '_', 'Y' ] );
		$item->setAliases( 'nl', [ 'GHI', '_', 'Z' ] );

		$lookup = $this->getTermIndex();
		$lookup->saveTermsOfEntity( $item );

		// modify the item and save new set of terms
		$item = new Item( new ItemId( 'Q568431314' ) );
		$item->setLabel( 'en', 'abc' );
		$item->setLabel( 'nl', 'jke' );
		$item->setDescription( 'en', '-abc-' );
		$item->setDescription( 'de', '-def-' );
		$item->setDescription( 'nl', '-ghi-' );
		$item->setDescription( 'it', 'ABC' );
		$item->setAliases( 'en', [ 'ABC', 'X', '_' ] );
		$item->setAliases( 'de', [ 'DEF', 'Y' ] );
		$item->setAliases( 'nl', [ '_', 'Z', 'foo' ] );

		$lookup->saveTermsOfEntity( $item );

		// check that the stored terms are the ones in the modified items
		$expectedTerms = $lookup->getEntityTerms( $item );
		$actualTerms = $lookup->getTermsOfEntity( $item->getId() );

		$missingTerms = array_udiff( $expectedTerms, $actualTerms, [ TermIndexEntry::class, 'compare' ] );
		$extraTerms = array_udiff( $actualTerms, $expectedTerms, [ TermIndexEntry::class, 'compare' ] );

		$this->assertEquals( [], $missingTerms, 'Missing terms' );
		$this->assertEquals( [], $extraTerms, 'Extra terms' );
	}

	public function testUpdateTermsOfEntity_entitySourceBasedFederation() {
		$item = new Item( new ItemId( 'Q568431314' ) );

		// save original set of terms
		$item->setLabel( 'en', 'abc' );
		$item->setLabel( 'de', 'def' );
		$item->setLabel( 'nl', 'ghi' );
		$item->setDescription( 'en', '-abc-' );
		$item->setDescription( 'de', '-def-' );
		$item->setDescription( 'nl', '-ghi-' );
		$item->setAliases( 'en', [ 'ABC', '_', 'X' ] );
		$item->setAliases( 'de', [ 'DEF', '_', 'Y' ] );
		$item->setAliases( 'nl', [ 'GHI', '_', 'Z' ] );

		$lookup = $this->newTermSqlIndexForSourceBasedFederation();
		$lookup->saveTermsOfEntity( $item );

		// modify the item and save new set of terms
		$item = new Item( new ItemId( 'Q568431314' ) );
		$item->setLabel( 'en', 'abc' );
		$item->setLabel( 'nl', 'jke' );
		$item->setDescription( 'en', '-abc-' );
		$item->setDescription( 'de', '-def-' );
		$item->setDescription( 'nl', '-ghi-' );
		$item->setDescription( 'it', 'ABC' );
		$item->setAliases( 'en', [ 'ABC', 'X', '_' ] );
		$item->setAliases( 'de', [ 'DEF', 'Y' ] );
		$item->setAliases( 'nl', [ '_', 'Z', 'foo' ] );

		$lookup->saveTermsOfEntity( $item );

		// check that the stored terms are the ones in the modified items
		$expectedTerms = $lookup->getEntityTerms( $item );
		$actualTerms = $lookup->getTermsOfEntity( $item->getId() );

		$missingTerms = array_udiff( $expectedTerms, $actualTerms, [ TermIndexEntry::class, 'compare' ] );
		$extraTerms = array_udiff( $actualTerms, $expectedTerms, [ TermIndexEntry::class, 'compare' ] );

		$this->assertEquals( [], $missingTerms, 'Missing terms' );
		$this->assertEquals( [], $extraTerms, 'Extra terms' );
	}

	private function getTermConflictEntities() {
		$entities = [
			$this->makeTermConflictItem( 'Q1', 'de', 'Foo', 'Bar' ),
			$this->makeTermConflictItem( 'Q2', 'de', 'Bar', 'Foo' ),
			$this->makeTermConflictItem( 'Q3', 'en', 'Foo', 'Bar' ),
			$this->makeTermConflictItem( 'Q4', 'en', 'Bar', 'Foo' ),
			$this->makeTermConflictItem( 'Q5', 'de', 'Foo', 'Quux' )
		];

		$deFooBarP6 = Property::newFromType( 'string' );
		$deFooBarP6->setId( new PropertyId( 'P6' ) );
		$deFooBarP6->setLabel( 'de', 'Foo' );
		$deFooBarP6->setAliases( 'de', [ 'AFoo' ] );
		$deFooBarP6->setDescription( 'de', 'Bar' );

		$entities[] = $deFooBarP6;

		return $entities;
	}

	private function makeTermConflictItem( $id, $languageCode, $label, $description ) {
		$item = new Item( new ItemId( $id ) );
		$item->setLabel( $languageCode, $label );
		$item->setDescription( $languageCode, $description );

		return $item;
	}

	public function labelConflictProvider() {
		$entities = $this->getTermConflictEntities();

		return [
			'conflicting label' => [
				$entities,
				Property::ENTITY_TYPE,
				[ 'de' => 'Foo' ],
				[],
				[ 'P6' ],
			],
			'conflicting label with different case' => [
				$entities,
				Property::ENTITY_TYPE,
				[ 'de' => 'fOO' ],
				[],
				[ 'P6' ],
			],
			'conflict between label and alias' => [
				$entities,
				Property::ENTITY_TYPE,
				[ 'de' => 'AFoo' ],
				[],
				[ 'P6' ],
			],
			'conflict between alias and label' => [
				$entities,
				Property::ENTITY_TYPE,
				[],
				[ 'de' => 'Foo' ],
				[ 'P6' ],
			],
			'conflicting alias' => [
				$entities,
				Property::ENTITY_TYPE,
				[],
				[ 'de' => 'AFoo' ],
				[ 'P6' ],
			],
			'no conflicting label' => [
				$entities,
				Item::ENTITY_TYPE,
				[ 'de' => 'Nope' ],
				[],
				[],
			],
			'conflicts in multiple languages' => [
				$entities,
				Item::ENTITY_TYPE,
				[ 'de' => 'Foo', 'en' => 'Foo' ],
				[],
				[ 'Q1', 'Q3', 'Q5' ],
			],
		];
	}

	/**
	 * @dataProvider labelConflictProvider
	 */
	public function testGetLabelConflicts(
		array $entities,
		$entityType,
		array $labels,
		array $aliases,
		array $expected
	) {
		$termIndex = $this->getTermIndex();
		$termIndex->clear();

		foreach ( $entities as $entity ) {
			$termIndex->saveTermsOfEntity( $entity );
		}

		//TODO: move this test case to LabelConflictFinderContractTester
		$matches = $termIndex->getLabelConflicts( $entityType, $labels, $aliases );
		$actual = $this->getEntityIdStrings( $matches );

		$this->assertArrayEquals( $expected, $actual, false, false );
	}

	/**
	 * @dataProvider labelConflictProvider
	 */
	public function testGetLabelConflicts_entitySourceBasedFederation(
		array $entities,
		$entityType,
		array $labels,
		array $aliases,
		array $expected
	) {
		$termIndex = $this->newTermSqlIndexForSourceBasedFederation();
		$termIndex->clear();

		foreach ( $entities as $entity ) {
			$termIndex->saveTermsOfEntity( $entity );
		}

		//TODO: move this test case to LabelConflictFinderContractTester
		$matches = $termIndex->getLabelConflicts( $entityType, $labels, $aliases );
		$actual = $this->getEntityIdStrings( $matches );

		$this->assertArrayEquals( $expected, $actual, false, false );
	}

	public function labelWithDescriptionConflictProvider() {
		$entities = $this->getTermConflictEntities();

		return [
			'by label, empty descriptions' => [
				$entities,
				Item::ENTITY_TYPE,
				[ 'de' => 'Foo' ],
				[],
				[],
			],
			'by label, mismatching description' => [
				$entities,
				Item::ENTITY_TYPE,
				[ 'de' => 'Foo' ],
				[ 'de' => 'XYZ' ],
				[],
			],
			'by label and description' => [
				$entities,
				Item::ENTITY_TYPE,
				[ 'de' => 'Foo' ],
				[ 'de' => 'Bar' ],
				[ 'Q1' ],
			],
			'by label and description, different label capitalization' => [
				$entities,
				Item::ENTITY_TYPE,
				[ 'de' => 'fOO' ],
				[ 'de' => 'Bar' ],
				[ 'Q1' ],
			],
			'by label and description, different description capitalization' => [
				$entities,
				Item::ENTITY_TYPE,
				[ 'de' => 'Foo' ],
				[ 'de' => 'bAR' ],
				[ 'Q1' ],
			],
			'two languages for label and description' => [
				$entities,
				Item::ENTITY_TYPE,
				[ 'de' => 'Foo', 'en' => 'Foo' ],
				[ 'de' => 'Bar', 'en' => 'Bar' ],
				[ 'Q1', 'Q3' ],
			],
			'same label and description, different language' => [
				$entities,
				Item::ENTITY_TYPE,
				[ 'en' => 'Foo' ],
				[ 'en' => 'Quux' ],
				[],
			],
		];
	}

	/**
	 * @dataProvider labelWithDescriptionConflictProvider
	 */
	public function testGetLabelWithDescriptionConflicts(
		array $entities,
		$entityType,
		array $labels,
		array $descriptions,
		array $expected
	) {
		$this->markTestSkippedOnMySql();

		$termIndex = $this->getTermIndex();
		$termIndex->clear();

		foreach ( $entities as $entity ) {
			$termIndex->saveTermsOfEntity( $entity );
		}

		// FIXME: This tests the LabelConflictFinder interface!
		$matches = $termIndex->getLabelWithDescriptionConflicts( $entityType, $labels, $descriptions );
		$actual = $this->getEntityIdStrings( $matches );

		$this->assertArrayEquals( $expected, $actual, false, false );
	}

	/**
	 * @dataProvider labelWithDescriptionConflictProvider
	 */
	public function testGetLabelWithDescriptionConflicts_entitySourceBasedFederation(
		array $entities,
		$entityType,
		array $labels,
		array $descriptions,
		array $expected
	) {
		$this->markTestSkippedOnMySql();

		$termIndex = $this->newTermSqlIndexForSourceBasedFederation();
		$termIndex->clear();

		foreach ( $entities as $entity ) {
			$termIndex->saveTermsOfEntity( $entity );
		}

		// FIXME: This tests the LabelConflictFinder interface!
		$matches = $termIndex->getLabelWithDescriptionConflicts( $entityType, $labels, $descriptions );
		$actual = $this->getEntityIdStrings( $matches );

		$this->assertArrayEquals( $expected, $actual, false, false );
	}

	protected function getEntityIdStrings( array $terms ) {
		return array_map( function( TermIndexEntry $term ) {
			$id = $term->getEntityId();
			return $id->getSerialization();
		}, $terms );
	}

	public function testGetTermsOfEntity() {
		$lookup = $this->getTermIndex();

		$item = new Item( new ItemId( 'Q568234314' ) );

		$item->setLabel( 'en', 'abc' );
		$item->setLabel( 'de', 'def' );
		$item->setLabel( 'nl', 'ghi' );
		$item->setDescription( 'en', 'testGetTermsOfEntity' );
		$item->setAliases( 'fr', [ 'o', '_', 'O' ] );

		$this->assertTrue( $lookup->saveTermsOfEntity( $item ) );

		$labelTerms = $lookup->getTermsOfEntity( $item->getId(), [ 'label' ] );
		$this->assertEquals( 3, count( $labelTerms ), "expected 3 labels" );

		$englishTerms = $lookup->getTermsOfEntity( $item->getId(), null, [ 'en' ] );
		$this->assertEquals( 2, count( $englishTerms ), "expected 2 English terms" );

		$germanLabelTerms = $lookup->getTermsOfEntity( $item->getId(), [ 'label' ], [ 'de' ] );
		$this->assertEquals( 1, count( $germanLabelTerms ), "expected 1 German label" );

		$noTerms = $lookup->getTermsOfEntity( $item->getId(), [ 'label' ], [] );
		$this->assertEmpty( $noTerms, "expected no labels" );

		$noTerms = $lookup->getTermsOfEntity( $item->getId(), [], [ 'de' ] );
		$this->assertEmpty( $noTerms, "expected no labels" );

		$terms = $lookup->getTermsOfEntity( $item->getId() );
		$this->assertEquals( 7, count( $terms ), "expected 7 terms for item" );

		// make list of strings for easy checking
		$term_keys = [];
		foreach ( $terms as $t ) {
			$term_keys[] = $t->getTermType() . '/' .  $t->getLanguage() . '/' . $t->getText();
		}

		$k = TermIndexEntry::TYPE_LABEL . '/en/abc';
		$this->assertContains( $k, $term_keys,
			"expected to find $k in terms for item" );

		$k = TermIndexEntry::TYPE_DESCRIPTION . '/en/testGetTermsOfEntity';
		$this->assertContains( $k, $term_keys,
			"expected to find $k in terms for item" );

		$k = TermIndexEntry::TYPE_ALIAS . '/fr/_';
		$this->assertContains( $k, $term_keys,
			"expected to find $k in terms for item" );
	}

	public function testGetTermsOfEntity_entitySourceBasedFederation() {
		$lookup = $this->newTermSqlIndexForSourceBasedFederation();

		$item = new Item( new ItemId( 'Q568234314' ) );

		$item->setLabel( 'en', 'abc' );
		$item->setLabel( 'de', 'def' );
		$item->setLabel( 'nl', 'ghi' );
		$item->setDescription( 'en', 'testGetTermsOfEntity' );
		$item->setAliases( 'fr', [ 'o', '_', 'O' ] );

		$this->assertTrue( $lookup->saveTermsOfEntity( $item ) );

		$labelTerms = $lookup->getTermsOfEntity( $item->getId(), [ 'label' ] );
		$this->assertEquals( 3, count( $labelTerms ), "expected 3 labels" );

		$englishTerms = $lookup->getTermsOfEntity( $item->getId(), null, [ 'en' ] );
		$this->assertEquals( 2, count( $englishTerms ), "expected 2 English terms" );

		$germanLabelTerms = $lookup->getTermsOfEntity( $item->getId(), [ 'label' ], [ 'de' ] );
		$this->assertEquals( 1, count( $germanLabelTerms ), "expected 1 German label" );

		$noTerms = $lookup->getTermsOfEntity( $item->getId(), [ 'label' ], [] );
		$this->assertEmpty( $noTerms, "expected no labels" );

		$noTerms = $lookup->getTermsOfEntity( $item->getId(), [], [ 'de' ] );
		$this->assertEmpty( $noTerms, "expected no labels" );

		$terms = $lookup->getTermsOfEntity( $item->getId() );
		$this->assertEquals( 7, count( $terms ), "expected 7 terms for item" );

		// make list of strings for easy checking
		$term_keys = [];
		foreach ( $terms as $t ) {
			$term_keys[] = $t->getTermType() . '/' .  $t->getLanguage() . '/' . $t->getText();
		}

		$k = TermIndexEntry::TYPE_LABEL . '/en/abc';
		$this->assertContains( $k, $term_keys,
			"expected to find $k in terms for item" );

		$k = TermIndexEntry::TYPE_DESCRIPTION . '/en/testGetTermsOfEntity';
		$this->assertContains( $k, $term_keys,
			"expected to find $k in terms for item" );

		$k = TermIndexEntry::TYPE_ALIAS . '/fr/_';
		$this->assertContains( $k, $term_keys,
			"expected to find $k in terms for item" );
	}

	public function testGetTermsOfEntities() {
		$lookup = $this->getTermIndex();

		$item1 = new Item( new ItemId( 'Q568234314' ) );

		$item1->setLabel( 'en', 'abc' );
		$item1->setLabel( 'de', 'def' );
		$item1->setLabel( 'nl', 'ghi' );
		$item1->setDescription( 'en', 'one description' );
		$item1->setAliases( 'fr', [ 'o', '_', 'O' ] );

		$item2 = new Item( new ItemId( 'Q87236423' ) );

		$item2->setLabel( 'en', 'xyz' );
		$item2->setLabel( 'de', 'uvw' );
		$item2->setLabel( 'nl', 'rst' );
		$item2->setDescription( 'en', 'another description' );
		$item2->setAliases( 'fr', [ 'X', '~', 'x' ] );

		$this->assertTrue( $lookup->saveTermsOfEntity( $item1 ) );
		$this->assertTrue( $lookup->saveTermsOfEntity( $item2 ) );

		$itemIds = [ $item1->getId(), $item2->getId() ];

		$labelTerms = $lookup->getTermsOfEntities( $itemIds, [ TermIndexEntry::TYPE_LABEL ] );
		$this->assertEquals( 6, count( $labelTerms ), "expected 3 labels" );

		$englishTerms = $lookup->getTermsOfEntities( $itemIds, null, [ 'en' ] );
		$this->assertEquals( 4, count( $englishTerms ), "expected 2 English terms" );

		$englishTerms = $lookup->getTermsOfEntities( [ $item1->getId() ], null, [ 'en' ] );
		$this->assertEquals( 2, count( $englishTerms ), "expected 2 English terms" );

		$germanLabelTerms = $lookup->getTermsOfEntities( $itemIds, [ TermIndexEntry::TYPE_LABEL ], [ 'de' ] );
		$this->assertEquals( 2, count( $germanLabelTerms ), "expected 1 German label" );

		$noTerms = $lookup->getTermsOfEntities( $itemIds, [ TermIndexEntry::TYPE_LABEL ], [] );
		$this->assertEmpty( $noTerms, "expected no labels" );

		$noTerms = $lookup->getTermsOfEntities( $itemIds, [], [ 'de' ] );
		$this->assertEmpty( $noTerms, "expected no labels" );

		$terms = $lookup->getTermsOfEntities( $itemIds );
		$this->assertEquals( 14, count( $terms ), "expected 7 terms for item" );

		// make list of strings for easy checking
		$term_keys = [];
		foreach ( $terms as $t ) {
			$term_keys[] = $t->getTermType() . '/' .  $t->getLanguage() . '/' . $t->getText();
		}

		$k = TermIndexEntry::TYPE_LABEL . '/en/abc';
		$this->assertContains( $k, $term_keys,
			"expected to find $k in terms for item" );

		$k = TermIndexEntry::TYPE_LABEL . '/en/xyz';
		$this->assertContains( $k, $term_keys,
			"expected to find $k in terms for item" );

		$k = TermIndexEntry::TYPE_DESCRIPTION . '/en/another description';
		$this->assertContains( $k, $term_keys,
			"expected to find $k in terms for item" );

		$k = TermIndexEntry::TYPE_ALIAS . '/fr/x';
		$this->assertContains( $k, $term_keys,
			"expected to find $k in terms for item" );
	}

	public function testGetTermsOfEntities_entitySourceBasedFederation() {
		$lookup = $this->newTermSqlIndexForSourceBasedFederation();

		$item1 = new Item( new ItemId( 'Q568234314' ) );

		$item1->setLabel( 'en', 'abc' );
		$item1->setLabel( 'de', 'def' );
		$item1->setLabel( 'nl', 'ghi' );
		$item1->setDescription( 'en', 'one description' );
		$item1->setAliases( 'fr', [ 'o', '_', 'O' ] );

		$item2 = new Item( new ItemId( 'Q87236423' ) );

		$item2->setLabel( 'en', 'xyz' );
		$item2->setLabel( 'de', 'uvw' );
		$item2->setLabel( 'nl', 'rst' );
		$item2->setDescription( 'en', 'another description' );
		$item2->setAliases( 'fr', [ 'X', '~', 'x' ] );

		$this->assertTrue( $lookup->saveTermsOfEntity( $item1 ) );
		$this->assertTrue( $lookup->saveTermsOfEntity( $item2 ) );

		$itemIds = [ $item1->getId(), $item2->getId() ];

		$labelTerms = $lookup->getTermsOfEntities( $itemIds, [ TermIndexEntry::TYPE_LABEL ] );
		$this->assertEquals( 6, count( $labelTerms ), "expected 3 labels" );

		$englishTerms = $lookup->getTermsOfEntities( $itemIds, null, [ 'en' ] );
		$this->assertEquals( 4, count( $englishTerms ), "expected 2 English terms" );

		$englishTerms = $lookup->getTermsOfEntities( [ $item1->getId() ], null, [ 'en' ] );
		$this->assertEquals( 2, count( $englishTerms ), "expected 2 English terms" );

		$germanLabelTerms = $lookup->getTermsOfEntities( $itemIds, [ TermIndexEntry::TYPE_LABEL ], [ 'de' ] );
		$this->assertEquals( 2, count( $germanLabelTerms ), "expected 1 German label" );

		$noTerms = $lookup->getTermsOfEntities( $itemIds, [ TermIndexEntry::TYPE_LABEL ], [] );
		$this->assertEmpty( $noTerms, "expected no labels" );

		$noTerms = $lookup->getTermsOfEntities( $itemIds, [], [ 'de' ] );
		$this->assertEmpty( $noTerms, "expected no labels" );

		$terms = $lookup->getTermsOfEntities( $itemIds );
		$this->assertEquals( 14, count( $terms ), "expected 7 terms for item" );

		// make list of strings for easy checking
		$term_keys = [];
		foreach ( $terms as $t ) {
			$term_keys[] = $t->getTermType() . '/' .  $t->getLanguage() . '/' . $t->getText();
		}

		$k = TermIndexEntry::TYPE_LABEL . '/en/abc';
		$this->assertContains( $k, $term_keys,
			"expected to find $k in terms for item" );

		$k = TermIndexEntry::TYPE_LABEL . '/en/xyz';
		$this->assertContains( $k, $term_keys,
			"expected to find $k in terms for item" );

		$k = TermIndexEntry::TYPE_DESCRIPTION . '/en/another description';
		$this->assertContains( $k, $term_keys,
			"expected to find $k in terms for item" );

		$k = TermIndexEntry::TYPE_ALIAS . '/fr/x';
		$this->assertContains( $k, $term_keys,
			"expected to find $k in terms for item" );
	}

	/**
	 * @param TermSqlIndex $termIndex
	 * @param string $text
	 * @param string|null $termType
	 * @param string|null $language
	 * @param string|null $entityType
	 */
	private function assertTermExists(
		TermSqlIndex $termIndex,
		$text,
		$termType = null,
		$language = null,
		$entityType = null
	) {
		$this->assertTrue(
			$this->termExists( $termIndex, $text, $termType, $language, $entityType ),
			"Term \"$text\" should exist"
		);
	}

	/**
	 * @param TermSqlIndex $termIndex
	 * @param string $text
	 */
	private function assertNotTermExists( TermSqlIndex $termIndex, $text ) {
		$this->assertFalse(
			$this->termExists( $termIndex, $text ),
			"Term \"$text\" should not exist"
		);
	}

	/**
	 * @param TermSqlIndex $termIndex
	 * @param string $text
	 * @param string|null $termType
	 * @param string|null $language
	 * @param string|null $entityType
	 *
	 * @return bool
	 */
	private function termExists(
		TermSqlIndex $termIndex,
		$text,
		$termType = null,
		$language = null,
		$entityType = null
	) {
		$criteria = [];
		$criteria['termText'] = $text;

		if ( $language !== null ) {
			$criteria['termLanguage'] = $language;
		}

		$matches = $termIndex->getMatchingTerms(
			[ new TermIndexSearchCriteria( $criteria ) ],
			$termType,
			$entityType
		);
		return !empty( $matches );
	}

	public function termProvider() {
		yield [ 'en', 'FoO', 'fOo', true ];
		yield [ 'ru', 'Берлин', 'берлин', true ];
		yield [ 'en', 'FoO', 'bar', false ];
		yield [ 'ru', 'Берлин', 'бе55585рлин', false ];
	}

	/**
	 * @dataProvider termProvider
	 */
	public function testGetMatchingTerms_useSearchFields( $languageCode, $termText, $searchText, $matches ) {
		$termIndex = $this->getTermIndex();

		$item = new Item( new ItemId( 'Q42' ) );
		$item->setLabel( $languageCode, $termText );

		$termIndex->saveTermsOfEntity( $item );

		$term = new TermIndexSearchCriteria( [ 'termLanguage' => $languageCode, 'termText' => $searchText ] );

		//FIXME: test with arrays for term types and entity types!
		$obtainedTerms = $termIndex->getMatchingTerms(
			[ $term ],
			TermIndexEntry::TYPE_LABEL,
			Item::ENTITY_TYPE,
			[ 'caseSensitive' => false ]
		);

		$this->assertEquals( $matches ? 1 : 0, count( $obtainedTerms ) );

		if ( $matches ) {
			$obtainedTerm = array_shift( $obtainedTerms );

			$this->assertEquals( $termText, $obtainedTerm->getText() );
		}
	}

	/**
	 * @dataProvider termProvider
	 */
	public function testGetMatchingTerms_useSearchFields_entitySourceBasedFederation( $languageCode, $termText, $searchText, $matches ) {
		$termIndex = $this->newTermSqlIndexForSourceBasedFederation();

		$item = new Item( new ItemId( 'Q42' ) );
		$item->setLabel( $languageCode, $termText );

		$termIndex->saveTermsOfEntity( $item );

		$term = new TermIndexSearchCriteria( [ 'termLanguage' => $languageCode, 'termText' => $searchText ] );

		//FIXME: test with arrays for term types and entity types!
		$obtainedTerms = $termIndex->getMatchingTerms(
			[ $term ],
			TermIndexEntry::TYPE_LABEL,
			Item::ENTITY_TYPE,
			[ 'caseSensitive' => false ]
		);

		$this->assertEquals( $matches ? 1 : 0, count( $obtainedTerms ) );

		if ( $matches ) {
			$obtainedTerm = array_shift( $obtainedTerms );

			$this->assertEquals( $termText, $obtainedTerm->getText() );
		}
	}

	public function provideTermsForDoNotUseSearchFieldsTests() {
		yield [ 'en', 'Foo', 'Foo', true ];
		yield [ 'en', 'Foo', 'foo', false ];
		yield [ 'ru', 'Берлин', 'Берлин', true ];
		yield [ 'ru', 'Берлин', 'берлин', false ];
	}

	/**
	 * @dataProvider provideTermsForDoNotUseSearchFieldsTests
	 */
	public function testGetMatchingTerms_doNotUseSearchFields( $languageCode, $termText, $searchText, $matches ) {
		$termIndex = $this->getTermIndex();
		$termIndex->setUseSearchFields( false );

		$item = new Item( new ItemId( 'Q42' ) );
		$item->setLabel( $languageCode, $termText );

		$termIndex->saveTermsOfEntity( $item );

		$term = new TermIndexSearchCriteria( [ 'termLanguage' => $languageCode, 'termText' => $searchText ] );

		//FIXME: test with arrays for term types and entity types!
		$obtainedTerms = $termIndex->getMatchingTerms(
			[ $term ],
			TermIndexEntry::TYPE_LABEL,
			Item::ENTITY_TYPE,
			[ 'caseSensitive' => false ]
		);

		$this->assertEquals( $matches ? 1 : 0, count( $obtainedTerms ) );

		if ( $matches ) {
			$obtainedTerm = array_shift( $obtainedTerms );

			$this->assertEquals( $termText, $obtainedTerm->getText() );
		}
	}

	/**
	 * @dataProvider provideTermsForDoNotUseSearchFieldsTests
	 */
	public function testGetMatchingTerms_doNotUseSearchFields_entitySourceBasedFederation( $languageCode, $termText, $searchText, $matches ) {
		$termIndex = $this->newTermSqlIndexForSourceBasedFederation();
		$termIndex->setUseSearchFields( false );

		$item = new Item( new ItemId( 'Q42' ) );
		$item->setLabel( $languageCode, $termText );

		$termIndex->saveTermsOfEntity( $item );

		$term = new TermIndexSearchCriteria( [ 'termLanguage' => $languageCode, 'termText' => $searchText ] );

		//FIXME: test with arrays for term types and entity types!
		$obtainedTerms = $termIndex->getMatchingTerms(
			[ $term ],
			TermIndexEntry::TYPE_LABEL,
			Item::ENTITY_TYPE,
			[ 'caseSensitive' => false ]
		);

		$this->assertEquals( $matches ? 1 : 0, count( $obtainedTerms ) );

		if ( $matches ) {
			$obtainedTerm = array_shift( $obtainedTerms );

			$this->assertEquals( $termText, $obtainedTerm->getText() );
		}
	}

	/**
	 * Returns a fake term index configured for the given repository which uses the local database.
	 *
	 * @param string $repository
	 * @return TermSqlIndex
	 */
	private function getTermIndexForRepository( $repository ) {
		return new TermSqlIndex(
			new StringNormalizer(),
			new EntityIdComposer( [
				'item' => function( $repositoryName, $uniquePart ) {
					return ItemId::newFromRepositoryAndNumber( $repositoryName, $uniquePart );
				},
				'property' => function( $repositoryName, $uniquePart ) {
					return PropertyId::newFromRepositoryAndNumber( $repositoryName, $uniquePart );
				},
			] ),
			new PrefixMappingEntityIdParser( [ '' => $repository ], new BasicEntityIdParser() ),
			new UnusableEntitySource(),
			DataAccessSettingsFactory::repositoryPrefixBasedFederation(),
			false,
			$repository
		);
	}

	public function testGivenForeignRepositoryName_getMatchingTermsReturnsEntityIdWithTheRepositoryPrefix() {
		$localTermIndex = $this->getTermIndex();

		$item = new Item( new ItemId( 'Q300' ) );
		$item->setLabel( 'en', 'Foo' );

		$localTermIndex->saveTermsOfEntity( $item );

		$fooTermIndex = $this->getTermIndexForRepository( 'foo' );

		$results = $fooTermIndex->getMatchingTerms( [ new TermIndexSearchCriteria( [ 'termText' => 'Foo' ] ) ] );

		$this->assertCount( 1, $results );

		$termIndexEntry = $results[0];

		$this->assertTrue( $termIndexEntry->getEntityId()->equals( new ItemId( 'foo:Q300' ) ) );
		$this->assertEquals( 'Foo', $termIndexEntry->getText() );
	}

	public function labelWithDescriptionConflictProvider_CaseSensitive() {
		foreach ( $this->labelWithDescriptionConflictProvider() as $testCase => $arguments ) {
			list( $entities, $entityType, $labels, $descriptions, $expected ) = $arguments;
			if ( preg_match( '/different .* capitalization/', $testCase ) ) {
				$expected = [];
			}
			yield $testCase => [ $entities, $entityType, $labels, $descriptions, $expected ];
		}
	}

	/**
	 * @dataProvider labelWithDescriptionConflictProvider_CaseSensitive
	 */
	public function testGetLabelWithDescriptionConflicts_NoUseSearchFields(
		array $entities,
		$entityType,
		array $labels,
		array $descriptions,
		array $expected
	) {
		$this->markTestSkippedOnMySql();

		// TODO this is copied from TermIndexTestCase, is there a nicer way to do this?
		$termIndex = $this->getTermIndex();
		$termIndex->setUseSearchFields( false );
		$termIndex->clear();

		foreach ( $entities as $entity ) {
			$termIndex->saveTermsOfEntity( $entity );
		}

		$matches = $termIndex->getLabelWithDescriptionConflicts( $entityType, $labels, $descriptions );
		$actual = $this->getEntityIdStrings( $matches );

		$this->assertArrayEquals( $expected, $actual, false, false );
	}

	/**
	 * @dataProvider labelWithDescriptionConflictProvider_CaseSensitive
	 */
	public function testGetLabelWithDescriptionConflicts_NoUseSearchFields_entitySourceBasedFederation(
		array $entities,
		$entityType,
		array $labels,
		array $descriptions,
		array $expected
	) {
		$this->markTestSkippedOnMySql();

		$termIndex = $this->newTermSqlIndexForSourceBasedFederation();
		$termIndex->setUseSearchFields( false );
		$termIndex->clear();

		foreach ( $entities as $entity ) {
			$termIndex->saveTermsOfEntity( $entity );
		}

		$matches = $termIndex->getLabelWithDescriptionConflicts( $entityType, $labels, $descriptions );
		$actual = $this->getEntityIdStrings( $matches );

		$this->assertArrayEquals( $expected, $actual, false, false );
	}

	public function getMatchingTermsOptionsProvider() {
		$labels = [
			'en' => new Term( 'en', 'Foo' ),
			'de' => new Term( 'de', 'Fuh' ),
		];

		$descriptions = [
			'en' => new Term( 'en', 'Bar' ),
			'de' => new Term( 'de', 'Bär' ),
		];

		$fingerprint = new Fingerprint(
			new TermList( $labels ),
			new TermList( $descriptions ),
			new AliasGroupList()
		);

		$labelFooEn = new TermIndexSearchCriteria( [
			'termType' => TermIndexEntry::TYPE_LABEL,
			'termLanguage' => 'en',
			'termText' => 'Foo',
		] );
		$descriptionBarEn = new TermIndexSearchCriteria( [
			'termType' => TermIndexEntry::TYPE_DESCRIPTION,
			'termLanguage' => 'en',
			'termText' => 'Bar',
		] );

		return [
			'no options' => [
				$fingerprint,
				[ $labelFooEn ],
				[],
				[ $labelFooEn ],
			],
			'LIMIT options' => [
				$fingerprint,
				[ $labelFooEn, $descriptionBarEn ],
				[ 'LIMIT' => 1 ],
				// This is not really well defined. Could be either of the two.
				// So use null to show we want something but don't know what it is
				[ null ],
			]
		];
	}

	/**
	 * @dataProvider getMatchingTermsOptionsProvider
	 *
	 * @param Fingerprint $fingerprint
	 * @param TermIndexEntry[] $queryTerms
	 * @param array $options
	 * @param TermIndexEntry[] $expected
	 */
	public function testGetMatchingTerms_options( Fingerprint $fingerprint, array $queryTerms, array $options, array $expected ) {
		$termIndex = $this->getTermIndex();
		$termIndex->clear();

		$item = new Item( new ItemId( 'Q42' ) );
		$item->setFingerprint( $fingerprint );

		$termIndex->saveTermsOfEntity( $item );

		$actual = $termIndex->getMatchingTerms( $queryTerms, null, null, $options );

		$this->assertSameSize( $expected, $actual );

		foreach ( $expected as $key => $expectedTerm ) {
			$this->assertArrayHasKey( $key, $actual );
			if ( $expectedTerm instanceof TermIndexEntry ) {
				$actualTerm = $actual[$key];
				$this->assertEquals( $expectedTerm->getTermType(), $actualTerm->getTermType(), 'termType' );
				$this->assertEquals( $expectedTerm->getLanguage(), $actualTerm->getLanguage(), 'termLanguage' );
				$this->assertEquals( $expectedTerm->getText(), $actualTerm->getText(), 'termText' );
			}
		}
	}

	/**
	 * @dataProvider getMatchingTermsOptionsProvider
	 *
	 * @param Fingerprint $fingerprint
	 * @param TermIndexEntry[] $queryTerms
	 * @param array $options
	 * @param TermIndexEntry[] $expected
	 */
	public function testGetMatchingTerms_options_entitySourceBasedFederation(
		Fingerprint $fingerprint,
		array $queryTerms,
		array $options,
		array $expected
	) {
		$termIndex = $this->newTermSqlIndexForSourceBasedFederation();
		$termIndex->clear();

		$item = new Item( new ItemId( 'Q42' ) );
		$item->setFingerprint( $fingerprint );

		$termIndex->saveTermsOfEntity( $item );

		$actual = $termIndex->getMatchingTerms( $queryTerms, null, null, $options );

		$this->assertSameSize( $expected, $actual );

		foreach ( $expected as $key => $expectedTerm ) {
			$this->assertArrayHasKey( $key, $actual );
			if ( $expectedTerm instanceof TermIndexEntry ) {
				$actualTerm = $actual[$key];
				$this->assertEquals( $expectedTerm->getTermType(), $actualTerm->getTermType(), 'termType' );
				$this->assertEquals( $expectedTerm->getLanguage(), $actualTerm->getLanguage(), 'termLanguage' );
				$this->assertEquals( $expectedTerm->getText(), $actualTerm->getText(), 'termText' );
			}
		}
	}

	public function provideGetSearchKey() {
		return [
			'basic' => [
				'foo', // raw
				'foo', // normalized
			],

			'trailing newline' => [
				"foo \n",
				'foo',
			],

			'whitespace' => [
				'  foo  ', // raw
				'foo', // normalized
			],

			'lower case of non-ascii character' => [
				'ÄpFEl', // raw
				'äpfel', // normalized
			],

			'lower case of decomposed character' => [
				"A\xCC\x88pfel", // raw
				'äpfel', // normalized
			],

			'lower case of cyrillic character' => [
				'Берлин', // raw
				'берлин', // normalized
			],

			'lower case of greek character' => [
				'Τάχιστη', // raw
				'τάχιστη', // normalized
			],

			'nasty unicode whitespace' => [
				// ZWNJ: U+200C \xE2\x80\x8C
				// RTLM: U+200F \xE2\x80\x8F
				// PSEP: U+2029 \xE2\x80\xA9
				"\xE2\x80\x8F\xE2\x80\x8Cfoo\xE2\x80\x8Cbar\xE2\x80\xA9", // raw
				"foo bar", // normalized
			],
		];
	}

	/**
	 * @dataProvider provideGetSearchKey
	 */
	public function testGetSearchKey( $raw, $normalized ) {
		$index = $this->getTermIndex();

		$key = $index->getSearchKey( $raw );
		$this->assertEquals( $normalized, $key );
	}

	/**
	 * @dataProvider provideGetSearchKey
	 */
	public function testGetSearchKey_entitySourceBasedFederation( $raw, $normalized ) {
		$index = $this->newTermSqlIndexForSourceBasedFederation();

		$key = $index->getSearchKey( $raw );
		$this->assertEquals( $normalized, $key );
	}

	/**
	 * @dataProvider getEntityTermsProvider
	 */
	public function testGetEntityTerms( $expectedTerms, EntityDocument $entity ) {
		$termIndex = $this->getTermIndex();
		$wikibaseTerms = $termIndex->getEntityTerms( $entity );

		$this->assertEquals( $expectedTerms, $wikibaseTerms );
	}

	/**
	 * @dataProvider getEntityTermsProvider
	 */
	public function testGetEntityTerms_entitySourceBasedFederation( $expectedTerms, EntityDocument $entity ) {
		$termIndex = $this->newTermSqlIndexForSourceBasedFederation();
		$wikibaseTerms = $termIndex->getEntityTerms( $entity );

		$this->assertEquals( $expectedTerms, $wikibaseTerms );
	}

	/**
	 * @dataProvider getEntityTermsProvider
	 */
	public function testGetEntityTerms_NoUseSearchFields( $expectedTerms, EntityDocument $entity ) {
		$termIndex = $this->getTermIndex();
		$termIndex->setUseSearchFields( false );
		$wikibaseTerms = $termIndex->getEntityTerms( $entity );

		$this->assertEquals( $expectedTerms, $wikibaseTerms );
	}

	/**
	 * @dataProvider getEntityTermsProvider
	 */
	public function testGetEntityTerms_NoUseSearchFields_entitySourceBasedFederation( $expectedTerms, EntityDocument $entity ) {
		$termIndex = $this->newTermSqlIndexForSourceBasedFederation();
		$termIndex->setUseSearchFields( false );
		$wikibaseTerms = $termIndex->getEntityTerms( $entity );

		$this->assertEquals( $expectedTerms, $wikibaseTerms );
	}

	public function getEntityTermsProvider() {
		$id = new ItemId( 'Q999' );
		$item = new Item( $id );

		$item->setLabel( 'en', 'kittens!!!:)' );
		$item->setDescription( 'es', 'es un gato!' );
		$item->setAliases( 'en', [ 'kitten-alias' ] );

		$expectedTerms = [
			new TermIndexEntry( [
				'entityId' => new ItemId( 'Q999' ),
				'termText' => 'es un gato!',
				'termLanguage' => 'es',
				'termType' => 'description'
			] ),
			new TermIndexEntry( [
				'entityId' => new ItemId( 'Q999' ),
				'termText' => 'kittens!!!:)',
				'termLanguage' => 'en',
				'termType' => 'label'
			] ),
			new TermIndexEntry( [
				'entityId' => new ItemId( 'Q999' ),
				'termText' => 'kitten-alias',
				'termLanguage' => 'en',
				'termType' => 'alias'
			] )
		];

		$entityWithoutTerms = $this->getMock( EntityDocument::class );
		$entityWithoutTerms->expects( $this->any() )
			->method( 'getId' )
			->will( $this->returnValue( $id ) );

		return [
			[ $expectedTerms, $item ],
			[ [], new Item( $id ) ],
			[ [], $entityWithoutTerms ]
		];
	}

	/**
	 * @see http://bugs.mysql.com/bug.php?id=10327
	 * @see EditEntityTest::markTestSkippedOnMySql
	 */
	private function markTestSkippedOnMySql() {
		if ( $this->db->getType() === 'mysql' ) {
			$this->markTestSkipped( 'MySQL doesn\'t support self-joins on temporary tables' );
		}
	}

	public function testGivenForeignRepositoryName_getTermsOfEntitiesReturnsEntityIdsWithRepositoryPrefix() {
		$localTermIndex = $this->getTermIndex();

		$item = new Item( new ItemId( 'Q300' ) );
		$item->setLabel( 'en', 'Foo' );

		$localTermIndex->saveTermsOfEntity( $item );

		$fooTermIndex = $this->getTermIndexForRepository( 'foo' );

		$results = $fooTermIndex->getTermsOfEntities( [ new ItemId( 'foo:Q300' ) ] );

		$this->assertCount( 1, $results );

		$termIndexEntry = $results[0];

		$this->assertTrue( $termIndexEntry->getEntityId()->equals( new ItemId( 'foo:Q300' ) ) );
		$this->assertEquals( 'Foo', $termIndexEntry->getText() );
	}

	public function testGivenEntityIdFromAnotherRepository_getTermsOfEntitiesThrowsException() {
		$fooTermIndex = $this->getTermIndexForRepository( 'foo' );

		$this->setExpectedException( MWException::class );

		$fooTermIndex->getTermsOfEntities( [ new ItemId( 'Q300' ) ] );
	}

	public function testGivenEntityIdFromAnotherRepository_getTermsOfEntityThrowsException() {
		$fooTermIndex = $this->getTermIndexForRepository( 'foo' );

		$this->setExpectedException( MWException::class );

		$fooTermIndex->getTermsOfEntity( new ItemId( 'Q300' ) );
	}

	public function testGivenEntityFromAnotherRepository_getEntityTermsThrowsException() {
		$fooTermIndex = $this->getTermIndexForRepository( 'foo' );

		$this->setExpectedException( MWException::class );

		$fooTermIndex->getEntityTerms( new Item( new ItemId( 'Q300' ) ) );
	}

	public function testGivenEntityFromAnotherRepository_saveTermsOfEntityThrowsException() {
		$fooTermIndex = $this->getTermIndexForRepository( 'foo' );

		$item = new Item( new ItemId( 'Q300' ) );
		$item->setLabel( 'en', 'Foo' );

		$this->setExpectedException( MWException::class );

		$fooTermIndex->saveTermsOfEntity( $item );
	}

	public function testGivenEntityFromAnotherRepository_deleteTermsOfEntityThrowsException() {
		$fooTermIndex = $this->getTermIndexForRepository( 'foo' );

		$this->setExpectedException( MWException::class );

		$fooTermIndex->deleteTermsOfEntity( new ItemId( 'Q300' ) );
	}

	public function testGivenEntityIdFromAnotherSource_getTermsOfEntitiesThrowsException() {
		$fooTermIndex = $this->getTermSqlIndexForSourceOf( 'item' );

		$this->setExpectedException( MWException::class );

		$fooTermIndex->getTermsOfEntities( [ new PropertyId( 'P300' ) ] );
	}

	public function testGivenEntityIdFromAnotherSource_getTermsOfEntityThrowsException() {
		$fooTermIndex = $this->getTermSqlIndexForSourceOf( 'item' );

		$this->setExpectedException( MWException::class );

		$fooTermIndex->getTermsOfEntity( new PropertyId( 'P300' ) );
	}

	public function testGivenEntityFromAnotherSource_getEntityTermsThrowsException() {
		$fooTermIndex = $this->getTermSqlIndexForSourceOf( 'item' );

		$this->setExpectedException( MWException::class );

		$fooTermIndex->getEntityTerms( new Property( new PropertyId( 'P300' ), null, 'string' ) );
	}

	public function testGivenEntityFromAnotherSource_saveTermsOfEntityThrowsException() {
		$fooTermIndex = $this->getTermSqlIndexForSourceOf( 'item' );

		$property = new Property( new PropertyId( 'P300' ), null, 'string' );
		$property->setLabel( 'en', 'Foo' );

		$this->setExpectedException( MWException::class );

		$fooTermIndex->saveTermsOfEntity( $property );
	}

	public function testGivenEntityFromAnotherSource_deleteTermsOfEntityThrowsException() {
		$fooTermIndex = $this->getTermSqlIndexForSourceOf( 'item' );

		$this->setExpectedException( MWException::class );

		$fooTermIndex->deleteTermsOfEntity( new PropertyId( 'P300' ) );
	}

	public function testGivenEntityFromAnotherSource_getLabelConflictsThrowsException() {
		$fooTermIndex = $this->getTermSqlIndexForSourceOf( 'item' );

		$this->setExpectedException( MWException::class );

		$fooTermIndex->getLabelConflicts( 'property', [ 'en' => 'some irrelevant label' ] );
	}

	public function testGivenEntityFromAnotherSource_getLabelWithDescriptionConflictsThrowsException() {
		$fooTermIndex = $this->getTermSqlIndexForSourceOf( 'item' );

		$this->setExpectedException( MWException::class );

		$fooTermIndex->getLabelWithDescriptionConflicts( 'property', [ 'en' => 'some irrelevant label' ],  [ 'en' => 'random description' ] );
	}

	private function getTermSqlIndexForSourceOf( $entityType ) {
		$irrelevantNamespaceId = 100;

		return new TermSqlIndex(
			new StringNormalizer(),
			new EntityIdComposer( [
				'item' => function ( $repositoryName, $uniquePart ) {
					return ItemId::newFromRepositoryAndNumber( $repositoryName, $uniquePart );
				},
				'property' => function ( $repositoryName, $uniquePart ) {
					return PropertyId::newFromRepositoryAndNumber( $repositoryName, $uniquePart );
				},
			] ),
			new BasicEntityIdParser(),
			new EntitySource(
				'testsource',
				false,
				[ $entityType => [ 'namespaceId' => $irrelevantNamespaceId, 'slot' => 'main' ] ],
				'',
				'',
				'',
				''
			),
			DataAccessSettingsFactory::entitySourceBasedFederation(),
			false,
			''
		);
	}

	public function testInsertTerms_duplicate() {
		$item = new Item( new ItemId( 'Q1112362' ) );
		$termEs = new TermIndexEntry( [
			'entityId' => $item->getId(),
			'termText' => 'Spanish',
			'termLanguage' => 'es',
			'termType' => 'description'
		] );
		$termDe = new TermIndexEntry( [
			'entityId' => $item->getId(),
			'termText' => 'German',
			'termLanguage' => 'de',
			'termType' => 'description'
		] );

		$termIndex = $this->getTermIndex();
		/** @var TermSqlIndex $termIndex */
		$termIndex = TestingAccessWrapper::newFromObject( $termIndex );

		// TODO: this is testing internals of the class
		$this->assertTrue(
			$termIndex->insertTerms(
				$item,
				[ $termEs, $termDe, $termEs ],
				$termIndex->getConnection( DB_MASTER )
			)
		);

		$rowCount = $this->db->selectRowCount(
			'wb_terms',
			null,
			[ 'term_full_entity_id' => 'Q1112362', 'term_entity_type' => 'item' ],
			__METHOD__
		);

		$this->assertSame( 2, $rowCount );
	}

	public function testInsertTerms_duplicate_entitySourceBasedFederation() {
		$item = new Item( new ItemId( 'Q1112362' ) );
		$termEs = new TermIndexEntry( [
			'entityId' => $item->getId(),
			'termText' => 'Spanish',
			'termLanguage' => 'es',
			'termType' => 'description'
		] );
		$termDe = new TermIndexEntry( [
			'entityId' => $item->getId(),
			'termText' => 'German',
			'termLanguage' => 'de',
			'termType' => 'description'
		] );

		$termIndex = $this->newTermSqlIndexForSourceBasedFederation();
		/** @var TermSqlIndex $termIndex */
		$termIndex = TestingAccessWrapper::newFromObject( $termIndex );

		// TODO: this is testing internals of the class
		$this->assertTrue(
			$termIndex->insertTerms(
				$item,
				[ $termEs, $termDe, $termEs ],
				$termIndex->getConnection( DB_MASTER )
			)
		);

		$rowCount = $this->db->selectRowCount(
			'wb_terms',
			null,
			[ 'term_full_entity_id' => 'Q1112362', 'term_entity_type' => 'item' ],
			__METHOD__
		);

		$this->assertSame( 2, $rowCount );
	}

	/**
	 * @dataProvider provideForceWriteSearchFields
	 */
	public function testInsertTerms_NoUseSearchFields( $forceWriteSearchFields ) {
		$item = new Item( new ItemId( 'Q1112362' ) );
		$termDe = new TermIndexEntry( [
			'entityId' => $item->getId(),
			'termText' => 'German',
			'termLanguage' => 'de',
			'termType' => 'description'
		] );

		$termIndex = $this->getTermIndex();
		$termIndex->setUseSearchFields( false );
		$termIndex->setForceWriteSearchFields( $forceWriteSearchFields );
		/** @var TermSqlIndex $termIndex */
		$termIndex = TestingAccessWrapper::newFromObject( $termIndex );

		$this->assertTrue(
			$termIndex->insertTerms(
				$item,
				[ $termDe ],
				$termIndex->getConnection( DB_MASTER )
			)
		);

		$result = $this->db->selectField(
			'wb_terms',
			'term_search_key',
			[ 'term_full_entity_id' => 'Q1112362', 'term_entity_type' => 'item' ],
			__METHOD__
		);

		$expected = $forceWriteSearchFields ? 'german' : '';
		$this->assertSame( $expected, $result );
	}

	/**
	 * @dataProvider provideForceWriteSearchFields
	 */
	public function testInsertTerms_NoUseSearchFields_entitySourceBasedFederation( $forceWriteSearchFields ) {
		$item = new Item( new ItemId( 'Q1112362' ) );
		$termDe = new TermIndexEntry( [
			'entityId' => $item->getId(),
			'termText' => 'German',
			'termLanguage' => 'de',
			'termType' => 'description'
		] );

		$termIndex = $this->newTermSqlIndexForSourceBasedFederation();
		$termIndex->setUseSearchFields( false );
		$termIndex->setForceWriteSearchFields( $forceWriteSearchFields );
		/** @var TermSqlIndex $termIndex */
		$termIndex = TestingAccessWrapper::newFromObject( $termIndex );

		$this->assertTrue(
			$termIndex->insertTerms(
				$item,
				[ $termDe ],
				$termIndex->getConnection( DB_MASTER )
			)
		);

		$result = $this->db->selectField(
			'wb_terms',
			'term_search_key',
			[ 'term_full_entity_id' => 'Q1112362', 'term_entity_type' => 'item' ],
			__METHOD__
		);

		$expected = $forceWriteSearchFields ? 'german' : '';
		$this->assertSame( $expected, $result );
	}

	public function provideForceWriteSearchFields() {
		return [
			'don’t force writing search fields' => [ true ],
			'force writing search fields' => [ false ],
		];
	}

}
