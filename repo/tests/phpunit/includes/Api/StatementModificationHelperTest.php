<?php

namespace Wikibase\Repo\Tests\Api;

use ApiMain;
use DataValues\StringValue;
use MediaWikiIntegrationTestCase;
use RuntimeException;
use ValueValidators\Result;
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
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpException;
use Wikibase\Repo\SnakFactory;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Api\StatementModificationHelper
 *
 * @group Wikibase
 * @group WikibaseAPI
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class StatementModificationHelperTest extends MediaWikiIntegrationTestCase {

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
		$errorReporter = $this->newApiErrorReporter();
		$helper = $this->getNewInstance( $errorReporter );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'invalid-entity-id' );
		$helper->getEntityIdFromString( $invalidEntityIdString );
	}

	public function testCreateSummary() {
		$helper = $this->getNewInstance();
		$customSummary = 'I did it!';

		$summary = $helper->createSummary(
			[ 'summary' => $customSummary ],
			$this->createCreateClaimApiModule()
		);
		$this->assertSame( 'wbcreateclaim', $summary->getMessageKey() );
		$this->assertEquals( $customSummary, $summary->getUserSummary() );

		$summary = $helper->createSummary(
			[],
			$this->createCreateClaimApiModule()
		);
		$this->assertSame( 'wbcreateclaim', $summary->getMessageKey() );
		$this->assertNull( $summary->getUserSummary() );
	}

	private function createCreateClaimApiModule() {
		$apiMain = new ApiMain();
		$apiHelperFactory = WikibaseRepo::getApiHelperFactory();
		$changeOpFactoryProvider = WikibaseRepo::getChangeOpFactoryProvider();

		$modificationHelper = new StatementModificationHelper(
			WikibaseRepo::getSnakFactory(),
			WikibaseRepo::getEntityIdParser(),
			WikibaseRepo::getStatementGuidValidator(),
			$apiHelperFactory->getErrorReporter( $apiMain )
		);

		return new CreateClaim(
			$apiMain,
			'wbcreateclaim',
			$changeOpFactoryProvider->getStatementChangeOpFactory(),
			$apiHelperFactory->getErrorReporter( $apiMain ),
			$modificationHelper,
			function ( $module ) use ( $apiHelperFactory ) {
				return $apiHelperFactory->getResultBuilder( $module );
			},
			function ( $module ) use ( $apiHelperFactory ) {
				return $apiHelperFactory->getEntitySavingHelper( $module );
			},
			false,
			[ 'mainItem' => 'Q42' ]
		);
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
		$entity = $this->createMock( EntityDocument::class );
		$errorReporter = $this->newApiErrorReporter();
		$helper = $this->getNewInstance( $errorReporter );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'no-such-claim' );
		$helper->getStatementFromEntity( 'foo', $entity );
	}

	public function testGetStatementFromEntity_reportsErrorForUnknownStatementGuid() {
		$entity = new Item();
		$errorReporter = $this->newApiErrorReporter();
		$helper = $this->getNewInstance( $errorReporter );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'no-such-claim' );
		$helper->getStatementFromEntity( 'unknown', $entity );
	}

	public function testApplyChangeOp_validatesAndAppliesChangeOp() {
		$changeOp = $this->createMock( ChangeOp::class );

		$changeOp->expects( $this->once() )
			->method( 'validate' )
			->willReturn( Result::newSuccess() );

		$changeOp->expects( $this->once() )
			->method( 'apply' );

		$helper = $this->getNewInstance();
		$helper->applyChangeOp( $changeOp, new Item() );
	}

	public function testApplyChangeOp_reportsErrorForInvalidChangeOp() {
		$errorReporter = $this->newApiErrorReporter();
		$helper = $this->getNewInstance( $errorReporter );

		$changeOp = $this->createMock( ChangeOp::class );

		$changeOp->method( 'validate' )
			->willReturn( Result::newError( [] ) );

		$changeOp->expects( $this->never() )
			->method( 'apply' );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'modification-failed' );
		$helper->applyChangeOp( $changeOp, new Item() );
	}

	public function testApplyChangeOp_reportsErrorWhenApplyFails() {
		$errorReporter = $this->newApiErrorReporter();
		$helper = $this->getNewInstance( $errorReporter );

		$changeOp = $this->createMock( ChangeOp::class );

		$changeOp->method( 'validate' )
			->willReturn( Result::newSuccess() );

		$changeOp->method( 'apply' )
			->willThrowException( new ChangeOpException() );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'modification-failed' );
		$helper->applyChangeOp( $changeOp, new Item() );
	}

	/**
	 * @param ApiErrorReporter|null $errorReporter
	 *
	 * @return StatementModificationHelper
	 */
	private function getNewInstance( ApiErrorReporter $errorReporter = null ) {
		$entityIdParser = new ItemIdParser();

		return new StatementModificationHelper(
			$this->createMock( SnakFactory::class ),
			$entityIdParser,
			new StatementGuidValidator( $entityIdParser ),
			$errorReporter ?: $this->newApiErrorReporter()
		);
	}

	/**
	 * @return ApiErrorReporter
	 */
	private function newApiErrorReporter() {
		$errorReporter = $this->createMock( ApiErrorReporter::class );

		$errorReporter->method( 'dieException' )
			->willReturnCallback( function ( $exception, $message ) {
				throw new RuntimeException( $message );
			} );

		$errorReporter->method( 'dieError' )
			->willReturnCallback( function ( $description, $message ) {
				throw new RuntimeException( $message );
			} );

		return $errorReporter;
	}

}
