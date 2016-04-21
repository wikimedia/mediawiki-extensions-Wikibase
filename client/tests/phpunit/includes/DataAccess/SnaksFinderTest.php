<?php

namespace Wikibase\Client\Tests\DataAccess;

use DataValues\StringValue;
use Wikibase\Client\DataAccess\SnaksFinder;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementListProvider;

/**
 * @covers Wikibase\Client\DataAccess\SnaksFinder
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseDataAccess
 * @group PropertyParserFunctionTest
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 */
class SnaksFinderTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider findSnaksProvider
	 */
	public function testFindSnaks(
		array $expected,
		StatementListProvider $statementListProvider,
		PropertyId $propertyId,
		array $acceptableRanks = null
	) {
		$snaksFinder = new SnaksFinder();

		$snakList = $snaksFinder->findSnaks( $statementListProvider, $propertyId, $acceptableRanks );
		$this->assertEquals( $expected, $snakList );
	}

	public function findSnaksProvider() {
		$propertyId = new PropertyId( 'P1337' );

		$statement1 = new Statement( new PropertyValueSnak(
			$propertyId,
			new StringValue( 'a kitten!' )
		) );
		$statement1->setGuid( 'Q42$1' );

		$statement2 = new Statement( new PropertyValueSnak(
			$propertyId,
			new StringValue( 'two kittens!!' )
		) );
		$statement2->setGuid( 'Q42$2' );

		// A Statement with a lower rank which should not affect the output
		$statement3 = new Statement( new PropertyValueSnak(
			$propertyId,
			new StringValue( 'three kittens!!!' )
		) );
		$statement3->setGuid( 'Q42$3' );
		$statement3->setRank( Statement::RANK_DEPRECATED );

		$item = new Item( new ItemId( 'Q42' ) );
		$item->getStatements()->addStatement( $statement1 );
		$item->getStatements()->addStatement( $statement2 );
		$item->getStatements()->addStatement( $statement3 );

		$snaksNormal = array(
			new PropertyValueSnak( $propertyId, new StringValue( 'a kitten!' ) ),
			new PropertyValueSnak( $propertyId, new StringValue( 'two kittens!!' ) )
		);
		$snakDeprecated = array( new PropertyValueSnak( $propertyId, new StringValue( 'three kittens!!!' ) ) );

		return array(
			array( $snaksNormal, $item, new PropertyId( 'P1337' ) ),
			array( [], $item, new PropertyId( 'P90001' ) ),
			array(
				$snakDeprecated,
				$item,
				new PropertyId( 'P1337' ),
				array( Statement::RANK_DEPRECATED )
			),
		);
	}

}
