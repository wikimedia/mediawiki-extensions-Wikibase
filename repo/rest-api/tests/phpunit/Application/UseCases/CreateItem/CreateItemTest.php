<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\CreateItem;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\CreateItem\CreateItem;
use Wikibase\Repo\RestApi\Application\UseCases\CreateItem\CreateItemRequest;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\ItemEditSummary;
use Wikibase\Repo\RestApi\Domain\Services\ItemCreator;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\InMemoryItemRepository;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\CreateItem\CreateItem
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class CreateItemTest extends TestCase {

	private ItemCreator $itemCreator;
	private AssertUserIsAuthorized $assertUserIsAuthorized;

	protected function setUp(): void {
		parent::setUp();

		$this->itemCreator = new InMemoryItemRepository();
		$this->assertUserIsAuthorized = $this->createStub( AssertUserIsAuthorized::class );
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
			$itemRepo->getItemWriteModel( $newItem->getId() )
		);
		$this->assertEquals( $itemRepo->getLabels( $newItem->getId() ), $newItem->getLabels() );
		$this->assertSame( $itemRepo->getLatestRevisionId( $newItem->getId() ), $response->getRevisionId() );
		$this->assertSame( $itemRepo->getLatestRevisionTimestamp( $newItem->getId() ), $response->getLastModified() );
		$this->assertEquals(
			new EditMetadata( $editTags, $isBot, ItemEditSummary::newCreateSummary( $comment ) ),
			$itemRepo->getLatestRevisionEditMetadata( $newItem->getId() )
		);
	}

	public function testGivenUserUnauthorized_throws(): void {
		$expectedException = $this->createStub( UseCaseError::class );
		$this->assertUserIsAuthorized = $this->createStub( AssertUserIsAuthorized::class );
		$this->assertUserIsAuthorized->method( 'checkCreateItemPermissions' )->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute(
				new CreateItemRequest( [ 'labels' => [ 'en' => 'new item' ] ], [], false, null, null )
			);
			$this->fail( 'expected exception not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	private function newUseCase(): CreateItem {
		return new CreateItem(
			new TestValidatingRequestDeserializer(),
			$this->itemCreator,
			$this->assertUserIsAuthorized
		);
	}

}
