<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\GetItemLabel;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\Model\LatestItemRevisionMetadataResult;
use Wikibase\Repo\RestApi\Domain\ReadModel\Label;
use Wikibase\Repo\RestApi\Domain\Services\ItemLabelRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\UseCases\GetItemLabel\GetItemLabel;
use Wikibase\Repo\RestApi\UseCases\GetItemLabel\GetItemLabelRequest;
use Wikibase\Repo\RestApi\UseCases\GetItemLabel\GetItemLabelResponse;

/**
 * @covers \Wikibase\Repo\RestApi\UseCases\GetItemLabels\GetItemLabel
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemLabelTest extends TestCase {

	/**
	 * @var MockObject|ItemRevisionMetadataRetriever
	 */
	private $itemRevisionMetadataRetriever;

	/**
	 * @var MockObject|ItemLabelRetriever
	 */
	private $labelRetriever;

	protected function setUp(): void {
		parent::setUp();

		$this->itemRevisionMetadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
		$this->labelRetriever = $this->createStub( ItemLabelRetriever::class );
	}

	public function testSuccess(): void {
		$label = new Label( 'en', 'earth' );

		$itemId = new ItemId( 'Q2' );
		$lastModified = '20201111070707';
		$revisionId = 2;

		$this->itemRevisionMetadataRetriever = $this->createMock( ItemRevisionMetadataRetriever::class );
		$this->itemRevisionMetadataRetriever->expects( $this->once() )
			->method( 'getLatestRevisionMetadata' )
			->with( $itemId )
			->willReturn( LatestItemRevisionMetadataResult::concreteRevision( $revisionId, $lastModified ) );

		$this->labelRetriever = $this->createMock( ItemLabelRetriever::class );
		$this->labelRetriever->expects( $this->once() )
			->method( 'getLabel' )
			->with( $itemId, 'en' )
			->willReturn( $label );

		$request = new GetItemLabelRequest( 'Q2', 'en' );
		$response = $this->newUseCase()->execute( $request );
		$this->assertEquals( new GetItemLabelResponse( $label, $lastModified, $revisionId ), $response );
	}

	private function newUseCase(): GetItemLabel {
		return new GetItemLabel(
			$this->itemRevisionMetadataRetriever,
			$this->labelRetriever
		);
	}

}
