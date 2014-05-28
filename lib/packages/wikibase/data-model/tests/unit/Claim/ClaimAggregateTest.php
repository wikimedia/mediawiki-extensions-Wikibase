<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\ClaimAggregate;
use Wikibase\DataModel\Claim\Claims;
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

		$aggregates[] = Property::newFromType( 'string' );

		$argLists = array();

		/**
		 * @var Claim $claim
		 */
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
		$obtainedClaims = $aggregate->getClaims();
		$this->assertInternalType( 'array', $obtainedClaims );

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
			$obtainedClaims[] = $claim;
		}

		$freshlyObtained = $aggregate->getClaims();

		$this->assertEquals(
			$unmodifiedClaims,
			$freshlyObtained,
			'Was able to modify statements via ClaimAggregate::getClaims'
		);

		$unmodifiedClaimsLookup = new Claims( $unmodifiedClaims );

		foreach ( $freshlyObtained as $obtainedClaim ) {
			$this->assertTrue( $unmodifiedClaimsLookup->hasClaim( $obtainedClaim ) );
		}
	}

}
