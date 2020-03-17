<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use PHPUnit\Framework\MockObject\MockObject;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\ChangeOp\NullChangeOp;

/**
 * @covers \Wikibase\Repo\ChangeOp\NullChangeOp
 *
 * @group Wikibase
 * @license GPL-2.0-or-later
 */
class NullChangeOpTest extends \PHPUnit\Framework\TestCase {

	public function testReturnsValidResult_WhenValidatesEntityDocument() {
		/** @var EntityDocument $entityDocument */
		$entityDocument = $this->createMock( EntityDocument::class );
		$nullChangeOp = new NullChangeOp();

		$result = $nullChangeOp->validate( $entityDocument );

		$this->assertTrue( $result->isValid() );
	}

	public function testDoesNotChangeEntity_WhenApplied() {
		/** @var EntityDocument|MockObject $entityDocument */
		$entityDocument = new Item( ItemId::newFromNumber( 123 ) );
		$targetEntityDocument = $entityDocument->copy();

		$nullChangeOp = new NullChangeOp();

		$nullChangeOpResult = $nullChangeOp->apply( $entityDocument );

		$this->assertFalse( $nullChangeOpResult->isEntityChanged() );
		$this->assertEquals(
			$entityDocument,
			$targetEntityDocument
		);
	}

	public function testGetActions() {
		$changeOp = new NullChangeOp();

		$this->assertSame( [], $changeOp->getActions() );
	}

}
