<?php

namespace Wikibase\DataAccess\Tests\PropertyParserFunction;

use DataValues\StringValue;
use Language;
use Wikibase\Claim;
use Wikibase\DataAccess\PropertyParserFunction\SnaksFinder;
use Wikibase\DataModel\Claim\Statement;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Item;
use Wikibase\Property;
use Wikibase\PropertyValueSnak;
use Wikibase\Test\MockPropertyLabelResolver;
use Wikibase\Test\MockRepository;

/**
 * @covers Wikibase\DataAccess\PropertyParserFunction\SnaksFinder
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseDataAccess
 * @group PropertyParserFunctionTest
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SnaksFinderTest extends \PHPUnit_Framework_TestCase {

	private function getDefaultInstance() {
		$repo = $this->newMockRepository();
		$propertyLabelResolver = new MockPropertyLabelResolver( 'en', $repo );

		return new SnaksFinder( $repo, $propertyLabelResolver );
	}

	private function newMockRepository() {
		$propertyId = new PropertyId( 'P1337' );

		$entityLookup = new MockRepository();

		$claim1 = new Claim( new PropertyValueSnak(
			$propertyId,
			new StringValue( 'a kitten!' )
		) );
		$claim1->setGuid( 'Q42$1' );

		$claim2 = new Claim( new PropertyValueSnak(
			$propertyId,
			new StringValue( 'two kittens!!' )
		) );
		$claim2->setGuid( 'Q42$2' );

		// A Statement with a lower rank which should not affect the output
		$claim3 = new Statement( new PropertyValueSnak(
			$propertyId,
			new StringValue( 'three kittens!!!' )
		) );
		$claim3->setGuid( 'Q42$3' );
		$claim3->setRank( Claim::RANK_NORMAL );

		$item = Item::newEmpty();
		$item->setId( new ItemId( 'Q42' ) );
		$item->addClaim( $claim1 );
		$item->addClaim( $claim2 );
		$item->addClaim( $claim3 );

		$property = Property::newFromType( 'string' );
		$property->setId( $propertyId );
		$property->setLabel( 'en', 'a kitten!' );

		$entityLookup->putEntity( $item );
		$entityLookup->putEntity( $property );

		return $entityLookup;
	}

	/**
	 * @dataProvider findSnaksProvider
	 */
	public function testFindSnaks( $expected, ItemId $itemId, $propertyLabelOrId ) {
		$snaksFinder = $this->getDefaultInstance();

		$snakList = $snaksFinder->findSnaks( $itemId, $propertyLabelOrId, 'en' );
		$this->assertEquals( $expected, $snakList );
	}

	public function findSnaksProvider() {
		$itemId = new ItemId( 'Q42' );

		$propertyId = new PropertyId( 'P1337' );

		$snaks = array(
			'Q42$1' => new PropertyValueSnak( $propertyId, new StringValue( 'a kitten!' ) ),
			'Q42$2' => new PropertyValueSnak( $propertyId, new StringValue( 'two kittens!!' ) )
		);

		return array(
			array( $snaks, $itemId, 'a kitten!' ),
			array( $snaks, $itemId, 'p1337' ),
			array( $snaks, $itemId, 'P1337' ),
			array( array(), $itemId, 'P1444' ),
			array( array(), new ItemId( 'Q100' ), 'P1337' )
		);
	}

	public function testFindSnaksWithUnknownPropertyLabel_throwsException() {
		$snaksFinder = $this->getDefaultInstance();

		$this->setExpectedException( 'Wikibase\Lib\PropertyLabelNotResolvedException' );

		$snaksFinder->findSnaks( new ItemId( 'Q42' ), 'hedgehog', 'en' );
	}

}
