<?php

namespace Wikibase\DataModel\Services\Tests\Statement\Filter;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Services\Statement\Filter\PropertySetStatementFilter;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;

/**
 * @covers Wikibase\DataModel\Services\Statement\Filter\PropertySetStatementFilter
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class PropertySetStatementFilterTest extends PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider statementProvider
	 */
	public function testIsMatch( Statement $statement, $propertyIds, $expected ) {
		$filter = new PropertySetStatementFilter( $propertyIds );
		$this->assertSame( $expected, $filter->statementMatches( $statement ) );
	}

	public function statementProvider() {
		$p1 = new Statement( new PropertyNoValueSnak( 1 ) );
		$p2 = new Statement( new PropertyNoValueSnak( 2 ) );

		return array(
			array( $p1, null, false ),
			array( $p1, array(), false ),
			array( $p1, 'P1', true ),
			array( $p1, 'P11', false ),
			array( $p2, 'P1', false ),
			array( $p2, 'P2', true ),
			array( $p1, 'P2', false ),
			array( $p1, array( 'P1' ), true ),
			array( $p1, array( 'P2', 'P1' ), true ),
		);
	}

}
