<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use PHPUnit\Framework\TestCase;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpFingerprint;
use Wikibase\Repo\ChangeOp\ChangeOpFingerprintResult;
use Wikibase\Repo\ChangeOp\ChangeOps;
use Wikibase\Repo\ChangeOp\ChangeOpsResult;
use Wikibase\Repo\ChangeOp\NullChangeOp;
use Wikibase\Repo\Validators\TermValidatorFactory;

/**
 * @covers \Wikibase\Repo\ChangeOp\ChangeOpFingerprint
 *
 * @group Wikibase
 * @group ChangeOp
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpFingerprintTest extends TestCase {

	/** @var TermValidatorFactory */
	private $termValidatorFactory;

	protected function setUp(): void {
		$this->innerChangeOp = $this->createMock( ChangeOps::class );
		$this->termValidatorFactory = $this->createMock( TermValidatorFactory::class );
	}

	public function testAdd() {
		$nullChangeOp = new NullChangeOp();

		$this->innerChangeOp->expects( $this->once() )
			->method( 'add' )
			->with( $nullChangeOp );

		$fingerprintChangeOp = new ChangeOpFingerprint( $this->innerChangeOp, $this->termValidatorFactory );
		$fingerprintChangeOp->add( $nullChangeOp );
	}

	public function testGetChangeOps() {
		$changeOps = [
			$this->createMock( ChangeOp::class ),
			$this->createMock( ChangeOps::class ),
		];

		$this->innerChangeOp->method( 'getChangeOps' )
			->willReturn( $changeOps );

		$fingerprintChangeOp = new ChangeOpFingerprint( $this->innerChangeOp, $this->termValidatorFactory );
		$this->assertEquals( $changeOps, $fingerprintChangeOp->getChangeOps() );
	}

	public function testValidate() {
		$validateResult = Result::newSuccess();
		$entity = $this->createMock( EntityDocument::class );

		$this->innerChangeOp->method( 'validate' )
			->willReturn( $validateResult );

		$fingerprintChangeOp = new ChangeOpFingerprint( $this->innerChangeOp, $this->termValidatorFactory );
		$this->assertSame( $validateResult, $fingerprintChangeOp->validate( $entity ) );
	}

	public function testGetActions() {
		$actions = [ 'fooAction', 'barAction' ];

		$this->innerChangeOp->method( 'getActions' )
			->willReturn( $actions );

		$fingerprintChangeOp = new ChangeOpFingerprint( $this->innerChangeOp, $this->termValidatorFactory );
		$this->assertEquals( $actions, $fingerprintChangeOp->getActions() );
	}

	public function testApply() {
		$innerChangeOpResult = $this->createMock( ChangeOpsResult::class );
		$innerChangeOpResult->method( 'getChangeOpsResults' )->willReturn( [ $this->createMock( ChangeOps::class ) ] );

		$this->innerChangeOp->method( 'apply' )
			->willReturn( $innerChangeOpResult );

		$fingerprintChangeOp = new ChangeOpFingerprint( $this->innerChangeOp, $this->termValidatorFactory );
		$fingerprintChangeOpResult = $fingerprintChangeOp->apply( $this->createMock( EntityDocument::class ) );

		$this->assertInstanceOf( ChangeOpFingerprintResult::class, $fingerprintChangeOpResult );
		$this->assertSame(
			$innerChangeOpResult->getChangeOpsResults(),
			$fingerprintChangeOpResult->getChangeOpsResults()
		);
	}

}
