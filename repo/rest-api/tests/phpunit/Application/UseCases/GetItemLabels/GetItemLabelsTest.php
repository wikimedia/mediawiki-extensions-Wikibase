<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\GetItemLabels;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemLabels\GetItemLabels;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemLabels\GetItemLabelsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemLabels\GetItemLabelsResponse;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemLabels\GetItemLabelsValidator;
use Wikibase\Repo\RestApi\Application\UseCases\ItemRedirect;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Domain\ReadModel\Label;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Domain\ReadModel\LatestItemRevisionMetadataResult;
use Wikibase\Repo\RestApi\Domain\Services\ItemLabelsRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetItemLabels\GetItemLabels
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemLabelsTest extends TestCase {

	/**
	 * @var MockObject|ItemRevisionMetadataRetriever
	 */
	private $itemRevisionMetadataRetriever;

	/**
	 * @var MockObject|ItemLabelsRetriever
	 */
	private $labelsRetriever;

	protected function setUp(): void {
		parent::setUp();

		$this->itemRevisionMetadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
		$this->labelsRetriever = $this->createStub( ItemLabelsRetriever::class );
	}

	public function testSuccess(): void {
		$labels = new Labels(
			new Label( 'en', 'earth' ),
			new Label( 'ar', 'أرض' ),
		);

		$itemId = new ItemId( 'Q10' );
		$lastModified = '20201111070707';
		$revisionId = 2;

		$this->itemRevisionMetadataRetriever = $this->createMock( ItemRevisionMetadataRetriever::class );
		$this->itemRevisionMetadataRetriever->expects( $this->once() )
			->method( 'getLatestRevisionMetadata' )
			->with( $itemId )
			->willReturn( LatestItemRevisionMetadataResult::concreteRevision( $revisionId, $lastModified ) );

		$this->labelsRetriever = $this->createMock( ItemLabelsRetriever::class );
		$this->labelsRetriever->expects( $this->once() )
			->method( 'getLabels' )
			->with( $itemId )
			->willReturn( $labels );

		$request = new GetItemLabelsRequest( 'Q10' );
		$response = $this->newUseCase()->execute( $request );
		$this->assertEquals( new GetItemLabelsResponse( $labels, $lastModified, $revisionId ), $response );
	}

	public function testGivenInvalidItemId_throws(): void {
		try {
			$this->newUseCase()->execute(
				new GetItemLabelsRequest( 'X321' )
			);

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_ITEM_ID, $e->getErrorCode() );
			$this->assertSame( 'Not a valid item ID: X321', $e->getErrorMessage() );
			$this->assertNull( $e->getErrorContext() );
		}
	}

	public function testGivenRequestedItemDoesNotExist_throwsUseCaseError(): void {
		$itemId = new ItemId( 'Q10' );

		$this->itemRevisionMetadataRetriever = $this->createMock( ItemRevisionMetadataRetriever::class );
		$this->itemRevisionMetadataRetriever->expects( $this->once() )
			->method( 'getLatestRevisionMetadata' )
			->with( $itemId )
			->willReturn( LatestItemRevisionMetadataResult::itemNotFound() );

		try {
			$this->newUseCase()->execute(
				new GetItemLabelsRequest( $itemId->getSerialization() )
			);

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::ITEM_NOT_FOUND, $e->getErrorCode() );
			$this->assertSame( 'Could not find an item with the ID: Q10', $e->getErrorMessage() );
			$this->assertNull( $e->getErrorContext() );
		}
	}

	public function testGivenItemRedirect_throwsItemRedirectException(): void {
		$redirectSource = 'Q123';
		$redirectTarget = 'Q321';

		$this->itemRevisionMetadataRetriever
			->method( 'getLatestRevisionMetadata' )
			->with( new ItemId( $redirectSource ) )
			->willReturn( LatestItemRevisionMetadataResult::redirect( new ItemId( $redirectTarget ) ) );

		try {
			$this->newUseCase()->execute( new GetItemLabelsRequest( $redirectSource ) );

			$this->fail( 'this should not be reached' );
		} catch ( ItemRedirect $e ) {
			$this->assertSame( $redirectTarget, $e->getRedirectTargetId() );
		}
	}

	private function newUseCase(): GetItemLabels {
		return new GetItemLabels(
			$this->itemRevisionMetadataRetriever,
			$this->labelsRetriever,
			new GetItemLabelsValidator( new ItemIdValidator() )
		);
	}

}
