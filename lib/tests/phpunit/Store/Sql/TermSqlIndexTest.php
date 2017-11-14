<?php

namespace Wikibase\Lib\Tests\Store\Sql;

use MWException;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\EntityId\PrefixMappingEntityIdParser;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\EntityIdComposer;
use Wikibase\Lib\Store\TermIndexSearchCriteria;
use Wikibase\Lib\Tests\Store\TermIndexTest;
use Wikibase\StringNormalizer;
use Wikibase\TermIndexEntry;
use Wikibase\Lib\Store\Sql\TermSqlIndex;
use Wikibase\WikibaseSettings;
use Wikimedia\Assert\ParameterAssertionException;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers Wikibase\Lib\Store\Sql\TermSqlIndex
 *
 * @group Wikibase
 * @group WikibaseStore
 * @group Database
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Thiemo Kreuz
 */
class TermSqlIndexTest extends TermIndexTest {

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
			false,
			$repositoryName
		);
	}

	/**
	 * @return TermSqlIndex
	 */
	public function getTermIndex() {
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
			new BasicEntityIdParser()
		);
	}

	public function termProvider() {
		return [
			[ 'en', 'FoO', 'fOo', true ],
			[ 'ru', 'Берлин', 'берлин', true ],
			[ 'en', 'FoO', 'bar', false ],
			[ 'ru', 'Берлин', 'бе55585рлин', false ],
		];
	}

	/**
	 * @dataProvider termProvider
	 */
	public function testGetMatchingTerms2( $languageCode, $termText, $searchText, $matches ) {
		$termIndex = $this->getTermIndex();
		$termIndex->clear();

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
	public function testGetMatchingTerms2_withFullEntityId(
		$languageCode,
		$termText,
		$searchText,
		$matches
	) {
		$termIndex = $this->getTermIndex();
		$termIndex->clear();
		$termIndex->setReadFullEntityIdColumn( true );

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

	public function testGivenForeignRepositoryName_getMatchingTermsReturnsEntityIdWithTheRepositoryPrefixFullEntityId() {
		$localTermIndex = $this->getTermIndex();
		$localTermIndex->setReadFullEntityIdColumn( true );

		$item = new Item( new ItemId( 'Q300' ) );
		$item->setLabel( 'en', 'Foo' );

		$localTermIndex->saveTermsOfEntity( $item );

		$fooTermIndex = $this->getTermIndexForRepository( 'foo' );
		$fooTermIndex->setReadFullEntityIdColumn( true );

		$results = $fooTermIndex->getMatchingTerms( [ new TermIndexSearchCriteria( [ 'termText' => 'Foo' ] ) ] );

		$this->assertCount( 1, $results );

		$termIndexEntry = $results[0];

		$this->assertTrue( $termIndexEntry->getEntityId()->equals( new ItemId( 'foo:Q300' ) ) );
		$this->assertEquals( 'Foo', $termIndexEntry->getText() );
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

		parent::testGetLabelWithDescriptionConflicts( $entities, $entityType, $labels, $descriptions, $expected );
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
	public function testGetMatchingTerms_options_withFullEntityId(
		Fingerprint $fingerprint,
		array $queryTerms,
		array $options,
		array $expected
	) {
		$termIndex = $this->getTermIndex();
		$termIndex->clear();
		$termIndex->setReadFullEntityIdColumn( true );

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
	public function testGetSearchKey_withFullEntityId( $raw, $normalized ) {
		$index = $this->getTermIndex();
		$index->setReadFullEntityIdColumn( true );

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
	public function testGetEntityTerms_withFullEntityId( $expectedTerms, EntityDocument $entity ) {
		$termIndex = $this->getTermIndex();
		$termIndex->setReadFullEntityIdColumn( true );
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

	public function testSaveTermsOfEntity_withoutFullEntityId() {
		$item = new Item( new ItemId( 'Q11116325' ) );
		$item->setLabel( 'en', 'kitten-Q11116325' );

		$termIndex = new TermSqlIndex(
			new StringNormalizer(),
			new EntityIdComposer( [
				'item' => function( $repositoryName, $uniquePart ) {
					return new ItemId( 'Q' . $uniquePart );
				},
				'property' => function( $repositoryName, $uniquePart ) {
					return new PropertyId( 'P' . $uniquePart );
				},
			] ),
			new BasicEntityIdParser(),
			false,
			'',
			false
		);

		$result = $termIndex->saveTermsOfEntity( $item );
		$this->assertTrue( $result );

		$row = $this->db->selectRow(
			'wb_terms',
			[ 'term_entity_id', 'term_entity_type', 'term_full_entity_id' ],
			[ 'term_entity_id' => '11116325', 'term_entity_type' => 'item' ],
			__METHOD__
		);

		$this->assertNull( $row->term_full_entity_id );
	}

	public function testSaveTermsOfEntity_withFullEntityId() {
		$item = new Item( new ItemId( 'Q1112362' ) );
		$item->setLabel( 'en', 'kitten-Q1112362' );

		$termIndex = $this->getTermIndex();

		$result = $termIndex->saveTermsOfEntity( $item );
		$this->assertTrue( $result );

		$row = $this->db->selectRow(
			'wb_terms',
			[ 'term_entity_id', 'term_entity_type', 'term_full_entity_id' ],
			[ 'term_entity_id' => '1112362', 'term_entity_type' => 'item' ],
			__METHOD__
		);

		$this->assertSame( 'Q1112362', $row->term_full_entity_id );
	}

	public function testSaveTermsOfEntity_withReadFullEntityId() {
		$item = new Item( new ItemId( 'Q1112362' ) );
		$item->setLabel( 'en', 'kitten-Q1112362' );

		$termIndex = $this->getTermIndex();
		$termIndex->setReadFullEntityIdColumn( true );

		$result = $termIndex->saveTermsOfEntity( $item );
		$this->assertTrue( $result );

		$row = $this->db->selectRow(
			'wb_terms',
			[ 'term_entity_id', 'term_entity_type', 'term_full_entity_id' ],
			[ 'term_entity_id' => '1112362', 'term_entity_type' => 'item' ],
			__METHOD__
		);

		$this->assertSame( 'Q1112362', $row->term_full_entity_id );
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
		$termIndex->setReadFullEntityIdColumn( true );
		/** @var TermSqlIndex $termIndex */
		$termIndex = TestingAccessWrapper::newFromObject( $termIndex );

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
			[ 'term_entity_id' => '1112362', 'term_entity_type' => 'item' ],
			__METHOD__
		);

		$this->assertSame( 2, $rowCount );
	}

	public function testDeleteTermsForEntity_withReadFullEntityId() {
		$index = $this->getTermIndex();
		$index->clear();
		$index->setReadFullEntityIdColumn( true );

		$item = new Item( new ItemId( 'Q10' ) );
		$item->setLabel( 'en', 'abc' );

		$index->saveTermsOfEntity( $item );

		$searchCriteria = [ new TermIndexSearchCriteria( [ 'termText' => 'abc' ] ) ];

		$matches = $index->getMatchingTerms( $searchCriteria );

		$this->assertNotEmpty( $matches );

		$index->deleteTermsOfEntity( $item->getId() );

		$matches = $index->getMatchingTerms( $searchCriteria );

		$this->assertEmpty( $matches );
	}

}
