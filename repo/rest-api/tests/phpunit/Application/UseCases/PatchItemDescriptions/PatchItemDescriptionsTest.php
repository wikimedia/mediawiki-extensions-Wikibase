<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\PatchItemDescriptions;

use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item as DataModelItem;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\RestApi\Application\Serialization\DescriptionsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\DescriptionsSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemDescriptions\PatchItemDescriptions;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemDescriptions\PatchItemDescriptionsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemDescriptions\PatchItemDescriptionsValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Domain\Model\EditSummary;
use Wikibase\Repo\RestApi\Domain\ReadModel\Description;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\Domain\ReadModel\Item;
use Wikibase\Repo\RestApi\Domain\ReadModel\ItemRevision;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;
use Wikibase\Repo\RestApi\Domain\Services\ItemDescriptionsRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Domain\Services\JsonPatcher;
use Wikibase\Repo\RestApi\Infrastructure\JsonDiffJsonPatcher;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\RestApi\Domain\Model\EditMetadataHelper;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\PatchItemDescriptions\PatchItemDescriptions
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchItemDescriptionsTest extends TestCase {

	use EditMetadataHelper;

	private AssertUserIsAuthorized $assertUserIsAuthorized;
	private ItemDescriptionsRetriever $descriptionsRetriever;
	private DescriptionsSerializer $descriptionsSerializer;
	private JsonPatcher $patcher;
	private ItemRetriever $itemRetriever;
	private DescriptionsDeserializer $descriptionsDeserializer;
	private ItemUpdater $itemUpdater;

	protected function setUp(): void {
		parent::setUp();

		$this->validator = new TestValidatingRequestDeserializer();
		$this->assertUserIsAuthorized = $this->createStub( AssertUserIsAuthorized::class );
		$this->descriptionsRetriever = $this->createStub( ItemDescriptionsRetriever::class );
		$this->descriptionsSerializer = new DescriptionsSerializer();
		$this->patcher = new JsonDiffJsonPatcher();
		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->descriptionsDeserializer = new DescriptionsDeserializer();
		$this->itemUpdater = $this->createStub( ItemUpdater::class );
	}

	public function testHappyPath(): void {
		$itemId = new ItemId( 'Q42' );
		$item = NewItem::withId( $itemId )->build();

		$newDescriptionText = 'a description of an Item';
		$newDescriptionLanguage = 'en';

		$this->descriptionsRetriever = $this->createStub( ItemDescriptionsRetriever::class );
		$this->descriptionsRetriever->method( 'getDescriptions' )->willReturn( new Descriptions() );

		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemRetriever->method( 'getItem' )->willReturn( $item );

		$revisionId = 657;
		$lastModified = '20221212040506';
		$editTags = TestValidatingRequestDeserializer::ALLOWED_TAGS;
		$isBot = false;
		$comment = 'descriptions patched by ' . __method__;

		$updatedItem = new Item(
			new Labels(),
			new Descriptions( new Description( $newDescriptionLanguage, $newDescriptionText ) ),
			new StatementList()
		);
		$this->itemUpdater = $this->createMock( ItemUpdater::class );
		$this->itemUpdater->expects( $this->once() )
			->method( 'update' )
			->with(
				$this->expectEquivalentItemByDescription( $newDescriptionLanguage, $newDescriptionText ),
				$this->expectEquivalentMetadata( $editTags, $isBot, $comment, EditSummary::PATCH_ACTION )
			)
			->willReturn( new ItemRevision( $updatedItem, $lastModified, $revisionId ) );

		$response = $this->newUseCase()->execute(
			new PatchItemDescriptionsRequest(
				(string)$itemId,
				[ [ 'op' => 'add', 'path' => "/$newDescriptionLanguage", 'value' => $newDescriptionText ] ],
				$editTags,
				$isBot,
				$comment,
				null
			)
		);

		$this->assertSame( $response->getDescriptions(), $updatedItem->getDescriptions() );
	}

	public function testInvalidRequest_throwsException(): void {
		$expectedException = new UseCaseException( 'invalid-description-patch-test' );
		$this->validator = $this->createStub( PatchItemDescriptionsValidator::class );
		$this->validator->method( 'validateAndDeserialize' )->willThrowException( $expectedException );
		try {
			$this->newUseCase()->execute( $this->createStub( PatchItemDescriptionsRequest::class ) );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenEditIsUnauthorized_throwsUseCaseError(): void {
		$itemId = new ItemId( 'Q123' );

		$expectedError = new UseCaseError( UseCaseError::PERMISSION_DENIED, 'permission denied' );
		$this->assertUserIsAuthorized = $this->createMock( AssertUserIsAuthorized::class );
		$this->assertUserIsAuthorized->method( 'execute' )
			->with( $itemId, null )
			->willThrowException( $expectedError );

		try {
			$this->newUseCase()->execute(
				$this->newUseCaseRequest( "$itemId", [ [ 'op' => 'remove', 'path' => '/en' ] ] )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedError, $e );
		}
	}

	private function newUseCase(): PatchItemDescriptions {
		return new PatchItemDescriptions(
			$this->validator,
			$this->assertUserIsAuthorized,
			$this->descriptionsRetriever,
			$this->descriptionsSerializer,
			$this->patcher,
			$this->itemRetriever,
			$this->descriptionsDeserializer,
			$this->itemUpdater
		);
	}

	private function newUseCaseRequest( string $itemId, array $patch ): PatchItemDescriptionsRequest {
		return new PatchItemDescriptionsRequest( $itemId, $patch, [], false, null, null );
	}

	private function expectEquivalentItemByDescription( string $languageCode, string $descriptionText ): Callback {
		return $this->callback(
			function( DataModelItem $item ) use ( $languageCode, $descriptionText ) {
				return $item->getDescriptions()->getByLanguage( $languageCode )->getText() === $descriptionText;
			}
		);
	}

}
