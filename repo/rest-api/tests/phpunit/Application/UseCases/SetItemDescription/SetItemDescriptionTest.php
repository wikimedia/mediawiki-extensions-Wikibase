<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\SetItemDescription;

use Wikibase\DataModel\Entity\Item as DataModelItem;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemDescription\SetItemDescription;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemDescription\SetItemDescriptionRequest;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemDescription\SetItemDescriptionResponse;
use Wikibase\Repo\RestApi\Domain\ReadModel\ItemRevision;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\SetItemDescription\SetItemDescription
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SetItemDescriptionTest extends \PHPUnit\Framework\TestCase {

	public function testExecute_happyPath(): void {
		$language = 'en';
		$description = 'Hello world again.';
		$itemId = 'Q123';
		$item = new DataModelItem();

		$itemRetriever = $this->createStub( ItemRetriever::class );
		$itemRetriever->method( 'getItem' )->willReturn( $item );

		$itemUpdater = $this->createMock( ItemUpdater::class );
		$itemUpdater->expects( $this->once() )
			->method( 'update' )
			->with( $this->callback(
				fn( DataModelItem $item ) => $item->getDescriptions()->toTextArray() === [ $language => $description ]
			) )
			->willReturn( $this->createStub( ItemRevision::class ) );

		$useCase = new SetItemDescription( $itemRetriever, $itemUpdater );
		$response = $useCase->execute( new SetItemDescriptionRequest( $itemId, $language, $description ) );

		$this->assertInstanceOf( SetItemDescriptionResponse::class, $response );
		$this->assertSame( $description, $response->getDescription() );
	}
}
