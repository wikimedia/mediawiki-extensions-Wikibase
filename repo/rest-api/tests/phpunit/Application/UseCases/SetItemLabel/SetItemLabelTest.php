<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\SetItemLabel;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item as DataModelItem;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemLabel\SetItemLabel;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemLabel\SetItemLabelRequest;
use Wikibase\Repo\RestApi\Domain\Model\EditSummary;
use Wikibase\Repo\RestApi\Domain\ReadModel\Item;
use Wikibase\Repo\RestApi\Domain\ReadModel\ItemRevision;
use Wikibase\Repo\RestApi\Domain\ReadModel\Label;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\Tests\RestApi\Domain\Model\EditMetadataHelper;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\SetItemLabel\SetItemLabel
 * @group Wikibase
 * @license GPL-2.0-or-later
 */
class SetItemLabelTest extends TestCase {

	use EditMetadataHelper;

	private ItemRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;

	protected function setUp(): void {
		parent::setUp();

		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemUpdater = $this->createStub( ItemUpdater::class );
	}

	public function testReplaceLabel(): void {
		$itemId = 'Q123';
		$langCode = 'en';
		$updatedLabelText = 'Replaced label';
		$editTags = [ 'some', 'tags' ];
		$isBot = false;
		$comment = "{$this->getName()} Comment";
		$revisionId = 657;
		$lastModified = '20221212040506';
		$item = NewItem::withId( $itemId )->andLabel( $langCode, 'Label to replace' )->build();

		$this->itemRetriever = $this->createMock( ItemRetriever::class );
		$this->itemRetriever->expects( $this->once() )->method( 'getItem' )->with( $itemId )->willReturn( $item );

		$updatedItem = new Item( new Labels( new Label( $langCode, $updatedLabelText ) ), new StatementList() );
		$this->itemUpdater = $this->createMock( ItemUpdater::class );
		$this->itemUpdater->expects( $this->once() )->method( 'update' )
			->with(
				$this->callback( fn( DataModelItem $item ) => $item->getLabels()->toTextArray() === [ $langCode => $updatedLabelText ] ),
				$this->expectEquivalentMetadata( $editTags, $isBot, $comment, EditSummary::REPLACE_ACTION )
			)
			->willReturn( new ItemRevision( $updatedItem, $lastModified, $revisionId ) );

		$request = new SetItemLabelRequest( $itemId, $langCode, $updatedLabelText, $editTags, $isBot, $comment );
		$response = $this->newUseCase()->execute( $request );

		$this->assertEquals( new Label( $langCode, $updatedLabelText ), $response->getLabel() );
		$this->assertSame( $revisionId, $response->getRevisionId() );
		$this->assertSame( $lastModified, $response->getLastModified() );
	}

	private function newUseCase(): SetItemLabel {
		return new SetItemLabel( $this->itemRetriever, $this->itemUpdater );
	}

}
