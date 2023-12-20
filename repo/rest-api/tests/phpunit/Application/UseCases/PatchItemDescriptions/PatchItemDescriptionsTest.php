<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\PatchItemDescriptions;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\RestApi\Application\Serialization\DescriptionsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\DescriptionsSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\AssertItemExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemDescriptions\PatchedDescriptionsValidator;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemDescriptions\PatchItemDescriptions;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemDescriptions\PatchItemDescriptionsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemDescriptions\PatchItemDescriptionsValidator;
use Wikibase\Repo\RestApi\Application\UseCases\PatchJson;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Application\Validation\ItemDescriptionValidator;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;
use Wikibase\Repo\RestApi\Domain\Model\DescriptionsEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\User;
use Wikibase\Repo\RestApi\Domain\ReadModel\Description;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\Domain\Services\ItemDescriptionsRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Infrastructure\JsonDiffJsonPatcher;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\InMemoryItemRepository;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\PatchItemDescriptions\PatchItemDescriptions
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchItemDescriptionsTest extends TestCase {

	private PatchItemDescriptionsValidator $validator;
	private AssertItemExists $assertItemExists;
	private AssertUserIsAuthorized $assertUserIsAuthorized;
	private ItemDescriptionsRetriever $descriptionsRetriever;
	private DescriptionsSerializer $descriptionsSerializer;
	private PatchJson $patcher;
	private ItemRetriever $itemRetriever;
	private PatchedDescriptionsValidator $patchedDescriptionsValidator;
	private ItemUpdater $itemUpdater;

	protected function setUp(): void {
		parent::setUp();

		$this->validator = new TestValidatingRequestDeserializer();
		$this->assertItemExists = $this->createStub( AssertItemExists::class );
		$this->assertUserIsAuthorized = $this->createStub( AssertUserIsAuthorized::class );
		$this->descriptionsRetriever = $this->createStub( ItemDescriptionsRetriever::class );
		$this->descriptionsSerializer = new DescriptionsSerializer();
		$this->patcher = new PatchJson( new JsonDiffJsonPatcher() );
		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->patchedDescriptionsValidator = new PatchedDescriptionsValidator(
			new DescriptionsDeserializer(),
			$this->createStub( ItemDescriptionValidator::class ),
			$this->createStub( LanguageCodeValidator::class )
		);
		$this->itemUpdater = $this->createStub( ItemUpdater::class );
	}

	public function testHappyPath(): void {
		$itemId = new ItemId( 'Q42' );

		$newDescriptionText = 'a description of an Item';
		$newDescriptionLanguage = 'en';

		$this->descriptionsRetriever = $this->createStub( ItemDescriptionsRetriever::class );
		$this->descriptionsRetriever->method( 'getDescriptions' )->willReturn( new Descriptions() );

		$editTags = TestValidatingRequestDeserializer::ALLOWED_TAGS;
		$isBot = false;
		$comment = 'descriptions patched by ' . __method__;

		$itemRepo = new InMemoryItemRepository();
		$itemRepo->addItem( new Item( $itemId ) );
		$this->itemRetriever = $itemRepo;
		$this->itemUpdater = $itemRepo;

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

		$this->assertSame( $itemRepo->getLatestRevisionId( $itemId ), $response->getRevisionId() );
		$this->assertSame( $itemRepo->getLatestRevisionTimestamp( $itemId ), $response->getLastModified() );
		$this->assertEquals(
			$response->getDescriptions(),
			new Descriptions( new Description( $newDescriptionLanguage, $newDescriptionText ) )
		);
		$this->assertEquals(
			new EditMetadata(
				$editTags,
				$isBot,
				DescriptionsEditSummary::newPatchSummary(
					$comment,
					new TermList(),
					new TermList( [ new Term( $newDescriptionLanguage, $newDescriptionText ) ] )
				)
			),
			$itemRepo->getLatestRevisionEditMetadata( $itemId )
		);
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

	public function testGivenItemNotFoundOrRedirect_throws(): void {
		$itemId = 'Q789';
		$expectedException = $this->createStub( UseCaseException::class );

		$this->assertItemExists = $this->createStub( AssertItemExists::class );
		$this->assertItemExists->method( 'execute' )->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute(
				$this->newUseCaseRequest(
					$itemId,
					[ [ 'op' => 'add', 'path' => '/ar', 'value' => 'الوصف العربي الجديد' ] ]
				)
			);
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
			->with( $itemId, User::newAnonymous() )
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

	public function testGivenPatchJsonError_throwsUseCaseError(): void {
		$expectedError = $this->createStub( UseCaseError::class );

		$this->descriptionsRetriever = $this->createStub( ItemDescriptionsRetriever::class );
		$this->descriptionsRetriever->method( 'getDescriptions' )
			->willReturn( new Descriptions( new Description( 'en', 'English Description' ) ) );

		$this->patcher = $this->createMock( PatchJson::class );
		$this->patcher->expects( $this->once() )
			->method( 'execute' )
			->with( [ 'en' => 'English Description' ], [] )
			->willThrowException( $expectedError );

		try {
			$this->newUseCase()->execute( $this->newUseCaseRequest( 'Q123', [] ) );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedError, $e );
		}
	}

	public function testGivenPatchedDescriptionsInvalid_throwsUseCaseError(): void {
		$itemId = 'Q123';
		$item = NewItem::withId( $itemId )->build();
		$patchResult = [ 'ar' => '' ];

		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemRetriever->method( 'getItem' )->willReturn( $item );

		$this->descriptionsRetriever = $this->createStub( ItemDescriptionsRetriever::class );
		$this->descriptionsRetriever->method( 'getDescriptions' )->willReturn( new Descriptions() );

		$expectedUseCaseError = $this->createStub( UseCaseError::class );
		$this->patchedDescriptionsValidator = $this->createMock( PatchedDescriptionsValidator::class );
		$this->patchedDescriptionsValidator->expects( $this->once() )
			->method( 'validateAndDeserialize' )
			->with( $item->getId(), new TermList(), $patchResult )
			->willThrowException( $expectedUseCaseError );

		try {
			$this->newUseCase()->execute(
				$this->newUseCaseRequest( $itemId, [ [ 'op' => 'add', 'path' => '/ar', 'value' => '' ] ] )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedUseCaseError, $e );
		}
	}

	private function newUseCase(): PatchItemDescriptions {
		return new PatchItemDescriptions(
			$this->validator,
			$this->assertItemExists,
			$this->assertUserIsAuthorized,
			$this->descriptionsRetriever,
			$this->descriptionsSerializer,
			$this->patcher,
			$this->itemRetriever,
			$this->patchedDescriptionsValidator,
			$this->itemUpdater
		);
	}

	private function newUseCaseRequest( string $itemId, array $patch ): PatchItemDescriptionsRequest {
		return new PatchItemDescriptionsRequest( $itemId, $patch, [], false, null, null );
	}

}
