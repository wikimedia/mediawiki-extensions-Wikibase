<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\SnakList;

/**
 * @covers Wikibase\DataModel\Claim\Claim
 *
 * @group Wikibase
 * @group WikibaseDataModel
 * @group WikibaseClaim
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ClaimStandaloneTest extends \PHPUnit_Framework_TestCase {

	public function testGivenNonClaim_equalsReturnsFalse() {
		$claim = new Claim( new PropertyNoValueSnak( 42 ) );

		$this->assertFalse( $claim->equals( null ) );
		$this->assertFalse( $claim->equals( 42 ) );
		$this->assertFalse( $claim->equals( new \stdClass() ) );
	}

	public function testGivenSameClaim_equalsReturnsTrue() {
		$claim = new Claim(
			new PropertyNoValueSnak( 42 ),
			new SnakList( array(
				new PropertyNoValueSnak( 1337 ),
			) )
		);

		$claim->setGuid( 'kittens' );

		$this->assertTrue( $claim->equals( $claim ) );
		$this->assertTrue( $claim->equals( clone $claim ) );
	}

	public function testGivenClaimWithDifferentProperty_equalsReturnsFalse() {
		$claim = new Claim( new PropertyNoValueSnak( 42 ) );
		$this->assertFalse( $claim->equals( new Claim( new PropertyNoValueSnak( 43 ) ) ) );
	}

	public function testGivenClaimWithDifferentSnakType_equalsReturnsFalse() {
		$claim = new Claim( new PropertyNoValueSnak( 42 ) );
		$this->assertFalse( $claim->equals( new Claim( new PropertySomeValueSnak( 42 ) ) ) );
	}

	public function testGivenClaimWithDifferentQualifiers_equalsReturnsFalse() {
		$claim = new Claim(
			new PropertyNoValueSnak( 42 ),
			new SnakList( array(
				new PropertyNoValueSnak( 1337 ),
			) )
		);

		$differentClaim = new Claim(
			new PropertyNoValueSnak( 42 ),
			new SnakList( array(
				new PropertyNoValueSnak( 32202 ),
			) )
		);

		$this->assertFalse( $claim->equals( $differentClaim ) );
	}

	public function testGivenClaimWithDifferentGuids_equalsReturnsFalse() {
		$claim = new Claim( new PropertyNoValueSnak( 42 ) );

		$differentClaim = new Claim( new PropertyNoValueSnak( 42 ) );
		$differentClaim->setGuid( 'kittens' );

		$this->assertFalse( $claim->equals( $differentClaim ) );
	}

	public function testGivenSimilarStatement_equalsReturnsFalse() {
		$claim = new Claim( new PropertyNoValueSnak( 42 ) );
		$this->assertFalse( $claim->equals( new Statement( new Claim( new PropertyNoValueSnak( 42 ) ) ) ) );
	}

}
