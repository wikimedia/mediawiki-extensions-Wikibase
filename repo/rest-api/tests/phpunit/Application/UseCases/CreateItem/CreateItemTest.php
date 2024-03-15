<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\CreateItem;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\DescriptionsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\ItemDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\SitelinkDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\SitelinksDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\StatementDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\CreateItem\CreateItem;
use Wikibase\Repo\RestApi\Application\UseCases\CreateItem\CreateItemRequest;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\ItemEditSummary;
use Wikibase\Repo\RestApi\Domain\Services\ItemCreator;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\DummyItemRevisionMetaDataRetriever;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\InMemoryItemRepository;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\SameTitleSitelinkTargetResolver;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\CreateItem\CreateItem
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class CreateItemTest extends TestCase {

	private ItemCreator $itemCreator;

	protected function setUp(): void {
		parent::setUp();

		$this->itemCreator = new InMemoryItemRepository();
	}

	public function testHappyPath(): void {
		$enLabel = 'new item';
		$itemSerialization = [ 'labels' => [ 'en' => $enLabel ] ];
		$editTags = [ TestValidatingRequestDeserializer::ALLOWED_TAGS[0] ];
		$isBot = false;
		$comment = 'potato';

		$itemRepo = new InMemoryItemRepository();
		$this->itemCreator = $itemRepo;

		$response = $this->newUseCase()->execute( new CreateItemRequest(
			$itemSerialization,
			$editTags,
			$isBot,
			$comment,
			null
		) );

		$newItem = $response->getItem();
		$this->assertEquals(
			NewItem::withLabel( 'en', $enLabel )->andId( $newItem->getId() )->build(),
			$itemRepo->getItem( $newItem->getId() )
		);
		$this->assertEquals( $itemRepo->getLabels( $newItem->getId() ), $newItem->getLabels() );
		$this->assertSame( $itemRepo->getLatestRevisionId( $newItem->getId() ), $response->getRevisionId() );
		$this->assertSame( $itemRepo->getLatestRevisionTimestamp( $newItem->getId() ), $response->getLastModified() );
		$this->assertEquals(
			new EditMetadata( $editTags, $isBot, ItemEditSummary::newCreateSummary( $comment ) ),
			$itemRepo->getLatestRevisionEditMetadata( $newItem->getId() )
		);
	}

	private function newUseCase(): CreateItem {
		return new CreateItem(
			new ItemDeserializer(
				new LabelsDeserializer(),
				new DescriptionsDeserializer(),
				new AliasesDeserializer(),
				new SitelinksDeserializer(
					new SitelinkDeserializer(
						'/\?/',
						[ 'Q123' ],
						new SameTitleSitelinkTargetResolver(),
						new DummyItemRevisionMetaDataRetriever()
					)
				),
				$this->createStub( StatementDeserializer::class )
			),
			$this->itemCreator
		);
	}

}
