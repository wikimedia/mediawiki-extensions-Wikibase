<?php

namespace Wikibase\Repo\Tests\Merge;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Merge\StatementsMerger;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\Tests\NewItem;
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

	public function testGivenNoStatements_targetHasNoStatements() {
		$source = new Item();
		$target = new Item();
		$expected = $target->getStatements()->toArray();

		$merger = $this->newStatementsMerger();
		$merger->merge( $source, $target );

		$this->assertEquals(
			$expected,
			$target->getStatements()->toArray()
		);
	}

	public function testGivenTargetHasNoStatements_sourceStatementsAreCopiedToTarget() {
		$source = NewItem::withStatement(
			NewStatement::forProperty( 'P42' )
				->withSomeGuid()
				->withValue( new ItemId( 'Q111' ) )
		)->build();
		$target = new Item( new ItemId( 'Q42' ) );
		$expected = $source->getStatements()->toArray();

		$merger = $this->newStatementsMerger();
		$merger->merge( $source, $target );

		$this->assertEquals(
			$expected[0]->getHash(),
			$target->getStatements()->toArray()[0]->getHash()
		);
	}

	/**
	 * @return StatementsMerger
	 */
	private function newStatementsMerger() {
		return new StatementsMerger(
			WikibaseRepo::getDefaultInstance()->getChangeOpFactoryProvider()->getStatementChangeOpFactory()
		);
	}

	// TODO: more tests

}
