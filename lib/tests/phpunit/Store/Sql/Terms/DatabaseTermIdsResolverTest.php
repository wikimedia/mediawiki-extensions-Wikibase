<?php

namespace Wikibase\Lib\Tests\Store\Sql\Terms;

use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermIdsResolver;
use Wikibase\Lib\Store\Sql\Terms\StaticTypeIdsStore;
use Wikibase\Lib\Store\Sql\Terms\TypeIdsResolver;
use Wikibase\TermStore\MediaWiki\Tests\Util\FakeLoadBalancer;
use Wikimedia\Rdbms\Database;
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

	public function testReadsEverythingFromReplicaIfPossible() {
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

		$dbw = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();
		$dbw->expects( $this->never() )->method( 'query' );

		$resolver = new DatabaseTermIdsResolver(
			$this->typeIdsResolver,
			new FakeLoadBalancer( [
				'dbr' => $this->db,
				'dbw' => $dbw,
			] )
		);
		$resolver->resolveTermIds( [ $termInLang1Id, $termInLang2Id, $termInLang3Id ] );
	}

	public function testFallsBackToMasterIfNecessaryAndAllowed() {
		$dbr = $this->db;
		$dbw = DatabaseSqlite::newStandaloneInstance( ':memory:' );
		$dbw->sourceFile( $this->getSqlFileAbsolutePath() );
		// both master and replica have most of the data
		foreach ( [ $dbr, $dbw ] as $db ) {
			// note: we assume that both DBs get the same insert IDs
			$db->insert( 'wbt_text',
				[ 'wbx_text' => 'text' ] );
			$text1Id = $db->insertId();
			$db->insert( 'wbt_text',
				[ 'wbx_text' => 'Text' ] );
			$text2Id = $db->insertId();
			$db->insert( 'wbt_text_in_lang',
				[ 'wbxl_language' => 'en', 'wbxl_text_id' => $text1Id ] );
			$textInLang1Id = $db->insertId();
			$db->insert( 'wbt_text_in_lang',
				[ 'wbxl_language' => 'de', 'wbxl_text_id' => $text2Id ] );
			$textInLang2Id = $db->insertId();
			$db->insert( 'wbt_term_in_lang',
				[ 'wbtl_type_id' => self::TYPE_LABEL, 'wbtl_text_in_lang_id' => $textInLang1Id ] );
			$termInLang1Id = $db->insertId();
			$db->insert( 'wbt_term_in_lang',
				[ 'wbtl_type_id' => self::TYPE_DESCRIPTION, 'wbtl_text_in_lang_id' => $textInLang1Id ] );
			$termInLang2Id = $db->insertId();
		}
		// only master has the last term_in_lang row
		$db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => self::TYPE_LABEL, 'wbtl_text_in_lang_id' => $textInLang2Id ] );
		$termInLang3Id = $db->insertId();

		$resolver = new DatabaseTermIdsResolver(
			$this->typeIdsResolver,
			new FakeLoadBalancer( [
				'dbr' => $dbr,
				'dbw' => $dbw,
			] ),
			true
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

	public function testDoesNotFallBackToMasterIfNotAllowed() {
		$dbr = $this->db;
		$dbw = DatabaseSqlite::newStandaloneInstance( ':memory:' );
		$dbw->sourceFile( $this->getSqlFileAbsolutePath() );
		// both master and replica have most of the data
		foreach ( [ $dbr, $dbw ] as $db ) {
			// note: we assume that both DBs get the same insert IDs
			$db->insert( 'wbt_text',
				[ 'wbx_text' => 'text' ] );
			$text1Id = $db->insertId();
			$db->insert( 'wbt_text',
				[ 'wbx_text' => 'Text' ] );
			$text2Id = $db->insertId();
			$db->insert( 'wbt_text_in_lang',
				[ 'wbxl_language' => 'en', 'wbxl_text_id' => $text1Id ] );
			$textInLang1Id = $db->insertId();
			$db->insert( 'wbt_text_in_lang',
				[ 'wbxl_language' => 'de', 'wbxl_text_id' => $text2Id ] );
			$textInLang2Id = $db->insertId();
			$db->insert( 'wbt_term_in_lang',
				[ 'wbtl_type_id' => self::TYPE_LABEL, 'wbtl_text_in_lang_id' => $textInLang1Id ] );
			$termInLang1Id = $db->insertId();
			$db->insert( 'wbt_term_in_lang',
				[ 'wbtl_type_id' => self::TYPE_DESCRIPTION, 'wbtl_text_in_lang_id' => $textInLang1Id ] );
			$termInLang2Id = $db->insertId();
		}
		// only master has the last term_in_lang row
		$db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => self::TYPE_LABEL, 'wbtl_text_in_lang_id' => $textInLang2Id ] );
		$termInLang3Id = $db->insertId();

		$resolver = new DatabaseTermIdsResolver(
			$this->typeIdsResolver,
			new FakeLoadBalancer( [
				'dbr' => $dbr,
				'dbw' => $dbw,
			] ),
			false
		);
		$terms = $resolver->resolveTermIds( [ $termInLang1Id, $termInLang2Id, $termInLang3Id ] );

		$this->assertSame( [
			'label' => [
				'en' => [ 'text' ],
				// 'de' => [ 'Text' ], // this is the row missing from the replica
			],
			'description' => [
				'en' => [ 'text' ],
			],
		], $terms );
	}

	public function testLogsInfoOnFallbackToMaster() {
		$dbr = $this->db;
		$dbw = DatabaseSqlite::newStandaloneInstance( ':memory:' );
		$dbw->sourceFile( $this->getSqlFileAbsolutePath() );
		// only master has any data
		$dbw->insert( 'wbt_text',
			[ 'wbx_text' => 'text' ] );
		$textId = $dbw->insertId();
		$dbw->insert( 'wbt_text_in_lang',
			[ 'wbxl_language' => 'en', 'wbxl_text_id' => $textId ] );
		$textInLangId = $dbw->insertId();
		$dbw->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => self::TYPE_LABEL, 'wbtl_text_in_lang_id' => $textInLangId ] );
		$termInLangId = $dbw->insertId();

		$logger = $this->getMockBuilder( AbstractLogger::class )
			->disableOriginalConstructor()
			->getMock();
		$logger->expects( $this->atLeastOnce() )
			->method( 'info' );

		$resolver = new DatabaseTermIdsResolver(
			$this->typeIdsResolver,
			new FakeLoadBalancer( [
				'dbr' => $dbr,
				'dbw' => $dbw,
			] ),
			true,
			$logger
		);
		$resolver->resolveTermIds( [ $termInLangId ] );
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
			'Q2' => [ $termInLang3Id ]
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

}
