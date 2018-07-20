<?php

namespace Wikibase\Repo\Tests\Merge;

use DataValues\StringValue;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\Repo\Merge\StatementsMerger;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\Tests\NewStatement;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Merge\StatementsMerger
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class StatementsMergerTest extends TestCase {

	/**
	 * @dataProvider statementsProvider
	 */
	public function testMergeStatements( $sourceStatements, $targetStatements, $afterMergeTargetStatements ) {
		$source = new Item( new ItemId( 'Q123' ), null, null, new StatementList( $sourceStatements ) );
		$target = new Item( new ItemId( 'Q321' ), null, null, new StatementList( $targetStatements ) );

		$merger = $this->newStatementsMerger();
		$merger->merge( $source, $target );

		$this->assertSameStatementsArray(
			$afterMergeTargetStatements,
			$target->getStatements()->toArray()
		);
		$this->assertEmpty( $source->getStatements() );
	}

	/**
	 * @dataProvider nonEntityDocumentProvider
	 *
	 * @expectedException InvalidArgumentException
	 */
	public function testGivenNotAnEntity_mergeThrowsException( $source, $target ) {
		$this->newStatementsMerger()->merge( $source, $target );
	}

	/**
	 * Checks whether statements are equivalent without checking GUIDs
	 *
	 * @param Statement[] $expected
	 * @param Statement[] $actual
	 */
	private function assertSameStatementsArray( array $expected, array $actual ) {
		$this->assertSameSize( $expected, $actual );

		foreach ( $expected as $i => $expectedStatement ) {
			$this->assertEquals(
				$expectedStatement->getHash(),
				$actual[$i]->getHash()
			);
		}
	}

	/**
	 * @return StatementsMerger
	 */
	private function newStatementsMerger() {
		return new StatementsMerger(
			WikibaseRepo::getDefaultInstance()->getChangeOpFactoryProvider()->getStatementChangeOpFactory()
		);
	}

	public function statementsProvider() {
		yield 'no statements' => [
			[],
			[],
			[]
		];

		$statement1 = NewStatement::forProperty( 'P42' )
			->withSomeGuid()
			->withValue( new ItemId( 'Q111' ) )
			->build();
		yield 'given no statements in target, copied from source' => [
			[ $statement1 ],
			[],
			[ $statement1 ]
		];

		$statement2 = NewStatement::forProperty( 'P23' )
			->withSomeGuid()
			->withValue( 'hello' )
			->build();
		yield 'given both source and target have statements, merge' => [
			[ $statement1 ],
			[ $statement2 ],
			[ $statement2, $statement1 ]
		];

		$qualified = NewStatement::forProperty( 'P123' )
			->withSomeGuid()
			->withValue( 'foo' )
			->withQualifier( 'P777', 'bar' )
			->build();
		yield 'given qualified statements on source, qualifiers are copied to target' => [
			[ $qualified ],
			[],
			[ $qualified ]
		];

		$statementBuilder = NewStatement::forProperty( 'P321' )
			->withValue( 'abcd' );
		yield 'given equivalent statements on source and target, merge into one' => [
			[ $statementBuilder->withSomeGuid()->build() ],
			[ $statementBuilder->withSomeGuid()->build() ],
			[ $statementBuilder->build() ]
		];

		$reference1 = new PropertyValueSnak( new PropertyId( 'P345' ), new StringValue( 'hi' ) );
		$reference2 = new PropertyValueSnak( new PropertyId( 'P456' ), new StringValue( 'hello' ) );
		yield 'given equivalent statements with references, references are merged' => [
			[ $this->newStatementWithReferences( $statementBuilder, [ $reference1 ] ) ],
			[ $this->newStatementWithReferences( $statementBuilder, [ $reference2 ] ) ],
			[ $this->newStatementWithReferences( $statementBuilder, [ $reference2, $reference1 ] ) ]
		];
	}

	private function newStatementWithReferences( NewStatement $statementBuilder, array $references ) {
		$statement = $statementBuilder->withSomeGuid()->build();
		foreach ( $references as $reference ) {
			$statement->addNewReference( $reference );
		}

		return $statement;
	}

	public function nonEntityDocumentProvider() {
		$nonEntity = $this->getMockBuilder( StatementListProvider::class )->getMock();

		yield 'source not an entity' => [
			$nonEntity,
			new Item()
		];

		yield 'target not an entity' => [
			new Item(),
			$nonEntity
		];

		yield 'source and target both not entities' => [
			$nonEntity,
			clone $nonEntity
		];
	}

}
