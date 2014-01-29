<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\ClaimListAccess;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;

/**
 * Tests for the ClaimListAccess implementing classes.
 *
 * @group Wikibase
 * @group WikibaseDataModel
 * @group WikibaseClaim
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ClaimListAccessTest extends \PHPUnit_Framework_TestCase {

	public function claimTestProvider() {
		$claims = array();

		$claims[] = new Claim( new PropertyNoValueSnak(
			new EntityId( Property::ENTITY_TYPE, 42 )
		) );
		$claims[] = new Claim( new PropertyValueSnak(
			new EntityId( Property::ENTITY_TYPE, 23 ),
			new \DataValues\StringValue( 'ohi' )
		) );

		$lists = array();

		$lists[] = new Claims();

		$argLists = array();

		foreach ( $claims as $i => $claim ) {
			$claim->setGuid( "ClaimListAccessTest\$claim-$i" );
		}

		/**
		 * @var ClaimListAccess $list
		 */
		foreach ( $lists as $list ) {
			foreach ( $claims as $claim ) {
				$argLists[] = array( clone $list, array( $claim ) );
			}

			$argLists[] = array( clone $list, $claims );
		}

		return $argLists;
	}

	/**
	 * @dataProvider claimTestProvider
	 *
	 * @param ClaimListAccess $list
	 * @param array $claims
	 */
	public function testAllOfTheStuff( ClaimListAccess $list, array $claims ) {
		foreach ( $claims as $claim ) {
			$list->addClaim( $claim );
			$this->assertTrue( $list->hasClaim( $claim ) );

			$list->removeClaim( $claim );
			$this->assertFalse( $list->hasClaim( $claim ) );
		}
	}

}
