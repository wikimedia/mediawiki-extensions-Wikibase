<?php

namespace Wikibase\DataModel\Services\Tests\Diff;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Diff\EntityDiffer;
use Wikibase\DataModel\Services\Diff\ItemDiff;
use Wikibase\DataModel\Services\Fixtures\EntityOfUnknownType;

/**
 * @covers \Wikibase\DataModel\Services\Diff\EntityDiffer
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityDifferTest extends TestCase {

	public function testGivenUnknownEntityType_exceptionIsThrown() {
		$differ = new EntityDiffer();

		$this->expectException( RuntimeException::class );
		$differ->diffEntities( new EntityOfUnknownType(), new EntityOfUnknownType() );
	}

	public function testGivenEntitiesWithDifferentTypes_exceptionIsThrown() {
		$differ = new EntityDiffer();

		$this->expectException( InvalidArgumentException::class );
		$differ->diffEntities( new Item(), Property::newFromType( 'string' ) );
	}

	public function testGivenTwoEmptyItems_emptyItemDiffIsReturned() {
		$differ = new EntityDiffer();

		$diff = $differ->diffEntities( new Item(), new Item() );

		$this->assertInstanceOf( ItemDiff::class, $diff );
		$this->assertTrue( $diff->isEmpty() );
	}

	public function testGivenUnknownEntityType_getConstructionDiffThrowsException() {
		$differ = new EntityDiffer();

		$this->expectException( RuntimeException::class );
		$differ->getConstructionDiff( new EntityOfUnknownType() );
	}

	public function testGivenUnknownEntityType_getDestructionDiffThrowsException() {
		$differ = new EntityDiffer();

		$this->expectException( RuntimeException::class );
		$differ->getDestructionDiff( new EntityOfUnknownType() );
	}

}
