<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use PHPUnit\Framework\TestCase;
use ValueValidators\Error;
use ValueValidators\Result;
use ValueValidators\ValueValidator;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\ChangeOp\ChangeOpFingerprintResult;
use Wikibase\Repo\ChangeOp\ChangeOpResult;
use Wikibase\Repo\ChangeOp\ChangeOpsResult;
use Wikibase\Repo\Validators\TermValidatorFactory;

/**
 * @covers \Wikibase\Repo\ChangeOp\ChangeOpFingerprintResult
 *
 * @group Wikibase
 * @group ChangeOp
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpFingerprintResultTest extends TestCase {

	/** @var TermValidatorFactory */
	private $termValidatorFactory;

	/** @var ChangeOpsResult */
	private $innerChangeOpResult;

	/** @var ItemId */
	private $itemId;

	protected function setUp(): void {
		$this->itemId = ItemId::newFromNumber( 123 );
		$this->innerChangeOpResult = $this->createMock( ChangeOpsResult::class );
		$this->innerChangeOpResult->method( 'getEntityId' )->willReturn( $this->itemId );
		$this->termValidatorFactory = $this->createMock( TermValidatorFactory::class );
	}

	public function testGetChangeOpsResults() {
		$changeOpResults = [
			$this->createMock( ChangeOpResult::class ),
			$this->createMock( ChangeOpsResult::class ),
		];

		$this->innerChangeOpResult->method( 'getChangeOpsResults' )
			->willReturn( $changeOpResults );

		$fingerprintChangeOpResults = new ChangeOpFingerprintResult(
			$this->innerChangeOpResult,
			$this->termValidatorFactory
		);

		$this->assertEquals( $changeOpResults, $fingerprintChangeOpResults->getChangeOpsResults() );
	}

	public function testGetEntityId() {
		$fingerprintChangeOpResult = new ChangeOpFingerprintResult(
			$this->innerChangeOpResult,
			$this->termValidatorFactory
		);

		$this->assertEquals( $this->itemId, $fingerprintChangeOpResult->getEntityId() );
	}

	public function testIsEntityChanged() {
		$this->innerChangeOpResult->method( 'isEntityChanged' )
			->willReturn( true );

		$fingerprintChangeOpResult = new ChangeOpFingerprintResult(
			$this->innerChangeOpResult,
			$this->termValidatorFactory
		);

		$this->assertTrue( $fingerprintChangeOpResult->isEntityChanged() );
	}

	public function testValidate() {
		$fingerprintUniquenessValidatorMock = $this->createMock( ValueValidator::class );
		$fingerprintUniquenessValidatorMock->method( 'validate' )->willReturn( Result::newSuccess() );
		$this->termValidatorFactory->method( 'getFingerprintUniquenessValidator' )->willReturn(
			$fingerprintUniquenessValidatorMock
		);

		$this->innerChangeOpResult->method( 'validate' )
			->willReturn( Result::newSuccess() );

		$fingerprintChangeOpResult = new ChangeOpFingerprintResult(
			$this->innerChangeOpResult,
			$this->termValidatorFactory
		);

		$this->assertTrue( $fingerprintChangeOpResult->validate()->isValid() );
	}

	public function testValidate_whenUniquenessValidationFails() {
		$fingerprintUniquenessValidatorMock = $this->createMock( ValueValidator::class );
		$fingerprintUniquenessValidatorMock->method( 'validate' )->willReturn( Result::newError( [
			Error::newError( 'foo' ),
		] ) );
		$this->termValidatorFactory->method( 'getFingerprintUniquenessValidator' )->willReturn(
			$fingerprintUniquenessValidatorMock
		);

		$this->innerChangeOpResult->method( 'validate' )
			->willReturn( Result::newSuccess() );

		$fingerprintChangeOpResult = new ChangeOpFingerprintResult(
			$this->innerChangeOpResult,
			$this->termValidatorFactory
		);

		$result = $fingerprintChangeOpResult->validate();
		$this->assertFalse( $result->isValid() );
		$this->assertEquals( [ Error::newError( 'foo' ) ], $result->getErrors() );
	}

	public function testValidate_whenInnerChangeOpsValidationFails() {
		$fingerprintUniquenessValidatorMock = $this->createMock( ValueValidator::class );
		$fingerprintUniquenessValidatorMock->method( 'validate' )->willReturn( Result::newSuccess() );
		$this->termValidatorFactory->method( 'getFingerprintUniquenessValidator' )->willReturn(
			$fingerprintUniquenessValidatorMock
		);

		$this->innerChangeOpResult->method( 'validate' )
			->willReturn( Result::newError( [ Error::newError( 'bar' ) ] ) );

		$fingerprintChangeOpResult = new ChangeOpFingerprintResult(
			$this->innerChangeOpResult,
			$this->termValidatorFactory
		);

		$result = $fingerprintChangeOpResult->validate();
		$this->assertFalse( $result->isValid() );
		$this->assertEquals( [ Error::newError( 'bar' ) ], $result->getErrors() );
	}

	public function testValidate_whenInnerChangeOpsAndUniquenessValidationFail() {
		$fingerprintUniquenessValidatorMock = $this->createMock( ValueValidator::class );
		$fingerprintUniquenessValidatorMock->method( 'validate' )->willReturn( Result::newError( [
			Error::newError( 'foo' ),
		] ) );
		$this->termValidatorFactory->method( 'getFingerprintUniquenessValidator' )->willReturn(
			$fingerprintUniquenessValidatorMock
		);

		$this->innerChangeOpResult->method( 'validate' )
			->willReturn( Result::newError( [ Error::newError( 'bar' ) ] ) );

		$fingerprintChangeOpResult = new ChangeOpFingerprintResult(
			$this->innerChangeOpResult,
			$this->termValidatorFactory
		);

		$result = $fingerprintChangeOpResult->validate();
		$this->assertFalse( $result->isValid() );
		$this->assertEquals( [ Error::newError( 'bar' ), Error::newError( 'foo' ) ], $result->getErrors() );
	}

}
