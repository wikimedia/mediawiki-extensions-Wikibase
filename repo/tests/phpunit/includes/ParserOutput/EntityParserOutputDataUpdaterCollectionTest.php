<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use InvalidArgumentException;
use ParserOutput;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Repo\ParserOutput\EntityParserOutputUpdater;
use Wikibase\Repo\ParserOutput\EntityParserOutputDataUpdaterCollection;

/**
 * @covers \Wikibase\Repo\ParserOutput\EntityParserOutputUpdater
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

		$dataUpdater1 = $this->getMock( EntityParserOutputUpdater::class );
		$dataUpdater1->expects( $this->once() )
			->method( 'updateParserOutput' )
			->with( $parserOutput, $entity );

		$dataUpdater2 = $this->getMock( EntityParserOutputUpdater::class );
		$dataUpdater2->expects( $this->once() )
			->method( 'updateParserOutput' )
			->with( $parserOutput, $entity );

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
		];
	}

}
