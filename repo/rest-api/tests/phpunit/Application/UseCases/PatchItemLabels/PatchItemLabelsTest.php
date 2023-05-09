<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\PatchItemLabels;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item as DataModelItem;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemLabels\PatchItemLabels;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemLabels\PatchItemLabelsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Model\EditSummary;
use Wikibase\Repo\RestApi\Domain\Model\User;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\Domain\ReadModel\Item;
use Wikibase\Repo\RestApi\Domain\ReadModel\ItemRevision;
use Wikibase\Repo\RestApi\Domain\ReadModel\Label;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Domain\ReadModel\LatestItemRevisionMetadataResult;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;
use Wikibase\Repo\RestApi\Domain\Services\ItemLabelsRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Domain\Services\JsonPatcher;
use Wikibase\Repo\RestApi\Domain\Services\PermissionChecker;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\WikibaseEntityPermissionChecker;
use Wikibase\Repo\RestApi\Infrastructure\JsonDiffJsonPatcher;
use Wikibase\Repo\Tests\RestApi\Domain\Model\EditMetadataHelper;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\PatchItemLabels\PatchItemLabels
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchItemLabelsTest extends TestCase {

	use EditMetadataHelper;

	private ItemLabelsRetriever $labelsRetriever;
	private LabelsSerializer $labelsSerializer;
	private JsonPatcher $patcher;
	private LabelsDeserializer $labelsDeserializer;
	private ItemRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;
	private ItemRevisionMetadataRetriever $metadataRetriever;
	private PermissionChecker $permissionChecker;

	protected function setUp(): void {
		parent::setUp();

		$this->labelsRetriever = $this->createStub( ItemLabelsRetriever::class );
		$this->labelsSerializer = new LabelsSerializer();
		$this->patcher = new JsonDiffJsonPatcher();
		$this->labelsDeserializer = new LabelsDeserializer();
		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemUpdater = $this->createStub( ItemUpdater::class );
		$this->metadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
		$this->permissionChecker = $this->createStub( PermissionChecker::class );
		$this->permissionChecker->method( 'canEdit' )->willReturn( true );
	}

	public function testHappyPath(): void {
		$itemId = new ItemId( 'Q42' );
		$item = NewItem::withId( $itemId )->build();

		$newLabelText = 'pomme de terre';
		$newLabelLanguage = 'fr';

		$this->labelsRetriever = $this->createStub( ItemLabelsRetriever::class );
		$this->labelsRetriever->method( 'getLabels' )->willReturn( new Labels() );

		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemRetriever->method( 'getItem' )->willReturn( $item );

		$this->metadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
		$this->metadataRetriever->method( 'getLatestRevisionMetadata' )
			->willReturn( LatestItemRevisionMetadataResult::concreteRevision( 321, '20201111070707' ) );

		$revisionId = 657;
		$lastModified = '20221212040506';
		$editTags = [ 'some', 'tags' ];
		$isBot = false;
		$comment = 'labels replaced by ' . __method__;

		$updatedItem = new Item(
			new Labels( new Label( $newLabelLanguage, $newLabelText ) ),
			new Descriptions(),
			new StatementList()
		);
		$this->itemUpdater = $this->createMock( ItemUpdater::class );
		$this->itemUpdater->expects( $this->once() )
			->method( 'update' )
			->with(
				$this->callback(
					fn( DataModelItem $item ) => $item->getLabels()->getByLanguage( $newLabelLanguage )->getText() === $newLabelText
				),
				$this->expectEquivalentMetadata( $editTags, $isBot, $comment, EditSummary::PATCH_ACTION )
			)
			->willReturn( new ItemRevision( $updatedItem, $lastModified, $revisionId ) );

		$response = $this->newUseCase()->execute(
			new PatchItemLabelsRequest(
				"$itemId",
				[
					[
						'op' => 'add',
						'path' => "/$newLabelLanguage",
						'value' => $newLabelText,
					],
				],
				$editTags,
				$isBot,
				$comment,
				null
			)
		);

		$this->assertSame( $response->getLabels(), $updatedItem->getLabels() );
		$this->assertSame( $lastModified, $response->getLastModified() );
		$this->assertSame( $revisionId, $response->getRevisionId() );
	}

	public function testGivenItemNotFound_throwsUseCaseError(): void {
		$itemId = 'Q789';
		$this->metadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
		$this->metadataRetriever->method( 'getLatestRevisionMetadata' )
			->willReturn( LatestItemRevisionMetadataResult::itemNotFound() );

		try {
			$this->newUseCase()->execute(
				new PatchItemLabelsRequest(
					$itemId,
					[
						[
							'op' => 'add',
							'path' => '/ar',
							'value' => 'new arabic label',
						],
					],
					[],
					false,
					null,
					null
				)
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
				new PatchItemLabelsRequest(
					$redirectSource,
					[
						[
							'op' => 'add',
							'path' => '/ar',
							'value' => 'new arabic label',
						],
					],
					[],
					false,
					null,
					null
				)
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::ITEM_REDIRECTED, $e->getErrorCode() );
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
				new PatchItemLabelsRequest(
					"$itemId",
					[
						[
							'op' => 'remove',
							'path' => '/en',
						],
					],
					[],
					false,
					null,
					null
				)
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame(
				UseCaseError::PERMISSION_DENIED,
				$e->getErrorCode()
			);
		}
	}

	private function newUseCase(): PatchItemLabels {
		return new PatchItemLabels(
			$this->labelsRetriever,
			$this->labelsSerializer,
			$this->patcher,
			$this->labelsDeserializer,
			$this->itemRetriever,
			$this->itemUpdater,
			$this->metadataRetriever,
			$this->permissionChecker
		);
	}

}
