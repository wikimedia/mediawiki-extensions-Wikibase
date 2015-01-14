<?php

namespace Wikibase\DataModel\Tests\Claim;

use DataValues\StringValue;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Snak\Snaks;

/**
 * @covers Wikibase\DataModel\Claim\Claim
 *
 * @group Wikibase
 * @group WikibaseDataModel
 * @group WikibaseClaim
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class ClaimTest extends \PHPUnit_Framework_TestCase {

	public function constructorProvider() {
		$propertyId = new PropertyId( 'P42' );

		return array(
			array(
				new PropertyNoValueSnak( $propertyId ),
			),
			array(
				new PropertyNoValueSnak( $propertyId ),
				new SnakList(),
			),
			array(
				new PropertyNoValueSnak( $propertyId ),
				new SnakList( array(
					new PropertyValueSnak( $propertyId, new StringValue( 'a' ) ),
					new PropertySomeValueSnak( new PropertyId( 'P1' ) ),
					new PropertyNoValueSnak( new PropertyId( 'P2' ) )
				) ),
			)
		);
	}

	public function instanceProvider() {
		return array_map(
			function( array $arguments ) {
				$mainSnak = $arguments[0];
				$qualifiers = array_key_exists( 1, $arguments ) ? $arguments[1] : null;
				return array( new Claim( $mainSnak, $qualifiers ) );
			},
			$this->constructorProvider()
		);
	}

	/**
	 * @dataProvider constructorProvider
	 *
	 * @param Snak $mainSnak
	 * @param Snaks|null $qualifiers
	 */
	public function testConstructor( Snak $mainSnak, Snaks $qualifiers = null ) {
		$claim = new Claim( $mainSnak, $qualifiers );

		$this->assertInstanceOf( 'Wikibase\DataModel\Claim\Claim', $claim );

		$this->assertEquals( $mainSnak, $claim->getMainSnak() );

		if ( $qualifiers === null ) {
			$this->assertCount( 0, $claim->getQualifiers() );
		} else {
			$this->assertEquals( $qualifiers, $claim->getQualifiers() );
		}
	}

	public function testSetMainSnak() {
		$propertyId = new PropertyId( 'P42' );

		$claim = new Claim( new PropertyNoValueSnak( $propertyId ) );

		$mainSnak = new PropertyNoValueSnak( new PropertyId( 'P41' ) );
		$claim->setMainSnak( $mainSnak );
		$this->assertEquals( $mainSnak, $claim->getMainSnak() );

		$mainSnak = new PropertyValueSnak( new PropertyId( 'P43' ), new StringValue( 'a' ) );
		$claim->setMainSnak( $mainSnak );
		$this->assertEquals( $mainSnak, $claim->getMainSnak() );

		$mainSnak = new PropertyNoValueSnak( $propertyId );
		$claim->setMainSnak( $mainSnak );
		$this->assertEquals( $mainSnak, $claim->getMainSnak() );
	}

	public function testSetQualifiers() {
		$propertyId = new PropertyId( 'P42' );

		$claim = new Claim( new PropertyNoValueSnak( $propertyId ) );

		$qualifiers = new SnakList();
		$claim->setQualifiers( $qualifiers );
		$this->assertEquals( $qualifiers, $claim->getQualifiers() );

		$qualifiers = new SnakList( array( new PropertyValueSnak( $propertyId, new StringValue( 'a' ) ) ) );
		$claim->setQualifiers( $qualifiers );
		$this->assertEquals( $qualifiers, $claim->getQualifiers() );

		$qualifiers = new SnakList( array(
			new PropertyValueSnak( $propertyId, new StringValue( 'a' ) ),
			new PropertySomeValueSnak( new PropertyId( 'P2' ) ),
			new PropertyNoValueSnak( new PropertyId( 'P3' ) )
		) );
		$claim->setQualifiers( $qualifiers );
		$this->assertEquals( $qualifiers, $claim->getQualifiers() );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetPropertyId( Claim $claim ) {
		$this->assertEquals(
			$claim->getMainSnak()->getPropertyId(),
			$claim->getPropertyId()
		);
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testSetGuid( Claim $claim ) {
		$claim->setGuid( 'foo-bar-baz' );
		$this->assertEquals( 'foo-bar-baz', $claim->getGuid() );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetGuid( Claim $claim ) {
		$guid = $claim->getGuid();
		$this->assertTrue( $guid === null || is_string( $guid ) );
		$this->assertEquals( $guid, $claim->getGuid() );

		$claim->setGuid( 'foobar' );
		$this->assertEquals( 'foobar', $claim->getGuid() );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testSerialize( Claim $claim ) {
		$copy = unserialize( serialize( $claim ) );

		$this->assertEquals( $claim->getHash(), $copy->getHash(), 'Serialization roundtrip should not affect hash' );
	}

	public function testGetHashStability() {
		$claim0 = new Claim( new PropertyNoValueSnak( 42 ) );
		$claim0->setGuid( 'claim0' );

		$claim1 = new Claim( new PropertyNoValueSnak( 42 ) );
		$claim1->setGuid( 'claim1' );

		$this->assertEquals( $claim0->getHash(), $claim1->getHash() );
	}

	public function testSetInvalidGuidCausesException() {
		$claim = new Claim( new PropertyNoValueSnak( 42 ) );

		$this->setExpectedException( 'InvalidArgumentException' );
		$claim->setGuid( 42 );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetRank( Claim $claim ) {
		$this->assertEquals( Claim::RANK_TRUTH, $claim->getRank() );
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param Claim $claim
	 */
	public function testGetAllSnaks( Claim $claim ) {
		$snaks = $claim->getAllSnaks();

		$this->assertGreaterThanOrEqual(
			count( $claim->getQualifiers() ) + 1,
			count( $snaks ),
			"At least one snak per Qualifier"
		);
	}

}
