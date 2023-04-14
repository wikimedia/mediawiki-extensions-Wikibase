<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\SetItemLabel;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item as DataModelItem;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemLabel\SetItemLabel;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemLabel\SetItemLabelRequest;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemLabel\SetItemLabelValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Domain\Model\EditSummary;
use Wikibase\Repo\RestApi\Domain\Model\LatestItemRevisionMetadataResult;
use Wikibase\Repo\RestApi\Domain\Model\User;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\Domain\ReadModel\Item;
use Wikibase\Repo\RestApi\Domain\ReadModel\ItemRevision;
use Wikibase\Repo\RestApi\Domain\ReadModel\Label;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Domain\Services\PermissionChecker;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\WikibaseEntityPermissionChecker;
use Wikibase\Repo\Tests\RestApi\Domain\Model\EditMetadataHelper;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\SetItemLabel\SetItemLabel
 * @group Wikibase
 * @license GPL-2.0-or-later
 */
class SetItemLabelTest extends TestCase {

	use EditMetadataHelper;

	private SetItemLabelValidator $validator;
	private ItemRevisionMetadataRetriever $metadataRetriever;
	private ItemRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;
	private PermissionChecker $permissionChecker;

	protected function setUp(): void {
		parent::setUp();

		$this->validator = $this->createStub( SetItemLabelValidator::class );
		$this->metadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemUpdater = $this->createStub( ItemUpdater::class );
		$this->permissionChecker = $this->createStub( PermissionChecker::class );
		$this->permissionChecker->method( 'canEdit' )->willReturn( true );
	}

	public function testAddLabel(): void {
		$itemId = 'Q123';
		$langCode = 'en';
		$newLabelText = 'New label';
		$editTags = [ 'some', 'tags' ];
		$isBot = false;
		$comment = "{$this->getName()} Comment";
		$revisionId = 657;
		$lastModified = '20221212040506';
		$item = NewItem::withId( $itemId )->build();

		$this->metadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
		$this->metadataRetriever->method( 'getLatestRevisionMetadata' )
			->willReturn( LatestItemRevisionMetadataResult::concreteRevision( 321, '20201111070707' ) );

		$this->itemRetriever = $this->createMock( ItemRetriever::class );
		$this->itemRetriever->expects( $this->once() )->method( 'getItem' )->with( $itemId )->willReturn( $item );

		$updatedItem = new Item(
			new Labels( new Label( $langCode, $newLabelText ) ),
			new Descriptions(),
			new StatementList()
		);
		$this->itemUpdater = $this->createMock( ItemUpdater::class );
		$this->itemUpdater->expects( $this->once() )->method( 'update' )
			->with(
				$this->callback( fn( DataModelItem $item ) => $item->getLabels()->toTextArray() === [ $langCode => $newLabelText ] ),
				$this->expectEquivalentMetadata( $editTags, $isBot, $comment, EditSummary::ADD_ACTION )
			)
			->willReturn( new ItemRevision( $updatedItem, $lastModified, $revisionId ) );

		$request = new SetItemLabelRequest( $itemId, $langCode, $newLabelText, $editTags, $isBot, $comment, null );
		$response = $this->newUseCase()->execute( $request );

		$this->assertEquals( new Label( $langCode, $newLabelText ), $response->getLabel() );
		$this->assertSame( $revisionId, $response->getRevisionId() );
		$this->assertSame( $lastModified, $response->getLastModified() );
		$this->assertFalse( $response->wasReplaced() );
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

		$this->metadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
		$this->metadataRetriever->method( 'getLatestRevisionMetadata' )
			->willReturn( LatestItemRevisionMetadataResult::concreteRevision( 321, '20201111070707' ) );

		$this->itemRetriever = $this->createMock( ItemRetriever::class );
		$this->itemRetriever->expects( $this->once() )->method( 'getItem' )->with( $itemId )->willReturn( $item );

		$updatedItem = new Item(
			new Labels( new Label( $langCode, $updatedLabelText ) ),
			new Descriptions(),
			new StatementList()
		);
		$this->itemUpdater = $this->createMock( ItemUpdater::class );
		$this->itemUpdater->expects( $this->once() )->method( 'update' )
			->with(
				$this->callback( fn( DataModelItem $item ) => $item->getLabels()->toTextArray() === [ $langCode => $updatedLabelText ] ),
				$this->expectEquivalentMetadata( $editTags, $isBot, $comment, EditSummary::REPLACE_ACTION )
			)
			->willReturn( new ItemRevision( $updatedItem, $lastModified, $revisionId ) );

		$request = new SetItemLabelRequest( $itemId, $langCode, $updatedLabelText, $editTags, $isBot, $comment, null );
		$response = $this->newUseCase()->execute( $request );

		$this->assertEquals( new Label( $langCode, $updatedLabelText ), $response->getLabel() );
		$this->assertSame( $revisionId, $response->getRevisionId() );
		$this->assertSame( $lastModified, $response->getLastModified() );
		$this->assertTrue( $response->wasReplaced() );
	}

	public function testGivenInvalidRequest_throwsUseCaseException(): void {
		$expectedException = new UseCaseException( 'invalid-label-test' );
		$this->validator = $this->createStub( SetItemLabelValidator::class );
		$this->validator->method( 'assertValidRequest' )->willThrowException( $expectedException );
		try {
			$this->newUseCase()->execute(
				new SetItemLabelRequest( 'Q123', 'en', 'label', [], false, null, null )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
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
				new SetItemLabelRequest( $redirectSource, 'en', 'test label', [], false, null, null )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::ITEM_REDIRECTED, $e->getErrorCode() );
			$this->assertStringContainsString( $redirectSource, $e->getErrorMessage() );
			$this->assertStringContainsString( $redirectTarget, $e->getErrorMessage() );
		}
	}

	public function testGivenEditIsUnauthorized_throwsUseCaseError(): void {
		$itemId = new ItemId( 'Q123' );
		$this->metadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
		$this->metadataRetriever->method( 'getLatestRevisionMetadata' )
			->willReturn( LatestItemRevisionMetadataResult::concreteRevision( 321, '20201111070707' ) );

		$this->permissionChecker = $this->createMock( WikibaseEntityPermissionChecker::class );
		$this->permissionChecker->expects( $this->once() )
			->method( 'canEdit' )
			->with( User::newAnonymous(), $itemId )
			->willReturn( false );

		try {
			$this->newUseCase()->execute(
				new SetItemLabelRequest( "$itemId", 'en', 'test label', [], false, null, null )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame(
				UseCaseError::PERMISSION_DENIED,
				$e->getErrorCode()
			);
		}
	}

	private function newUseCase(): SetItemLabel {
		return new SetItemLabel(
			$this->validator,
			$this->metadataRetriever,
			$this->itemRetriever,
			$this->itemUpdater,
			$this->permissionChecker
		);
	}

}
