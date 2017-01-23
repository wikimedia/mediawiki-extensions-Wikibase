<?php

namespace Wikibase\Repo\Tests\Api;

use ApiMain;
use DataValues\StringValue;
use ApiUsageException;
use StatusValue;
use ValueValidators\Result;
use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOpException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Statement\StatementGuidValidator;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\Api\ApiErrorReporter;
use Wikibase\Repo\Api\CreateClaim;
use Wikibase\Repo\Api\StatementModificationHelper;
use Wikibase\Repo\SnakFactory;

/**
 * @covers Wikibase\Repo\Api\StatementModificationHelper
 *
 * @group Wikibase
 * @group WikibaseAPI
 * @group Database
 *
 * @license GPL-2.0+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class StatementModificationHelperTest extends \MediaWikiTestCase {

	public function testValidGetEntityIdFromString() {
		$validEntityIdString = 'q55';

		$helper = $this->getNewInstance();
		$this->assertInstanceOf(
			EntityId::class,
			$helper->getEntityIdFromString( $validEntityIdString )
		);
	}

	public function testInvalidGetEntityIdFromString() {
		$invalidEntityIdString = 'no!';
		$errorReporter = $this->newApiErrorReporterWithException( 'invalid-entity-id' );
		$helper = $this->getNewInstance( $errorReporter );

		try {
			$helper->getEntityIdFromString( $invalidEntityIdString );
		} catch ( ApiUsageException $ex ) {
			$this->assertErrorCode( 'invalid-entity-id', $ex );
		}
	}

	public function testCreateSummary() {
		$apiMain = new ApiMain();
		$helper = $this->getNewInstance();
		$customSummary = 'I did it!';

		$summary = $helper->createSummary(
			array( 'summary' => $customSummary ),
			new CreateClaim( $apiMain, 'wbcreateclaim' )
		);
		$this->assertEquals( 'wbcreateclaim', $summary->getModuleName() );
		$this->assertEquals( $customSummary, $summary->getUserSummary() );

		$summary = $helper->createSummary(
			array(),
			new CreateClaim( $apiMain, 'wbcreateclaim' )
		);
		$this->assertEquals( 'wbcreateclaim', $summary->getModuleName() );
		$this->assertNull( $summary->getUserSummary() );
	}

	public function testGetStatementFromEntity() {
		$helper = $this->getNewInstance();

		$item = new Item();

		$snak = new PropertyValueSnak( 2754236, new StringValue( 'test' ) );
		$statement = new Statement( $snak );
		$statement->setGuid( 'q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0F' );
		$item->getStatements()->addStatement( $statement );
		$guid = $statement->getGuid();

		$this->assertEquals( $statement, $helper->getStatementFromEntity( $guid, $item ) );
	}

	public function testGetStatementFromEntity_reportsErrorForInvalidEntity() {
		$entity = $this->getMock( EntityDocument::class );
		$errorReporter = $this->newApiErrorReporterWithError( 'no-such-claim' );
		$helper = $this->getNewInstance( $errorReporter );

		try {
			$helper->getStatementFromEntity( 'foo', $entity );
		} catch ( ApiUsageException $ex ) {
			$this->assertErrorCode( 'no-such-claim', $ex );
		}
	}

	public function testGetStatementFromEntity_reportsErrorForUnknownStatementGuid() {
		$entity = new Item();
		$errorReporter = $this->newApiErrorReporterWithError( 'no-such-claim' );
		$helper = $this->getNewInstance( $errorReporter );

		try {
			$helper->getStatementFromEntity( 'unknown', $entity );
		} catch ( ApiUsageException $ex ) {
			$this->assertErrorCode( 'no-such-claim', $ex );
		}
	}

	public function testApplyChangeOp_validatesAndAppliesChangeOp() {
		$entity = new Item();

		$changeOp = $this->getMock( ChangeOp::class );
		$changeOp->expects( $this->once() )
			->method( 'validate' )
			->with( $entity )
			->will( $this->returnValue( Result::newSuccess() ) );
		$changeOp->expects( $this->once() )
			->method( 'apply' )
			->with( $entity );

		$helper = $this->getNewInstance();
		$helper->applyChangeOp( $changeOp, $entity );
	}

	public function testApplyChangeOp_reportsErrorForInvalidChangeOp() {
		$entity = new Item();
		$errorReporter = $this->newApiErrorReporterWithException( 'modification-failed' );
		$helper = $this->getNewInstance( $errorReporter );

		$changeOp = $this->getMock( ChangeOp::class );
		$changeOp->expects( $this->once() )
			->method( 'validate' )
			->with( $entity )
			->will( $this->returnValue( Result::newError( [] ) ) );
		$changeOp->expects( $this->never() )
			->method( 'apply' );

		try {
			$helper->applyChangeOp( $changeOp, $entity );
		} catch ( ApiUsageException $ex ) {
			$this->assertErrorCode( 'modification-failed', $ex );
		}
	}

	public function testApplyChangeOp_reportsErrorWhenApplyFails() {
		$entity = new Item();
		$errorReporter = $this->newApiErrorReporterWithException( 'modification-failed' );
		$helper = $this->getNewInstance( $errorReporter );

		$changeOp = $this->getMock( ChangeOp::class );
		$changeOp->expects( $this->once() )
			->method( 'validate' )
			->with( $entity )
			->will( $this->returnValue( Result::newSuccess() ) );
		$changeOp->expects( $this->once() )
			->method( 'apply' )
			->with( $entity )
			->will( $this->throwException( new ChangeOpException() ) );

		try {
			$helper->applyChangeOp( $changeOp, $entity );
		} catch ( ApiUsageException $ex ) {
			$this->assertErrorCode( 'modification-failed', $ex );
		}
	}

	/**
	 * @param ApiErrorReporter|null $errorReporter
	 *
	 * @return StatementModificationHelper
	 */
	private function getNewInstance( ApiErrorReporter $errorReporter = null ) {
		$entityIdParser = new ItemIdParser();

		return new StatementModificationHelper(
			$this->getMockBuilder( SnakFactory::class )->disableOriginalConstructor()->getMock(),
			$entityIdParser,
			new StatementGuidValidator( $entityIdParser ),
			$errorReporter ?: $this->newApiErrorReporter()
		);
	}

	/**
	 * @return ApiErrorReporter
	 */
	private function newApiErrorReporter() {
		$errorReporter = $this->getMockBuilder( ApiErrorReporter::class )
			->disableOriginalConstructor()
			->getMock();

		$errorReporter->expects( $this->never() )
			->method( $this->anything() );

		return $errorReporter;
	}

	/**
	 * @param string $expectedMessage
	 *
	 * @return ApiErrorReporter
	 */
	private function newApiErrorReporterWithException( $expectedMessage ) {
		$errorReporter = $this->getMockBuilder( ApiErrorReporter::class )
			->disableOriginalConstructor()
			->getMock();

		$errorReporter->method( 'dieException' )
			->will( $this->throwException(
				new ApiUsageException( null, StatusValue::newFatal( $expectedMessage ) )
			) );

		return $errorReporter;
	}

	/**
	 * @param string $expectedMessage
	 *
	 * @return ApiErrorReporter
	 */
	private function newApiErrorReporterWithError( $expectedMessage ) {
		$errorReporter = $this->getMockBuilder( ApiErrorReporter::class )
			->disableOriginalConstructor()
			->getMock();

		$errorReporter->method( 'dieError' )
			->will( $this->throwException(
				new ApiUsageException( null, StatusValue::newFatal( $expectedMessage ) )
			) );

		return $errorReporter;
	}

	/**
	 * @param string $expectedMessage
	 * @param ApiUsageException $actualException
	 */
	private function assertErrorCode( $expectedMessage, ApiUsageException $actualException ) {
		$this->assertTrue( $actualException->getStatusValue()->hasMessage( $expectedMessage ) );
	}

}
