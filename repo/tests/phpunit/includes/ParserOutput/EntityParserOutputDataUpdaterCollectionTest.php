<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use InvalidArgumentException;
use ParserOutput;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Repo\ParserOutput\EntityParserOutputDataUpdaterCollection;
use Wikibase\Repo\ParserOutput\EntityParserOutputUpdater;

/**
 * @covers \Wikibase\Repo\ParserOutput\EntityParserOutputUpdater
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class EntityParserOutputDataUpdaterCollectionTest extends \PHPUnit\Framework\TestCase {

	public function testUpdateParserOutput() {
		$entity = new Item();
		$parserOutput = new ParserOutput();

		$dataUpdater1 = $this->createMock( EntityParserOutputUpdater::class );
		$dataUpdater1->expects( $this->once() )
			->method( 'updateParserOutput' )
			->with( $parserOutput, $entity );

		$dataUpdater2 = $this->createMock( EntityParserOutputUpdater::class );
		$dataUpdater2->expects( $this->once() )
			->method( 'updateParserOutput' )
			->with( $parserOutput, $entity );

		$instance = new EntityParserOutputDataUpdaterCollection( $parserOutput, [
			$dataUpdater1,
			$dataUpdater2,
		] );

		$instance->updateParserOutput( $entity );
	}

	/**
	 * @dataProvider invalidConstructorArgumentProvider
	 */
	public function testGivenInvalidDataUpdater_constructorThrowsException( array $argument ) {
		$this->expectException( InvalidArgumentException::class );
		new EntityParserOutputDataUpdaterCollection( new ParserOutput(), $argument );
	}

	public function invalidConstructorArgumentProvider() {
		return [
			[ [ null ] ],
			[ [ 'notAnObject' ] ],
		];
	}

}
