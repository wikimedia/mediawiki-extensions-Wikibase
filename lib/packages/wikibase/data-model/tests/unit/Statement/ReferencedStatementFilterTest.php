<?php

namespace Wikibase\DataModel\Tests\Statement;

use DataValues\StringValue;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\ReferencedStatementFilter;
use Wikibase\DataModel\Statement\Statement;

/**
 * @covers \Wikibase\DataModel\Statement\ReferencedStatementFilter
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ReferencedStatementFilterTest extends \PHPUnit\Framework\TestCase {

	public function testReturnsFalseForMinimalStatement() {
		$filter = new ReferencedStatementFilter();
		$this->assertFalse( $filter->statementMatches( new Statement( new PropertyNoValueSnak( 42 ) ) ) );
	}

	public function testReturnsFalseForVerboseStatementWithoutReferences() {
		$statement = new Statement( new PropertyValueSnak( 42, new StringValue( '\o/' ) ) );
		$statement->setGuid( 'kittens of doom' );
		$statement->setRank( Statement::RANK_PREFERRED );
		$statement->setQualifiers( new SnakList( [
			new PropertyValueSnak( 1, new StringValue( 'wee' ) ),
			new PropertyValueSnak( 2, new StringValue( 'woo' ) ),
			new PropertyValueSnak( 3, new StringValue( 'waa' ) ),
		] ) );

		$filter = new ReferencedStatementFilter();
		$this->assertFalse( $filter->statementMatches( $statement ) );
	}

	public function testReturnsTrueForMinimalStatementWithReferences() {
		$statement = new Statement( new PropertyValueSnak( 42, new StringValue( '\o/' ) ) );
		$statement->setReferences( new ReferenceList( [
			new Reference( [
				new PropertyValueSnak( 1, new StringValue( 'wee' ) ),
			] ),
			new Reference( [
				new PropertyValueSnak( 2, new StringValue( 'woo' ) ),
				new PropertyValueSnak( 3, new StringValue( 'waa' ) ),
			] ),
		] ) );

		$filter = new ReferencedStatementFilter();
		$this->assertTrue( $filter->statementMatches( $statement ) );
	}

}
