<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use ParserOutput;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Repo\ParserOutput\EntityStatementDataUpdaterAdapter;
use Wikibase\Repo\ParserOutput\StatementDataUpdater;

/**
 * @covers Wikibase\Repo\ParserOutput\EntityStatementDataUpdaterAdapter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityStatementDataUpdaterAdapterTest extends TestCase {

	public function testProcessEntityCallsProcessStatementForEachStatement() {
		$statement1 = $this->getMockStatement();
		$statement2 = $this->getMockStatement();
		$item = new Item( null, null, null, new StatementList( [ $statement1, $statement2 ] ) );

		$statementDataUpdater = $this->getMockStatementDataUpdater();
		$statementDataUpdater->expects( $this->exactly( 2 ) )
			->method( 'processStatement' )
			->withConsecutive(
				[ $statement1 ],
				[ $statement2 ]
			);
		$adapter = new EntityStatementDataUpdaterAdapter( $statementDataUpdater );
		$adapter->processEntity( $item );
	}

	public function testUpdateParserOutputIsDelegated() {
		$parserOutput = new ParserOutput();
		$statementDataUpdater = $this->getMockStatementDataUpdater();
		$statementDataUpdater->expects( $this->once() )
			->method( 'updateParserOutput' )
			->with( $parserOutput );

		$adapter = new EntityStatementDataUpdaterAdapter( $statementDataUpdater );

		$adapter->updateParserOutput( $parserOutput );
	}

	private function getMockStatementDataUpdater() {
		return $this->getMockBuilder( StatementDataUpdater::class )
			->getMock();
	}

	private function getMockStatement() {
		return $this->getMockBuilder( Statement::class )
			->disableOriginalConstructor()
			->getMock();
	}

}
