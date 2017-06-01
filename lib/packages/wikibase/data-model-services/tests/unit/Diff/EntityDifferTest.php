<?php

namespace Wikibase\DataModel\Services\Tests\Diff;

use InvalidArgumentException;
use RuntimeException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Diff\ItemDiff;
use Wikibase\DataModel\Services\Fixtures\EntityOfUnknownType;
use Wikibase\DataModel\Services\Diff\EntityDiffer;

/**
 * @covers Wikibase\DataModel\Services\Diff\EntityDiffer
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityDifferTest extends \PHPUnit_Framework_TestCase {

	public function testGivenUnknownEntityType_exceptionIsThrown() {
		$differ = new EntityDiffer();

		$this->setExpectedException( RuntimeException::class );
		$differ->diffEntities( new EntityOfUnknownType(), new EntityOfUnknownType() );
	}

	public function testGivenEntitiesWithDifferentTypes_exceptionIsThrown() {
		$differ = new EntityDiffer();

		$this->setExpectedException( InvalidArgumentException::class );
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

		$this->setExpectedException( RuntimeException::class );
		$differ->getConstructionDiff( new EntityOfUnknownType() );
	}

	public function testGivenUnknownEntityType_getDestructionDiffThrowsException() {
		$differ = new EntityDiffer();

		$this->setExpectedException( RuntimeException::class );
		$differ->getDestructionDiff( new EntityOfUnknownType() );
	}

}
