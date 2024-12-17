<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Store\Sql\Terms;

use MediaWiki\Tests\MockDatabase;
use MediaWikiCoversValidator;
use MediaWikiTestCaseTrait;
use PHPUnit\Framework\TestCase;
use Wikibase\Lib\Rdbms\TermsDomainDb;
use Wikibase\Repo\Store\Sql\Terms\EntityTermsSelectQueryBuilder;
use Wikimedia\Rdbms\IExpression;

/**
 * @covers \Wikibase\Repo\Store\Sql\Terms\EntityTermsSelectQueryBuilder
 * @group Wikibase
 * @license GPL-2.0-or-later
 */
class EntityTermsSelectQueryBuilderTest extends TestCase {
	use MediaWikiCoversValidator;
	use MediaWikiTestCaseTrait;

	public static function provideEntityTypeAndExpectedQueryInfo(): iterable {
		yield 'item' => [
			'entityType' => 'item',
			'expectedTables' => [
				'wbt_item_terms',
				'wbt_term_in_lang' => 'wbt_term_in_lang',
				'wbt_text_in_lang' => 'wbt_text_in_lang',
				'wbt_text' => 'wbt_text',
			],
			'expectedJoinConds' => [
				'wbt_term_in_lang' => [
					'JOIN',
					'wbit_term_in_lang_id=wbtl_id',
				],
				'wbt_text_in_lang' => [
					'JOIN',
					'wbtl_text_in_lang_id=wbxl_id',
				],
				'wbt_text' => [
					'JOIN',
					'wbxl_text_id=wbx_id',
				],
			],
			'expectedEntityIdColumn' => 'wbit_item_id',
		];

		yield 'property' => [
			'entityType' => 'property',
			'expectedTables' => [
				'wbt_property_terms',
				'wbt_term_in_lang' => 'wbt_term_in_lang',
				'wbt_text_in_lang' => 'wbt_text_in_lang',
				'wbt_text' => 'wbt_text',
			],
			'expectedJoinConds' => [
				'wbt_term_in_lang' => [
					'JOIN',
					'wbpt_term_in_lang_id=wbtl_id',
				],
				'wbt_text_in_lang' => [
					'JOIN',
					'wbtl_text_in_lang_id=wbxl_id',
				],
				'wbt_text' => [
					'JOIN',
					'wbxl_text_id=wbx_id',
				],
			],
			'expectedEntityIdColumn' => 'wbpt_property_id',
		];
	}

	/** @dataProvider provideEntityTypeAndExpectedQueryInfo */
	public function testConstructor(
		string $entityType,
		array $expectedTables,
		array $expectedJoinConds,
		string $expectedEntityIdColumn
	): void {
		$sqb = new EntityTermsSelectQueryBuilder( $this->newTermsDb(), $entityType );
		$queryInfo = $sqb->getQueryInfo();

		$this->assertSame( $expectedTables, $queryInfo['tables'] );
		$this->assertSame( $expectedJoinConds, $queryInfo['join_conds'] );
		$this->assertSame( [ $expectedEntityIdColumn ], $queryInfo['fields'] );
		$this->assertSame( $expectedEntityIdColumn, $sqb->getEntityIdColumn() );
	}

	public function testWhereTerm(): void {
		$sqb = new EntityTermsSelectQueryBuilder( $this->newTermsDb(), 'item' );

		$sqb->whereTerm( 1, 'lang', 'text' );

		$this->assertSame( [
			'wbtl_type_id' => 1,
			'wbxl_language' => 'lang',
			'wbx_text' => 'text',
		], $sqb->getQueryInfo()['conds'] );
	}

	public function testWhereMultiTerm(): void {
		$db = new MockDatabase();
		$sqb = new EntityTermsSelectQueryBuilder( $this->newTermsDb( $db ), 'item' );

		$sqb->whereMultiTerm( 1, [ 'l1', 'l2' ], [ 't1', 't2' ] );

		$expected = '((wbtl_type_id = 1 AND wbxl_language = \'l1\' AND wbx_text = \'t1\')' .
			' OR (wbtl_type_id = 1 AND wbxl_language = \'l2\' AND wbx_text = \'t2\'))';
		$actualConds = $sqb->getQueryInfo()['conds'];
		foreach ( $actualConds as &$conds ) {
			if ( $conds instanceof IExpression ) {
				$conds = $conds->toSql( $db );
			}
		}
		$this->assertSame(
			[ $expected ],
			$actualConds
		);
	}

	public function newTermsDb( MockDatabase $db = null ): TermsDomainDb {
		$db ??= new MockDatabase();
		$termsDb = $this->createStub( TermsDomainDb::class );
		$termsDb->method( 'getReadConnection' )->willReturn( $db );

		return $termsDb;
	}

}
