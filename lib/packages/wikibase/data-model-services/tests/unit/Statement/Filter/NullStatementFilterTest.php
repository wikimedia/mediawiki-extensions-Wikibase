<?php

namespace Wikibase\DataModel\Services\Tests\Statement\Filter;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Services\Statement\Filter\NullStatementFilter;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;

/**
 * @covers Wikibase\DataModel\Services\Statement\Filter\NullStatementFilter
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class NullStatementFilterTest extends PHPUnit_Framework_TestCase {

	public function testIsMatch() {
		$filter = new NullStatementFilter();
		$statement = new Statement( new PropertyNoValueSnak( 1 ) );
		$this->assertTrue( $filter->statementMatches( $statement ) );
	}

}
