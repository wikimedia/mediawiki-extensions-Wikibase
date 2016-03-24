<?php

namespace Wikibase\DataModel\Services\Tests\Statement\Filter;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\DataModel\Services\Statement\Filter\DataTypeStatementFilter;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;

/**
 * @covers Wikibase\DataModel\Services\Statement\Filter\DataTypeStatementFilter
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class DataTypeStatementFilterTest extends PHPUnit_Framework_TestCase {

	/**
	 * @return PropertyDataTypeLookup
	 */
	private function getDataTypeLookup() {
		$dataTypeLookup = $this->getMock(
			'Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup'
		);

		$dataTypeLookup->expects( $this->once() )
			->method( 'getDataTypeIdForProperty' )
			->will( $this->returnCallback( function( PropertyId $propertyId ) {
				$id = $propertyId->getSerialization();

				if ( $id === 'P3' ) {
					throw new PropertyDataTypeLookupException( $propertyId );
				}

				return $id === 'P1' ? 'identifier' : 'string';
			} ) );

		return $dataTypeLookup;
	}

	/**
	 * @dataProvider statementProvider
	 */
	public function testIsMatch( Statement $statement, $dataTypes, $expected ) {
		$filter = new DataTypeStatementFilter( $this->getDataTypeLookup(), $dataTypes );
		$this->assertSame( $expected, $filter->statementMatches( $statement ) );
	}

	public function statementProvider() {
		$identifier = new Statement( new PropertyNoValueSnak( 1 ) );
		$string = new Statement( new PropertyNoValueSnak( 2 ) );
		$deleted = new Statement( new PropertyNoValueSnak( 3 ) );

		return array(
			array( $identifier, null, false ),
			array( $identifier, array(), false ),
			array( $identifier, 'identifier', true ),
			array( $identifier, 'identifiers', false ),
			array( $deleted, 'identifier', false ),
			array( $string, 'identifier', false ),
			array( $string, 'string', true ),
			array( $identifier, 'string', false ),
			array( $identifier, array( 'identifier' ), true ),
			array( $identifier, array( 'string', 'identifier' ), true ),
		);
	}

}
