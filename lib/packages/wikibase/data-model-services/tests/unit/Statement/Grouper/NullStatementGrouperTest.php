<?php

namespace Wikibase\DataModel\Services\Tests\Statement\Grouper;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Services\Statement\Grouper\NullStatementGrouper;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\StatementList;

/**
 * @covers \Wikibase\DataModel\Services\Statement\Grouper\NullStatementGrouper
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class NullStatementGrouperTest extends TestCase {

	public function testGroupStatements() {
		$statements = new StatementList();
		$statements->addNewStatement( new PropertyNoValueSnak( 1 ) );

		$expected = [ 'statements' => $statements ];

		$grouper = new NullStatementGrouper();
		$this->assertSame( $expected, $grouper->groupStatements( $statements ) );
	}

}
