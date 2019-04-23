<?php

namespace Wikibase\DataModel\Services\Tests\Statement\Grouper;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Services\Statement\Grouper\ByPropertyIdStatementGrouper;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;

/**
 * @covers \Wikibase\DataModel\Services\Statement\Grouper\ByPropertyIdStatementGrouper
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class ByPropertyIdStatementGrouperTest extends TestCase {

	public function testGroupStatements() {
		$statement1 = new Statement( new PropertyNoValueSnak( 1 ) );
		$statement2 = new Statement( new PropertyNoValueSnak( 2 ) );
		$statement3 = new Statement( new PropertyNoValueSnak( 1 ) );
		$statements = new StatementList( $statement1, $statement2, $statement3 );

		$expected = [
			'P1' => new StatementList( $statement1, $statement3 ),
			'P2' => new StatementList( $statement2 ),
		];

		$grouper = new ByPropertyIdStatementGrouper();
		$this->assertEquals( $expected, $grouper->groupStatements( $statements ) );
	}

}
