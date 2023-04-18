<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\SetItemDescription;

use Wikibase\DataModel\Entity\Item as DataModelItem;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemDescription\SetItemDescription;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemDescription\SetItemDescriptionRequest;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Model\EditSummary;
use Wikibase\Repo\RestApi\Domain\Model\LatestItemRevisionMetadataResult;
use Wikibase\Repo\RestApi\Domain\ReadModel\Description;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\Domain\ReadModel\Item as ReadModelItem;
use Wikibase\Repo\RestApi\Domain\ReadModel\ItemRevision;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\Tests\RestApi\Domain\Model\EditMetadataHelper;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\SetItemDescription\SetItemDescription
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SetItemDescriptionTest extends \PHPUnit\Framework\TestCase {

	use EditMetadataHelper;

	private ItemRevisionMetadataRetriever $metadataRetriever;
	private ItemRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;

	protected function setUp(): void {
		parent::setUp();

		$this->metadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
		$this->metadataRetriever->method( 'getLatestRevisionMetadata' )
			->willReturn( LatestItemRevisionMetadataResult::concreteRevision( 123, '20221212040505' ) );
		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemUpdater = $this->createStub( ItemUpdater::class );
	}

	public function testAddDescription(): void {
		$language = 'en';
		$description = 'Hello world again.';
		$itemId = 'Q123';
		$editTags = [ 'some', 'tags' ];
		$isBot = false;
		$comment = 'add description edit comment';

		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemRetriever->method( 'getItem' )->willReturn( new DataModelItem() );

		$updatedItem = new ReadModelItem(
			new Labels(),
			new Descriptions( new Description( $language, $description ) ),
			new StatementList()
		);
		$revisionId = 123;
		$lastModified = '20221212040506';

		$this->itemUpdater = $this->createMock( ItemUpdater::class );
		$this->itemUpdater->expects( $this->once() )
			->method( 'update' )
			->with(
				$this->callback( fn( DataModelItem $item ) => $item->getDescriptions()->toTextArray() === [ $language => $description ] ),
				$this->expectEquivalentMetadata( $editTags, $isBot, $comment, EditSummary::ADD_ACTION )
			)
			->willReturn( new ItemRevision( $updatedItem, $lastModified, $revisionId ) );

		$response = $this->newUseCase()->execute(
			new SetItemDescriptionRequest( $itemId, $language, $description, $editTags, $isBot, $comment )
		);

		$this->assertEquals( new Description( $language, $description ), $response->getDescription() );
		$this->assertSame( $revisionId, $response->getRevisionId() );
		$this->assertSame( $lastModified, $response->getLastModified() );
		$this->assertFalse( $response->wasReplaced() );
	}

	public function testReplaceDescription(): void {
		$language = 'en';
		$newDescription = 'Hello world again.';
		$itemId = 'Q123';
		$editTags = [ 'some', 'tags' ];
		$isBot = false;
		$item = NewItem::withId( $itemId )->andDescription( $language, 'Hello world' )->build();
		$comment = 'replace description edit comment';

		$this->itemRetriever = $this->createMock( ItemRetriever::class );
		$this->itemRetriever
			->expects( $this->once() )
			->method( 'getItem' )
			->with( $itemId )
			->willReturn( $item );

		$updatedItem = new ReadModelItem(
			new Labels(),
			new Descriptions( new Description( $language, $newDescription ) ),
			new StatementList()
		);
		$revisionId = 123;
		$lastModified = '20221212040506';

		$this->itemUpdater = $this->createMock( ItemUpdater::class );
		$this->itemUpdater->expects( $this->once() )
			->method( 'update' )
			->with(
				$this->callback(
					fn( DataModelItem $item ) => $item->getDescriptions()->toTextArray() === [ $language => $newDescription ]
				),
				$this->expectEquivalentMetadata( $editTags, $isBot, $comment, EditSummary::REPLACE_ACTION )
			)
			->willReturn( new ItemRevision( $updatedItem, $lastModified, $revisionId ) );

		$response = $this->newUseCase()->execute(
			new SetItemDescriptionRequest( $itemId, $language, $newDescription, $editTags, $isBot, $comment )
		);

		$this->assertEquals( new Description( $language, $newDescription ), $response->getDescription() );
		$this->assertSame( $revisionId, $response->getRevisionId() );
		$this->assertSame( $lastModified, $response->getLastModified() );
		$this->assertTrue( $response->wasReplaced() );
	}

	public function testGivenItemNotFound_throwsUseCaseError(): void {
		$itemId = 'Q789';
		$this->metadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
		$this->metadataRetriever->method( 'getLatestRevisionMetadata' )
			->willReturn( LatestItemRevisionMetadataResult::itemNotFound() );

		try {
			$this->newUseCase()->execute(
				new SetItemDescriptionRequest( $itemId, 'en', 'test description', [], false, null )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::ITEM_NOT_FOUND, $e->getErrorCode() );
			$this->assertStringContainsString( $itemId, $e->getErrorMessage() );
		}
	}

	public function testGivenItemRedirect_throwsUseCaseError(): void {
		$redirectSource = 'Q321';
		$redirectTarget = 'Q123';

		$this->metadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
		$this->metadataRetriever->method( 'getLatestRevisionMetadata' )
			->willReturn( LatestItemRevisionMetadataResult::redirect( new ItemId( $redirectTarget ) ) );

		try {
			$this->newUseCase()->execute(
				new SetItemDescriptionRequest( $redirectSource, 'en', 'test description', [], false, null )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::ITEM_REDIRECTED, $e->getErrorCode() );
			$this->assertStringContainsString( $redirectSource, $e->getErrorMessage() );
			$this->assertStringContainsString( $redirectTarget, $e->getErrorMessage() );
		}
	}

	private function newUseCase(): SetItemDescription {
		return new SetItemDescription( $this->metadataRetriever, $this->itemRetriever, $this->itemUpdater );
	}
}
