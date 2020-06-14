<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use PHPUnit\Framework\TestCase;
use ValueValidators\Error;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Repo\ChangeOp\ChangeOpResult;
use Wikibase\Repo\ChangeOp\ChangeOpsResult;

/**
 * @covers \Wikibase\Repo\ChangeOp\ChangeOpsResult
 *
 * @group Wikibase
 * @group ChangeOp
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpsResultTest extends TestCase {

	/** @var EntityId */
	private $entityId;

	/** @var ChangeOpResult */
	private $changeOpResult1;

	/** @var ChangeOpResult */
	private $changeOpResult2;

	protected function setUp(): void {
		$this->entityId = $this->createMock( EntityId::class );
		$this->changeOpResult1 = $this->createMock( ChangeOpResult::class );
		$this->changeOpResult2 = $this->createMock( ChangeOpResult::class );
	}

	public function testIsEntityChanged_onAnyChanged() {
		$this->changeOpResult1->expects( $this->once() )
			->method( 'isEntityChanged' )->willReturn( false );
		$this->changeOpResult2->expects( $this->once() )
			->method( 'isEntityChanged' )->willReturn( true );

		$actualResult = $this->getChangeOpsResult()->isEntityChanged();

		$this->assertTrue( $actualResult );
	}

	public function testIsEntityChanged_onNoneChanged() {
		$this->changeOpResult1->expects( $this->once() )
			->method( 'isEntityChanged' )->willReturn( false );
		$this->changeOpResult2->expects( $this->once() )
			->method( 'isEntityChanged' )->willReturn( false );

		$actualResult = $this->getChangeOpsResult()->isEntityChanged();

		$this->assertFalse( $actualResult );
	}

	public function testValidate_onSuccess() {
		$this->changeOpResult1->expects( $this->once() )
			->method( 'validate' )->willReturn( Result::newSuccess() );
		$this->changeOpResult2->expects( $this->once() )
			->method( 'validate' )->willReturn( Result::newSuccess() );

		$expectedResult = Result::newSuccess();

		$actualResult = $this->getChangeOpsResult()->validate();

		$this->assertEquals( $expectedResult, $actualResult );
	}

	public function testValidate_onErrors() {
		$error1 = Error::newError( 'error 1' );
		$error2 = Error::newError( 'error 2' );
		$this->changeOpResult1->expects( $this->once() )
			->method( 'validate' )->willReturn( Result::newError( [ $error1 ] ) );
		$this->changeOpResult2->expects( $this->once() )
			->method( 'validate' )->willReturn( Result::newError( [ $error2 ] ) );

		$expectedResult = Result::newError( [ $error1, $error2 ] );

		$actualResult = $this->getChangeOpsResult()->validate();

		$this->assertEquals( $expectedResult, $actualResult );
	}

	private function getChangeOpsResult() {
		return new ChangeOpsResult(
			$this->entityId,
			[ $this->changeOpResult1, $this->changeOpResult2 ]
		);
	}
}
