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
		$parserOutput = new ParserOutput();

		$dataUpdater1 = $this->getMock( EntityParserOutputDataUpdater::class );
		$dataUpdater1->expects( $this->once() )
			->method( 'processEntity' )
			->with( $entity );
		$dataUpdater1->expects( $this->once() )
			->method( 'updateParserOutput' )
			->with( $parserOutput );

		$dataUpdater2 = $this->getMock( EntityParserOutputDataUpdater::class );
		$dataUpdater2->expects( $this->once() )
			->method( 'processEntity' )
			->with( $entity );
		$dataUpdater2->expects( $this->once() )
			->method( 'updateParserOutput' )
			->with( $parserOutput );

		$instance = new EntityParserOutputDataUpdaterCollection( $parserOutput, [
			$dataUpdater1,
			$dataUpdater2
		] );

		$instance->updateParserOutput( $entity );
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

}
