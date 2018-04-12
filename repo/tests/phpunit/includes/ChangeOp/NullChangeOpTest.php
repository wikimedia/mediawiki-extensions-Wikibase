<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use PHPUnit4And6Compat;
use PHPUnit_Framework_MockObject_MockObject;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Repo\ChangeOp\NullChangeOp;

/**
 * @covers Wikibase\Repo\ChangeOp\NullChangeOp
 *
 * @group Wikibase
 * @license GPL-2.0-or-later
 */
class NullChangeOpTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	public function testReturnsValidResult_WhenValidatesEntityDocument() {
		/** @var EntityDocument $entityDocument */
		$entityDocument = $this->getMock( EntityDocument::class );
		$nullChangeOp = new NullChangeOp();

		$result = $nullChangeOp->validate( $entityDocument );

		$this->assertTrue( $result->isValid() );
	}

	public function testDoesNotCallAnyMethodOnEntity_WhenApplied() {
		/** @var EntityDocument|PHPUnit_Framework_MockObject_MockObject $entityDocument */
		$entityDocument = $this->getMock( EntityDocument::class );
		$nullChangeOp = new NullChangeOp();

		$this->expectNoMethodWillBeEverCalledOn( $entityDocument );
		$nullChangeOp->apply( $entityDocument );
	}

	private function expectNoMethodWillBeEverCalledOn( PHPUnit_Framework_MockObject_MockObject $entityMock ) {
		$entityMock->expects( $this->never() )->method( self::anything() );
	}

	public function testGetActions() {
		$changeOp = new NullChangeOp();

		$this->assertEmpty( $changeOp->getActions() );
	}

}
