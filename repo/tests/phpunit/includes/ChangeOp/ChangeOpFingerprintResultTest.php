<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use PHPUnit\Framework\TestCase;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Repo\ChangeOp\ChangeOpFingerprintResult;
use Wikibase\Repo\ChangeOp\ChangeOpResult;
use Wikibase\Repo\ChangeOp\ChangeOpsResult;

/**
 * @covers \Wikibase\Repo\ChangeOp\ChangeOpFingerprintResult
 *
 * @group Wikibase
 * @group ChangeOp
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpFingerprintResultTest extends TestCase {

	public function setUp(): void {
		$this->innerChangeOpResult = $this->createMock( ChangeOpsResult::class );
	}

	public function testGetChangeOpsResults() {
		$changeOpResults = [
			$this->createMock( ChangeOpResult::class ),
			$this->createMock( ChangeOpsResult::class )
		];

		$this->innerChangeOpResult->method( 'getChangeOpsResults' )
			->willReturn( $changeOpResults );

		$fingerprintChangeOpResults = new ChangeOpFingerprintResult( $this->innerChangeOpResult );
		$this->assertEquals( $changeOpResults, $fingerprintChangeOpResults->getChangeOpsResults() );
	}

	public function testGetEntityId() {
		$entityId = $this->createMock( EntityId::class );

		$this->innerChangeOpResult->method( 'getEntityId' )
			->willReturn( $entityId );

		$fingerprintChangeOpResult = new ChangeOpFingerprintResult( $this->innerChangeOpResult );
		$this->assertEquals( $entityId, $fingerprintChangeOpResult->getEntityId() );
	}

	public function testIsEntityChanged() {
		$this->innerChangeOpResult->method( 'isEntityChanged' )
			->willReturn( true );

		$fingerprintChangeOpResult = new ChangeOpFingerprintResult( $this->innerChangeOpResult );
		$this->assertTrue( $fingerprintChangeOpResult->isEntityChanged() );
	}

	public function testValidate() {
		$validateResult = Result::newSuccess();

		$this->innerChangeOpResult->method( 'validate' )
			->willReturn( $validateResult );

		$fingerprintChangeOpResult = new ChangeOpFingerprintResult( $this->innerChangeOpResult );
		$this->assertSame( $validateResult, $fingerprintChangeOpResult->validate() );
	}

}
