<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use InvalidArgumentException;
use PHPUnit4And6Compat;
use ParserOutput;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Repo\ParserOutput\EntityParserOutputDataUpdater;
use Wikibase\Repo\ParserOutput\EntityParserOutputDataUpdaterCollection;
use Wikibase\Repo\ParserOutput\ParserOutputDataUpdater;

/**
 * @covers Wikibase\Repo\ParserOutput\EntityParserOutputDataUpdater
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class EntityParserOutputDataUpdaterCollectionTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	public function testUpdateParserOutput() {
		$entity = new Item();

		$dataUpdater1 = $this->getMock( EntityParserOutputDataUpdater::class );
		$dataUpdater1->expects( $this->once() )
			->method( 'processEntity' )
			->with( $entity );
		$dataUpdater1->expects( $this->once() )
			->method( 'updateParserOutput' );

		$dataUpdater2 = $this->getMock( EntityParserOutputDataUpdater::class );
		$dataUpdater2->expects( $this->once() )
			->method( 'processEntity' )
			->with( $entity );
		$dataUpdater2->expects( $this->once() )
			->method( 'updateParserOutput' );

		$instance = new EntityParserOutputDataUpdaterCollection( new ParserOutput(), [
			$dataUpdater1,
			$dataUpdater2
		] );

		$instance->processEntity( $entity );

		$instance->finish();
	}

	/**
	 * @dataProvider invalidConstructorArgumentProvider
	 */
	public function testGivenInvalidDataUpdater_constructorThrowsException( array $argument ) {
		$this->setExpectedException( InvalidArgumentException::class );
		new EntityParserOutputDataUpdaterCollection( new ParserOutput(), $argument );
	}

	public function invalidConstructorArgumentProvider() {
		return [
			[ [ null ] ],
			[ [ 'notAnObject' ] ],
			[ [ $this->getMock( ParserOutputDataUpdater::class ) ] ],
		];
	}

	public function testProcessEntityDoesNotTriggerGetters() {
		$entity = $this->getMock( Item::class );
		$entity->expects( $this->never() )->method( 'getStatements' );
		$entity->expects( $this->never() )->method( 'getSiteLinkList' );
		$instance = new EntityParserOutputDataUpdaterCollection( new ParserOutput(), [] );
		$instance->processEntity( $entity );
	}

}
