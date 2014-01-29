<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\ClaimAggregate;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;

/**
 * Tests for ClaimAggregate implementing classes.
 *
 * @group Wikibase
 * @group WikibaseDataModel
 * @group WikibaseClaim
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ClaimAggregateTest extends \PHPUnit_Framework_TestCase {

	public function ClaimTestProvider() {
		$claims = array();

		$claims[] = new Claim( new PropertyNoValueSnak(
			new PropertyId( 'P42' )
		) );
		$claims[] = new Claim( new PropertyValueSnak(
			new PropertyId( 'P23' ),
			new StringValue( 'ohi' )
		) );

		$aggregates = array();

		$aggregates[] = Property::newEmpty();

		$argLists = array();

		foreach ( $claims as $i => $claim ) {
			$claim->setGuid( "ClaimListAccessTest\$claim-$i" );
		}

		/**
		 * @var ClaimAggregate $aggregate
		 */
		foreach ( $aggregates as $aggregate ) {
			foreach ( $claims as $claim ) {
				$argLists[] = array( clone $aggregate, array( $claim ) );
			}

			$argLists[] = array( clone $aggregate, $claims );
		}

		return $argLists;
	}

	/**
	 * @dataProvider ClaimTestProvider
	 *
	 * @param ClaimAggregate $aggregate
	 * @param array $claims
	 */
	public function testAllOfTheStuff( ClaimAggregate $aggregate, array $claims ) {
		$obtainedClaims = new Claims( $aggregate->getClaims() );
		$this->assertInstanceOf( '\Wikibase\Claims', $obtainedClaims );

		// Below code tests if the Claims in the ClaimAggregate indeed do not get modified.

		$unmodifiedClaims = clone $obtainedClaims;

		$qualifiers = new SnakList( array( new PropertyValueSnak(
			new PropertyId( 'P10' ),
			new StringValue( 'ohi' )
		) ) );

		/**
		 * @var Claim $claim
		 */
		foreach ( $obtainedClaims as $claim ) {
			$claim->setQualifiers( $qualifiers );
		}

		foreach ( $claims as $claim ) {
			$obtainedClaims->addClaim( $claim );
		}

		$freshlyObtained = new Claims( $aggregate->getClaims() );

		$this->assertEquals(
			iterator_to_array( $unmodifiedClaims ),
			iterator_to_array( $freshlyObtained ),
			'Was able to modify statements via ClaimAggregate::getClaims'
		);

		foreach ( $freshlyObtained as $obtainedClaim ) {
			$this->assertTrue( $unmodifiedClaims->hasClaim( $obtainedClaim ) );
		}
	}

}
