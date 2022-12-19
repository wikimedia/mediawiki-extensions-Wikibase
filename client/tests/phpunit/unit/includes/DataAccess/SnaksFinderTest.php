<?php

namespace Wikibase\Client\Tests\Unit\DataAccess;

use DataValues\StringValue;
use Wikibase\Client\DataAccess\SnaksFinder;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementListProvider;

/**
 * @covers \Wikibase\Client\DataAccess\SnaksFinder
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseDataAccess
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 */
class SnaksFinderTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider findSnaksProvider
	 */
	public function testFindSnaks(
		array $expected,
		StatementListProvider $statementListProvider,
		NumericPropertyId $propertyId,
		array $acceptableRanks = null
	) {
		$snaksFinder = new SnaksFinder();

		$snakList = $snaksFinder->findSnaks( $statementListProvider, $propertyId, $acceptableRanks );
		$this->assertEquals( $expected, $snakList );
	}

	public function findSnaksProvider() {
		$propertyId = new NumericPropertyId( 'P1337' );

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

		$snaksNormal = [
			new PropertyValueSnak( $propertyId, new StringValue( 'a kitten!' ) ),
			new PropertyValueSnak( $propertyId, new StringValue( 'two kittens!!' ) ),
		];
		$snakDeprecated = [ new PropertyValueSnak( $propertyId, new StringValue( 'three kittens!!!' ) ) ];

		return [
			[ $snaksNormal, $item, new NumericPropertyId( 'P1337' ) ],
			[ [], $item, new NumericPropertyId( 'P90001' ) ],
			[
				$snakDeprecated,
				$item,
				new NumericPropertyId( 'P1337' ),
				[ Statement::RANK_DEPRECATED ],
			],
		];
	}

}
