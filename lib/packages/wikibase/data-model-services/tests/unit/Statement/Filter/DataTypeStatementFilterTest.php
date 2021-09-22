<?php

namespace Wikibase\DataModel\Services\Tests\Statement\Filter;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\DataModel\Services\Statement\Filter\DataTypeStatementFilter;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;

/**
 * @covers \Wikibase\DataModel\Services\Statement\Filter\DataTypeStatementFilter
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class DataTypeStatementFilterTest extends TestCase {

	/**
	 * @return PropertyDataTypeLookup
	 */
	private function getDataTypeLookup() {
		$dataTypeLookup = $this->createMock( PropertyDataTypeLookup::class );

		$dataTypeLookup->expects( $this->once() )
			->method( 'getDataTypeIdForProperty' )
			->will( $this->returnCallback( static function( PropertyId $propertyId ) {
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

		return [
			[ $identifier, null, false ],
			[ $identifier, [], false ],
			[ $identifier, 'identifier', true ],
			[ $identifier, 'identifiers', false ],
			[ $deleted, 'identifier', false ],
			[ $string, 'identifier', false ],
			[ $string, 'string', true ],
			[ $identifier, 'string', false ],
			[ $identifier, [ 'identifier' ], true ],
			[ $identifier, [ 'string', 'identifier' ], true ],
		];
	}

}
