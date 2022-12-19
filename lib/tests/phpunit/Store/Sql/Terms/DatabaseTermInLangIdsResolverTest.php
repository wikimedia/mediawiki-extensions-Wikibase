<?php

namespace Wikibase\Lib\Tests\Store\Sql\Terms;

use PHPUnit\Framework\TestCase;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermInLangIdsResolver;
use Wikibase\Lib\Store\Sql\Terms\StaticTypeIdsStore;
use Wikibase\Lib\Store\Sql\Terms\TypeIdsResolver;
use Wikibase\Lib\Tests\Rdbms\LocalRepoDbTestHelper;
use Wikimedia\Rdbms\DatabaseSqlite;
use Wikimedia\Rdbms\IDatabase;

/**
 * @covers \Wikibase\Lib\Store\Sql\Terms\DatabaseTermInLangIdsResolver
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DatabaseTermInLangIdsResolverTest extends TestCase {

	use LocalRepoDbTestHelper;

	private const TYPE_LABEL = 1;
	private const TYPE_DESCRIPTION = 2;
	private const TYPE_ALIAS = 3;

	/** @var TypeIdsResolver */
	private $typeIdsResolver;

	/** @var IDatabase */
	private $db;

	private function getSqlFileAbsolutePath() {
		return __DIR__ . '/../../../../../../repo/sql/sqlite/term_store.sql';
	}

	protected function setUp(): void {
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

		$resolver = new DatabaseTermInLangIdsResolver(
			$this->typeIdsResolver,
			$this->typeIdsResolver,
			$this->getRepoDomainDb()
		);
		$terms = $resolver->resolveTermInLangIds( [ $termInLang1Id, $termInLang2Id, $termInLang3Id ] );

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

	public function resolveFilteredProvider() {
		$fullResult = [
			'label' => [
				'en' => [ 'text' ],
				'de' => [ 'Text' ],
			],
			'description' => [
				'en' => [ 'text' ],
			],
			'alias' => [
				'en' => [ 'text' ],
			],
		];
		$filteredResult = [
			'label' => [
				'en' => [ 'text' ],
			],
			'alias' => [
				'en' => [ 'text' ],
			],
		];

		return [
			'type filter exlcuding all types' => [
				[],
				[],
				null,
			],
			'language filter exlcuding all languages' => [
				[],
				null,
				[],
			],
			'types and languages = null, full result' => [
				$fullResult,
				null,
				null,
			],
			'all types and languages, full result' => [
				$fullResult,
				array_keys( $fullResult ),
				[ 'de', 'en' ],
			],
			'filtered by type' => [
				[ 'label' => $fullResult['label'] ],
				[ 'label' ],
				null,
			],
			'filtered by language' => [
				[
					'label' => [
						'de' => [ 'Text' ],
					],
				],
				null,
				[ 'nl', 'de', 'es' ],
			],
			'filtered by type and lang' => [
				$filteredResult,
				[ 'label', 'alias' ],
				[ 'en' ],
			],
			'filtered by unknown language' => [
				[],
				null,
				[ 'banana' ],
			],
		];
	}

	/**
	 * @dataProvider resolveFilteredProvider
	 */
	public function testCanResolveFiltered( array $expected, array $types = null, array $languages = null ) {
		$termInLangIds = [];

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
		$termInLangIds[] = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => self::TYPE_DESCRIPTION, 'wbtl_text_in_lang_id' => $textInLang1Id ] );
		$termInLangIds[] = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => self::TYPE_LABEL, 'wbtl_text_in_lang_id' => $textInLang2Id ] );
		$termInLangIds[] = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => self::TYPE_ALIAS, 'wbtl_text_in_lang_id' => $textInLang1Id ] );
		$termInLangIds[] = $this->db->insertId();

		$resolver = new DatabaseTermInLangIdsResolver(
			$this->typeIdsResolver,
			$this->typeIdsResolver,
			$this->getRepoDomainDb()
		);
		$terms = $resolver->resolveTermInLangIds(
			$termInLangIds,
			$types,
			$languages
		);

		$this->assertSame( $expected, $terms );
	}

	public function testCanResolveEmptyList() {
		$resolver = new DatabaseTermInLangIdsResolver(
			$this->typeIdsResolver,
			$this->typeIdsResolver,
			$this->getRepoDomainDb()
		);

		$this->assertSame( [], $resolver->resolveTermInLangIds( [] ) );
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

		$resolver = new DatabaseTermInLangIdsResolver(
			$this->typeIdsResolver,
			$this->typeIdsResolver,
			$this->getRepoDomainDb()
		);
		$terms = $resolver->resolveGroupedTermInLangIds( [
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
		$resolver = new DatabaseTermInLangIdsResolver(
			$this->typeIdsResolver,
			$this->typeIdsResolver,
			$this->getRepoDomainDb()
		);

		$this->assertSame( [], $resolver->resolveGroupedTermInLangIds( [] ) );
	}

	public function testGrouped_CanResolveListOfEmptyLists() {
		$resolver = new DatabaseTermInLangIdsResolver(
			$this->typeIdsResolver,
			$this->typeIdsResolver,
			$this->getRepoDomainDb()
		);

		$this->assertSame(
			[ 'x' => [], 'y' => [] ],
			$resolver->resolveGroupedTermInLangIds( [ 'x' => [], 'y' => [] ] )
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

		$resolver = new DatabaseTermInLangIdsResolver(
			$this->typeIdsResolver,
			$this->typeIdsResolver,
			$this->getRepoDomainDb()
		);
		$terms = $resolver->resolveGroupedTermInLangIds( [
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

		$resolver = new DatabaseTermInLangIdsResolver(
			$this->typeIdsResolver,
			$this->typeIdsResolver,
			$this->getRepoDomainDb()
		);
		$terms = $resolver->resolveGroupedTermInLangIds( [
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
		$this->db->insert( 'wbt_text', [ 'wbx_text' => 'text' ] );
		$text1Id = $this->db->insertId();
		$this->db->insert( 'wbt_text', [ 'wbx_text' => 'Text' ] );
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
			'wbpt_term_in_lang_id' => $termInLang1Id,
		] );
		$this->db->insert( 'wbt_property_terms', [
			'wbpt_property_id' => 1,
			'wbpt_term_in_lang_id' => $termInLang2Id,
		] );
		$this->db->insert( 'wbt_property_terms', [
			'wbpt_property_id' => 2,
			'wbpt_term_in_lang_id' => $termInLang3Id,
		] );

		$resolver = new DatabaseTermInLangIdsResolver(
			$this->typeIdsResolver,
			$this->typeIdsResolver,
			$this->getRepoDomainDb()
		);

		$termIds = $resolver->resolveTermsViaJoin(
			'wbt_property_terms',
			'wbpt_term_in_lang_id',
			'wbpt_property_id',
			[ 'wbpt_property_id' => [ 1, 2 ] ]
		);

		$this->assertSame(
			[
				1 => [
					'label' => [ 'en' => [ 'text' ] ],
					'description' => [ 'en' => [ 'text' ] ],
				],
				2 => [ 'label' => [ 'de' => [ 'Text' ] ] ],
			],
			$termIds
		);
	}

	public function testGrouped_filtered() {
		$this->db->insert( 'wbt_text', [ 'wbx_text' => 'text' ] );
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
		$this->db->insert( 'wbt_text_in_lang',
			[ 'wbxl_language' => 'ru', 'wbxl_text_id' => $text2Id ] );
		$textInLang3Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => self::TYPE_LABEL, 'wbtl_text_in_lang_id' => $textInLang1Id ] );
		$termInLang1Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => self::TYPE_DESCRIPTION, 'wbtl_text_in_lang_id' => $textInLang1Id ] );
		$termInLang2Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => self::TYPE_LABEL, 'wbtl_text_in_lang_id' => $textInLang2Id ] );
		$termInLang3Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => self::TYPE_ALIAS, 'wbtl_text_in_lang_id' => $textInLang3Id ] );
		$termInLang4Id = $this->db->insertId();

		$resolver = new DatabaseTermInLangIdsResolver(
			$this->typeIdsResolver,
			$this->typeIdsResolver,
			$this->getRepoDomainDb()
		);
		$terms = $resolver->resolveGroupedTermInLangIds(
			[
				'Q1' => [ $termInLang1Id, $termInLang2Id, $termInLang3Id ],
				'Q2' => [ $termInLang1Id, $termInLang2Id ],
				'Q3' => [ $termInLang1Id, $termInLang3Id, $termInLang4Id ],
				'Q4' => [ $termInLang2Id, $termInLang3Id ],
			],
			[ 'label', 'alias' ],
			[ 'ru', 'en' ]
		);

		$this->assertSame( [
			'label' => [
				'en' => [ 'text' ],
			],
		], $terms['Q1'] );
		$this->assertSame( [
			'label' => [
				'en' => [ 'text' ],
			],
		], $terms['Q2'] );
		$this->assertSame( [
			'label' => [
				'en' => [ 'text' ],
			],
			'alias' => [
				'ru' => [ 'Text' ],
			],
		], $terms['Q3'] );
		$this->assertSame( [], $terms['Q4'] );
	}

}
