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
use Wikibase\Repo\RestApi\Domain\Model\EditSummary;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\Domain\ReadModel\Item;
use Wikibase\Repo\RestApi\Domain\ReadModel\ItemRevision;
use Wikibase\Repo\RestApi\Domain\ReadModel\Label;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;
use Wikibase\Repo\RestApi\Domain\Services\ItemLabelsRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Domain\Services\JsonPatcher;
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

	protected function setUp(): void {
		parent::setUp();

		$this->labelsRetriever = $this->createStub( ItemLabelsRetriever::class );
		$this->labelsSerializer = new LabelsSerializer();
		$this->patcher = new JsonDiffJsonPatcher();
		$this->labelsDeserializer = new LabelsDeserializer();
		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemUpdater = $this->createStub( ItemUpdater::class );
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
				$comment
			)
		);

		$this->assertSame( $response->getLabels(), $updatedItem->getLabels() );
		$this->assertSame( $lastModified, $response->getLastModified() );
		$this->assertSame( $revisionId, $response->getRevisionId() );
	}

	private function newUseCase(): PatchItemLabels {
		return new PatchItemLabels(
			$this->labelsRetriever,
			$this->labelsSerializer,
			$this->patcher,
			$this->labelsDeserializer,
			$this->itemRetriever,
			$this->itemUpdater
		);
	}

}
