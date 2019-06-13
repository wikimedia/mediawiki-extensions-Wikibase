<?php

namespace Wikibase\Lib\Tests\Store\Sql\Terms;

use PHPUnit\Framework\TestCase;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermIdsResolver;
use Wikibase\Lib\Store\Sql\Terms\StaticTypeIdsStore;
use Wikibase\Lib\Store\Sql\Terms\TypeIdsResolver;
use Wikibase\TermStore\MediaWiki\Tests\Util\FakeLoadBalancer;
use Wikimedia\Rdbms\DatabaseSqlite;
use Wikimedia\Rdbms\IDatabase;

/**
 * @covers \Wikibase\Lib\Store\Sql\Terms\DatabaseTermIdsResolver
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DatabaseTermIdsResolverTest extends TestCase {

	const TYPE_LABEL = 1;
	const TYPE_DESCRIPTION = 2;
	const TYPE_ALIAS = 3;

	/** @var TypeIdsResolver */
	private $typeIdsResolver;

	/** @var IDatabase */
	private $db;

	private function getSqlFileAbsolutePath() {
		return __DIR__ . '/../../../../../../repo/sql/AddNormalizedTermsTablesDDL.sql';
	}

	public function setUp() {
		$this->typeIdsResolver = new StaticTypeIdsStore( [
			'label' => self::TYPE_LABEL,
			'description' => self::TYPE_DESCRIPTION,
			'alias' => self::TYPE_ALIAS,
		] );
		$this->db = DatabaseSqlite::newStandaloneInstance( ':memory:' );
		$this->db->sourceFile( $this->getSqlFileAbsolutePath() );
	}

	public function testCanResolveEverything() {
		$this->db->insert( 'wbt_text',
			[ 'wbx_text' => 'text' ] );
		$text1Id = $this->db->insertId();
		$this->db->insert( 'wbt_text',
			[ 'wbx_text' => 'Text' ] );
		$text2Id = $this->db->insertId();
		$this->db->insert( 'wbt_text_in_lang',
			[ 'wbxl_language' => 'en', 'wbxl_text_id' => $text1Id ] );
		$textInLang1Id = $this->db->insertId();
		$this->db->insert( 'wbt_text_in_lang',
			[ 'wbxl_language' => 'de', 'wbxl_text_id' => $text2Id ] );
		$textInLang2Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => self::TYPE_LABEL, 'wbtl_text_in_lang_id' => $textInLang1Id ] );
		$termInLang1Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => self::TYPE_DESCRIPTION, 'wbtl_text_in_lang_id' => $textInLang1Id ] );
		$termInLang2Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => self::TYPE_LABEL, 'wbtl_text_in_lang_id' => $textInLang2Id ] );
		$termInLang3Id = $this->db->insertId();

		$resolver = new DatabaseTermIdsResolver(
			$this->typeIdsResolver,
			new FakeLoadBalancer( [
				'dbr' => $this->db,
			] )
		);
		$terms = $resolver->resolveTermIds( [ $termInLang1Id, $termInLang2Id, $termInLang3Id ] );

		$this->assertSame( [
			'label' => [
				'en' => [ 'text' ],
				'de' => [ 'Text' ],
			],
			'description' => [
				'en' => [ 'text' ],
			],
		], $terms );
	}

	public function testCanResolveEmptyList() {
		$resolver = new DatabaseTermIdsResolver(
			$this->typeIdsResolver,
			new FakeLoadBalancer( [ 'dbr' => $this->db ] )
		);

		$this->assertEmpty( $resolver->resolveTermIds( [] ) );
	}

	public function testGrouped() {
		$this->db->insert( 'wbt_text',
			[ 'wbx_text' => 'text' ] );
		$text1Id = $this->db->insertId();
		$this->db->insert( 'wbt_text',
			[ 'wbx_text' => 'Text' ] );
		$text2Id = $this->db->insertId();
		$this->db->insert( 'wbt_text_in_lang',
			[ 'wbxl_language' => 'en', 'wbxl_text_id' => $text1Id ] );
		$textInLang1Id = $this->db->insertId();
		$this->db->insert( 'wbt_text_in_lang',
			[ 'wbxl_language' => 'de', 'wbxl_text_id' => $text2Id ] );
		$textInLang2Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => self::TYPE_LABEL, 'wbtl_text_in_lang_id' => $textInLang1Id ] );
		$termInLang1Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => self::TYPE_DESCRIPTION, 'wbtl_text_in_lang_id' => $textInLang1Id ] );
		$termInLang2Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => self::TYPE_LABEL, 'wbtl_text_in_lang_id' => $textInLang2Id ] );
		$termInLang3Id = $this->db->insertId();

		$resolver = new DatabaseTermIdsResolver(
			$this->typeIdsResolver,
			new FakeLoadBalancer( [
				'dbr' => $this->db,
			] )
		);
		$terms = $resolver->resolveGroupedTermIds( [
			'Q1' => [ $termInLang1Id, $termInLang2Id ],
			'Q2' => [ $termInLang3Id ],
		] );

		$this->assertSame( [
			'label' => [
				'en' => [ 'text' ],
			],
			'description' => [
				'en' => [ 'text' ],
			],
		], $terms['Q1'] );
		$this->assertSame( [
			'label' => [
				'de' => [ 'Text' ],
			],
		], $terms['Q2'] );
	}

	public function testGrouped_CanResolveEmptyList() {
		$resolver = new DatabaseTermIdsResolver(
			$this->typeIdsResolver,
			new FakeLoadBalancer( [ 'dbr' => $this->db ] )
		);

		$this->assertEmpty( $resolver->resolveGroupedTermIds( [] ) );
	}

	public function testGrouped_CanResolveListOfEmptyLists() {
		$resolver = new DatabaseTermIdsResolver(
			$this->typeIdsResolver,
			new FakeLoadBalancer( [ 'dbr' => $this->db ] )
		);

		$this->assertSame(
			[ 'x' => [], 'y' => [] ],
			$resolver->resolveGroupedTermIds( [ 'x' => [], 'y' => [] ] )
		);
	}

	public function testGrouped_CanResolveListOfMixedEmptyAndNonemptyLists() {
		$this->db->insert( 'wbt_text',
			[ 'wbx_text' => 'text' ] );
		$text1Id = $this->db->insertId();
		$this->db->insert( 'wbt_text',
			[ 'wbx_text' => 'Text' ] );
		$text2Id = $this->db->insertId();
		$this->db->insert( 'wbt_text_in_lang',
			[ 'wbxl_language' => 'en', 'wbxl_text_id' => $text1Id ] );
		$textInLang1Id = $this->db->insertId();
		$this->db->insert( 'wbt_text_in_lang',
			[ 'wbxl_language' => 'de', 'wbxl_text_id' => $text2Id ] );
		$textInLang2Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => self::TYPE_LABEL, 'wbtl_text_in_lang_id' => $textInLang1Id ] );
		$termInLang1Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => self::TYPE_DESCRIPTION, 'wbtl_text_in_lang_id' => $textInLang1Id ] );
		$termInLang2Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => self::TYPE_LABEL, 'wbtl_text_in_lang_id' => $textInLang2Id ] );
		$termInLang3Id = $this->db->insertId();

		$resolver = new DatabaseTermIdsResolver(
			$this->typeIdsResolver,
			new FakeLoadBalancer( [
				'dbr' => $this->db,
			] )
		);
		$terms = $resolver->resolveGroupedTermIds( [
			'Q1' => [ $termInLang1Id, $termInLang2Id ],
			'Q2' => [ $termInLang3Id ],
			'Q3' => [],
		] );

		$this->assertSame( [
			'label' => [
				'en' => [ 'text' ],
			],
			'description' => [
				'en' => [ 'text' ],
			],
		], $terms['Q1'] );
		$this->assertSame( [
			'label' => [
				'de' => [ 'Text' ],
			],
		], $terms['Q2'] );
		$this->assertSame( [], $terms['Q3'] );
	}

	public function testGrouped_sameTermsInMultipleGroups() {
		$this->db->insert( 'wbt_text',
			[ 'wbx_text' => 'text' ] );
		$text1Id = $this->db->insertId();
		$this->db->insert( 'wbt_text',
			[ 'wbx_text' => 'Text' ] );
		$text2Id = $this->db->insertId();
		$this->db->insert( 'wbt_text_in_lang',
			[ 'wbxl_language' => 'en', 'wbxl_text_id' => $text1Id ] );
		$textInLang1Id = $this->db->insertId();
		$this->db->insert( 'wbt_text_in_lang',
			[ 'wbxl_language' => 'de', 'wbxl_text_id' => $text2Id ] );
		$textInLang2Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => self::TYPE_LABEL, 'wbtl_text_in_lang_id' => $textInLang1Id ] );
		$termInLang1Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => self::TYPE_DESCRIPTION, 'wbtl_text_in_lang_id' => $textInLang1Id ] );
		$termInLang2Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => self::TYPE_LABEL, 'wbtl_text_in_lang_id' => $textInLang2Id ] );
		$termInLang3Id = $this->db->insertId();

		$resolver = new DatabaseTermIdsResolver(
			$this->typeIdsResolver,
			new FakeLoadBalancer( [
				'dbr' => $this->db,
			] )
		);
		$terms = $resolver->resolveGroupedTermIds( [
			'Q1' => [ $termInLang1Id, $termInLang2Id, $termInLang3Id ],
			'Q2' => [ $termInLang1Id, $termInLang2Id ],
			'Q3' => [ $termInLang1Id, $termInLang3Id ],
			'Q4' => [ $termInLang2Id, $termInLang3Id ],
		] );

		$this->assertSame( [
			'label' => [
				'en' => [ 'text' ],
				'de' => [ 'Text' ],
			],
			'description' => [
				'en' => [ 'text' ],
			],
		], $terms['Q1'] );
		$this->assertSame( [
			'label' => [
				'en' => [ 'text' ],
			],
			'description' => [
				'en' => [ 'text' ],
			],
		], $terms['Q2'] );
		$this->assertSame( [
			'label' => [
				'en' => [ 'text' ],
				'de' => [ 'Text' ],
			],
		], $terms['Q3'] );
		$this->assertSame( [
			'description' => [
				'en' => [ 'text' ],
			],
			'label' => [
				'de' => [ 'Text' ],
			],
		], $terms['Q4'] );
	}

	public function testResolveTermsViaJoin() {
		$this->db->insert( 'wbt_text',
			[ 'wbx_text' => 'text' ] );
		$text1Id = $this->db->insertId();
		$this->db->insert( 'wbt_text',
			[ 'wbx_text' => 'Text' ] );
		$text2Id = $this->db->insertId();
		$this->db->insert( 'wbt_text_in_lang',
			[ 'wbxl_language' => 'en', 'wbxl_text_id' => $text1Id ] );
		$textInLang1Id = $this->db->insertId();
		$this->db->insert( 'wbt_text_in_lang',
			[ 'wbxl_language' => 'de', 'wbxl_text_id' => $text2Id ] );
		$textInLang2Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => self::TYPE_LABEL, 'wbtl_text_in_lang_id' => $textInLang1Id ] );
		$termInLang1Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => self::TYPE_DESCRIPTION, 'wbtl_text_in_lang_id' => $textInLang1Id ] );
		$termInLang2Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => self::TYPE_LABEL, 'wbtl_text_in_lang_id' => $textInLang2Id ] );
		$termInLang3Id = $this->db->insertId();
		$this->db->insert( 'wbt_property_terms', [
			'wbpt_property_id' => 1,
			'wbpt_term_in_lang_id' => $termInLang1Id
		] );
		$this->db->insert( 'wbt_property_terms', [
			'wbpt_property_id' => 1,
			'wbpt_term_in_lang_id' => $termInLang2Id
		] );
		$this->db->insert( 'wbt_property_terms', [
			'wbpt_property_id' => 2,
			'wbpt_term_in_lang_id' => $termInLang3Id
		] );

		$resolver = new DatabaseTermIdsResolver(
			$this->typeIdsResolver,
			new FakeLoadBalancer( [
				'dbr' => $this->db,
			] )
		);

		$termIds = $resolver->resolveTermsViaJoin(
			'wbt_property_terms',
			'wbpt_property_id',
			[ 'wbpt_term_in_lang_id = wbtl_id', 'wbpt_property_id' => [ 1, 2 ] ]
		);

		$this->assertSame(
			[
				1 => [
					'label' => [ 'en' => [ 'text' ] ],
					'description' => [ 'en' => [ 'text' ] ]
				],
				2 => [ 'label' => [ 'de' => [ 'Text' ] ] ]
			],
			$termIds
		);
	}

}
