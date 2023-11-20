<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\RemoveItemLabel;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveItemLabel\RemoveItemLabel;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveItemLabel\RemoveItemLabelRequest;
use Wikibase\Repo\RestApi\Domain\Model\EditSummary;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\Tests\RestApi\Domain\Model\EditMetadataHelper;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\RemoveItemLabel\RemoveItemLabel
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 *
 */
class RemoveItemLabelTest extends TestCase {

	use EditMetadataHelper;

	private ItemRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;

	protected function setUp(): void {
		parent::setUp();

		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemUpdater = $this->createStub( ItemUpdater::class );
	}

	public function testHappyPath(): void {
		$itemId = new ItemId( 'Q123' );
		$languageCode = 'en';
		$label = 'test label';

		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemRetriever->method( 'getItem' )
			->willReturn( NewItem::withId( $itemId )->andLabel( $languageCode, $label )->build() );

		$this->itemUpdater = $this->createMock( ItemUpdater::class );
		$this->itemUpdater->expects( $this->once() )
			->method( 'update' )
			->with(
				NewItem::withId( $itemId )->build(),
				$this->expectEquivalentMetadata( [ 'tag' ], false, 'test', EditSummary::REMOVE_ACTION )
			);

		$request = new RemoveItemLabelRequest( (string)$itemId, $languageCode, [ 'tag' ], false, 'test', null );
		$this->newUseCase()->execute( $request );
	}

	private function newUseCase(): RemoveItemLabel {
		return new RemoveItemLabel( $this->itemRetriever, $this->itemUpdater );
	}

}
