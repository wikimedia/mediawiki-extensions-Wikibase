<?php

namespace Wikibase\Test\Entity\Diff;

use Wikibase\DataModel\Entity\Diff\EntityDiffer;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Test\DataModel\Fixtures\EntityOfUnknownType;

/**
 * @covers Wikibase\DataModel\Entity\Diff\EntityDiffer
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityDifferTest extends \PHPUnit_Framework_TestCase {

	public function testGivenUnknownEntityType_exceptionIsThrown() {
		$differ = new EntityDiffer();

		$this->setExpectedException( 'RuntimeException' );
		$differ->diffEntities( new EntityOfUnknownType(), new EntityOfUnknownType() );
	}

	public function testGivenEntitiesWithDifferentTypes_exceptionIsThrown() {
		$differ = new EntityDiffer();

		$this->setExpectedException( 'InvalidArgumentException' );
		$differ->diffEntities( Item::newEmpty(), Property::newFromType( 'string' ) );
	}

	public function testGivenTwoEmptyItems_emptyItemDiffIsReturned() {
		$differ = new EntityDiffer();

		$diff = $differ->diffEntities( Item::newEmpty(), Item::newEmpty() );

		$this->assertInstanceOf( 'Wikibase\DataModel\Entity\Diff\ItemDiff', $diff );
		$this->assertTrue( $diff->isEmpty() );
	}

	public function testGivenUnknownEntityType_getConstructionDiffThrowsException() {
		$differ = new EntityDiffer();

		$this->setExpectedException( 'RuntimeException' );
		$differ->getConstructionDiff( new EntityOfUnknownType() );
	}

	public function testGivenUnknownEntityType_getDestructionDiffThrowsException() {
		$differ = new EntityDiffer();

		$this->setExpectedException( 'RuntimeException' );
		$differ->getDestructionDiff( new EntityOfUnknownType() );
	}

}

