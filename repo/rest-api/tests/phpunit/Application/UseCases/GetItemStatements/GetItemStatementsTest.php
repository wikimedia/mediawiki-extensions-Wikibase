<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\GetItemStatements;

use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemStatements\GetItemStatements;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemStatements\GetItemStatementsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemStatements\GetItemStatementsValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestItemRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Application\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;
use Wikibase\Repo\RestApi\Domain\Services\ItemStatementsRetriever;
use Wikibase\Repo\Tests\RestApi\Domain\ReadModel\NewStatementReadModel;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetItemStatements\GetItemStatements
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemStatementsTest extends TestCase {

	/**
	 * @var Stub|GetLatestItemRevisionMetadata
	 */
	private $getRevisionMetadata;

	/**
	 * @var Stub|ItemStatementsRetriever
	 */
	private $statementsRetriever;

	protected function setUp(): void {
		parent::setUp();

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

	public function testGivenPropertyIdInRequest_retrievesOnlyRequestedStatements(): void {
		$requestedProperty = 'P123';
		$itemId = new ItemId( 'Q123' );

		$expectedStatements = $this->createStub( StatementList::class );
		$this->statementsRetriever = $this->createMock( ItemStatementsRetriever::class );
		$this->statementsRetriever->expects( $this->once() )
			->method( 'getStatements' )
			->with( $itemId, new NumericPropertyId( $requestedProperty ) )
			->willReturn( $expectedStatements );

		$this->getRevisionMetadata = $this->createStub( GetLatestItemRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )->willReturn( [ 123, '20230111070707' ] );

		$response = $this->newUseCase()->execute(
			new GetItemStatementsRequest( $itemId->getSerialization(), $requestedProperty )
		);

		$this->assertSame( $expectedStatements, $response->getStatements() );
	}

	public function testGivenInvalidItemId_throwsException(): void {
		try {
			$this->newUseCase()->execute( new GetItemStatementsRequest( 'X321' ) );

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_ITEM_ID, $e->getErrorCode() );
			$this->assertSame( 'Not a valid item ID: X321', $e->getErrorMessage() );
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
			new GetItemStatementsValidator( new ItemIdValidator() ),
			$this->statementsRetriever,
			$this->getRevisionMetadata
		);
	}

}
