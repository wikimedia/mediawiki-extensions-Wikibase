<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\GetItemLabels;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemLabels\GetItemLabels;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemLabels\GetItemLabelsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemLabels\GetItemLabelsResponse;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemLabels\GetItemLabelsValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestItemRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Application\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Domain\ReadModel\Label;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Domain\Services\ItemLabelsRetriever;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetItemLabels\GetItemLabels
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemLabelsTest extends TestCase {

	private GetLatestItemRevisionMetadata $getRevisionMetadata;
	private ItemLabelsRetriever $labelsRetriever;

	protected function setUp(): void {
		parent::setUp();

		$this->getRevisionMetadata = $this->createStub( GetLatestItemRevisionMetadata::class );
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

		$this->getRevisionMetadata = $this->createStub( GetLatestItemRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )->willReturn( [ $revisionId, $lastModified ] );

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

	public function testGivenItemNotFoundOrRedirect_throws(): void {
		$itemId = new ItemId( 'Q10' );

		$expectedException = $this->createStub( UseCaseException::class );

		$this->getRevisionMetadata = $this->createStub( GetLatestItemRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )
			->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute(
				new GetItemLabelsRequest( $itemId->getSerialization() )
			);

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	private function newUseCase(): GetItemLabels {
		return new GetItemLabels(
			$this->getRevisionMetadata,
			$this->labelsRetriever,
			new GetItemLabelsValidator( new ItemIdValidator() )
		);
	}

}
