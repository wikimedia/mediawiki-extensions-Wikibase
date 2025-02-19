<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Application\UseCases\GetItemStatements;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemStatements\GetItemStatements;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemStatements\GetItemStatementsRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemStatements\GetItemStatementsValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetLatestItemRevisionMetadata;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseException;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\StatementList;
use Wikibase\Repo\Domains\Crud\Domain\Services\ItemStatementsRetriever;
use Wikibase\Repo\Tests\Domains\Crud\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\Domains\Crud\Domain\ReadModel\NewStatementReadModel;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemStatements\GetItemStatements
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemStatementsTest extends TestCase {

	private GetItemStatementsValidator $requestValidator;
	private GetLatestItemRevisionMetadata $getRevisionMetadata;
	private ItemStatementsRetriever $statementsRetriever;

	protected function setUp(): void {
		parent::setUp();

		$this->requestValidator = new TestValidatingRequestDeserializer();
		$this->getRevisionMetadata = $this->createStub( GetLatestItemRevisionMetadata::class );
		$this->statementsRetriever = $this->createStub( ItemStatementsRetriever::class );
	}

	public function testGetItemStatements(): void {
		$itemId = new ItemId( 'Q123' );
		$revisionId = 987;
		$lastModified = '20201111070707';
		$statements = new StatementList(
			NewStatementReadModel::forProperty( 'P123' )
				->withValue( 'potato' )
				->withGuid( 'Q42$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' )
				->build(),
			NewStatementReadModel::someValueFor( 'P321' )
				->withGuid( 'Q42$BBBBBBBB-BBBB-CCCC-DDDD-EEEEEEEEEEEE' )
				->build()
		);

		$this->getRevisionMetadata = $this->createStub( GetLatestItemRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )->willReturn( [ $revisionId, $lastModified ] );

		$this->statementsRetriever = $this->createMock( ItemStatementsRetriever::class );
		$this->statementsRetriever->expects( $this->once() )
			->method( 'getStatements' )
			->with( $itemId )
			->willReturn( $statements );

		$response = $this->newUseCase()->execute(
			new GetItemStatementsRequest( $itemId->getSerialization() )
		);

		$this->assertSame( $statements, $response->getStatements() );
		$this->assertSame( $revisionId, $response->getRevisionId() );
		$this->assertSame( $lastModified, $response->getLastModified() );
	}

	public function testGivenFilterPropertyId_retrievesOnlyRequestedStatements(): void {
		$filterPropertyId = 'P123';
		$itemId = new ItemId( 'Q123' );

		$expectedStatements = $this->createStub( StatementList::class );
		$this->statementsRetriever = $this->createMock( ItemStatementsRetriever::class );
		$this->statementsRetriever->expects( $this->once() )
			->method( 'getStatements' )
			->with( $itemId, new NumericPropertyId( $filterPropertyId ) )
			->willReturn( $expectedStatements );

		$this->getRevisionMetadata = $this->createStub( GetLatestItemRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )->willReturn( [ 123, '20230111070707' ] );

		$response = $this->newUseCase()->execute(
			new GetItemStatementsRequest( $itemId->getSerialization(), $filterPropertyId )
		);

		$this->assertSame( $expectedStatements, $response->getStatements() );
	}

	public function testGivenInvalidRequest_throwsException(): void {
		$request = $this->createStub( GetItemStatementsRequest::class );
		$expectedError = $this->createStub( UseCaseError::class );

		$this->requestValidator = $this->createMock( GetItemStatementsValidator::class );
		$this->requestValidator->expects( $this->once() )
			->method( 'validateAndDeserialize' )
			->with( $request )
			->willThrowException( $expectedError );

		try {
			$this->newUseCase()->execute( $request );

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedError, $e );
		}
	}

	public function testGivenItemNotFoundOrRedirect_throws(): void {
		$expectedException = $this->createStub( UseCaseException::class );

		$this->getRevisionMetadata = $this->createStub( GetLatestItemRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )
			->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute( new GetItemStatementsRequest( 'Q123' ) );

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	private function newUseCase(): GetItemStatements {
		return new GetItemStatements(
			$this->requestValidator,
			$this->statementsRetriever,
			$this->getRevisionMetadata
		);
	}

}
