<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Application\UseCases\PatchItemLabels;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\Domains\Crud\Application\Serialization\LabelsDeserializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\LabelsSerializer;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertItemExists;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetLatestItemRevisionMetadata;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItemLabels\PatchedItemLabelsValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItemLabels\PatchItemLabels;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItemLabels\PatchItemLabelsRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItemLabels\PatchItemLabelsValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchJson;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseException;
use Wikibase\Repo\Domains\Crud\Application\Validation\ItemLabelsContentsValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\ItemLabelValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\LabelLanguageCodeValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\LabelsSyntaxValidator;
use Wikibase\Repo\Domains\Crud\Domain\Model\EditMetadata;
use Wikibase\Repo\Domains\Crud\Domain\Model\LabelsEditSummary;
use Wikibase\Repo\Domains\Crud\Domain\Model\User;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Label;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Labels;
use Wikibase\Repo\Domains\Crud\Domain\Services\ItemLabelsRetriever;
use Wikibase\Repo\Domains\Crud\Domain\Services\ItemUpdater;
use Wikibase\Repo\Domains\Crud\Domain\Services\ItemWriteModelRetriever;
use Wikibase\Repo\Domains\Crud\Infrastructure\JsonDiffJsonPatcher;
use Wikibase\Repo\Tests\Domains\Crud\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\Domains\Crud\Infrastructure\DataAccess\InMemoryItemRepository;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItemLabels\PatchItemLabels
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchItemLabelsTest extends TestCase {

	private ItemLabelsRetriever $labelsRetriever;
	private LabelsSerializer $labelsSerializer;
	private PatchJson $patcher;
	private PatchedItemLabelsValidator $patchedLabelsValidator;
	private ItemWriteModelRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;
	private GetLatestItemRevisionMetadata $getRevisionMetadata;
	private PatchItemLabelsValidator $validator;
	private AssertUserIsAuthorized $assertUserIsAuthorized;

	protected function setUp(): void {
		parent::setUp();

		$this->labelsRetriever = $this->createStub( ItemLabelsRetriever::class );
		$this->labelsSerializer = new LabelsSerializer();
		$this->patcher = new PatchJson( new JsonDiffJsonPatcher() );
		$this->patchedLabelsValidator = new PatchedItemLabelsValidator(
			new LabelsSyntaxValidator( new LabelsDeserializer(), $this->createStub( LabelLanguageCodeValidator::class ) ),
			new ItemLabelsContentsValidator( $this->createStub( ItemLabelValidator::class ) )
		);
		$this->itemRetriever = $this->createStub( ItemWriteModelRetriever::class );
		$this->itemUpdater = $this->createStub( ItemUpdater::class );
		$this->assertUserIsAuthorized = $this->createStub( AssertUserIsAuthorized::class );
		$this->getRevisionMetadata = $this->createStub( GetLatestItemRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )
			->willReturn( [ 321, '20201111070707' ] );
		$this->validator = new TestValidatingRequestDeserializer();
	}

	public function testHappyPath(): void {
		$itemId = new ItemId( 'Q42' );

		$newLabelText = 'pomme de terre';
		$newLabelLanguage = 'fr';

		$editTags = TestValidatingRequestDeserializer::ALLOWED_TAGS;
		$isBot = false;
		$comment = 'labels replaced by ' . __METHOD__;

		$itemRepo = new InMemoryItemRepository();
		$itemRepo->addItem( new Item( $itemId ) );
		$this->labelsRetriever = $itemRepo;
		$this->itemRetriever = $itemRepo;
		$this->itemUpdater = $itemRepo;

		$response = $this->newUseCase()->execute(
			new PatchItemLabelsRequest(
				"$itemId",
				[ [ 'op' => 'add', 'path' => "/$newLabelLanguage", 'value' => $newLabelText ] ],
				$editTags,
				$isBot,
				$comment,
				null
			)
		);

		$this->assertSame( $itemRepo->getLatestRevisionId( $itemId ), $response->getRevisionId() );
		$this->assertSame( $itemRepo->getLatestRevisionTimestamp( $itemId ), $response->getLastModified() );
		$this->assertEquals(
			$response->getLabels(),
			new Labels( new Label( $newLabelLanguage, $newLabelText ) )
		);
		$this->assertEquals(
			new EditMetadata(
				$editTags,
				$isBot,
				LabelsEditSummary::newPatchSummary(
					$comment,
					new TermList(),
					new TermList( [ new Term( $newLabelLanguage, $newLabelText ) ] )
				)
			),
			$itemRepo->getLatestRevisionEditMetadata( $itemId )
		);
	}

	public function testInvalidRequest_throwsException(): void {
		$expectedException = new UseCaseException( 'invalid-label-patch-test' );
		$this->validator = $this->createStub( PatchItemLabelsValidator::class );
		$this->validator->method( 'validateAndDeserialize' )->willThrowException( $expectedException );
		try {
			$this->newUseCase()->execute( $this->createStub( PatchItemLabelsRequest::class ) );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenItemNotFoundOrRedirect_throws(): void {
		$itemId = 'Q789';
		$expectedException = $this->createStub( UseCaseException::class );

		$this->getRevisionMetadata = $this->createStub( GetLatestItemRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )
			->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute(
				new PatchItemLabelsRequest(
					$itemId,
					[ [ 'op' => 'add', 'path' => '/ar', 'value' => 'new arabic label' ] ],
					[],
					false,
					null,
					null
				)
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenPatchJsonError_throwsUseCaseError(): void {
		$expectedError = $this->createStub( UseCaseError::class );

		$this->labelsRetriever = $this->createStub( ItemLabelsRetriever::class );
		$this->labelsRetriever->method( 'getLabels' )
			->willReturn( new Labels( new Label( 'en', 'English Label' ) ) );

		$this->patcher = $this->createMock( PatchJson::class );
		$this->patcher->expects( $this->once() )
			->method( 'execute' )
			->with( [ 'en' => 'English Label' ], [] )
			->willThrowException( $expectedError );

		try {
			$this->newUseCase()->execute( $this->newUseCaseRequest( 'Q123', [] ) );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedError, $e );
		}
	}

	public function testGivenPatchedLabelsInvalid_throwsUseCaseError(): void {
		$item = NewItem::withId( 'Q123' )->build();
		$patchResult = [ 'ar' => '' ];

		$itemRepo = new InMemoryItemRepository();
		$itemRepo->addItem( $item );
		$this->labelsRetriever = $itemRepo;
		$this->itemRetriever = $itemRepo;

		$expectedUseCaseError = $this->createStub( UseCaseError::class );
		$this->patchedLabelsValidator = $this->createMock( PatchedItemLabelsValidator::class );
		$this->patchedLabelsValidator->expects( $this->once() )
			->method( 'validateAndDeserialize' )
			->with( new TermList(), new TermList(), $patchResult )
			->willThrowException( $expectedUseCaseError );

		try {
			$this->newUseCase()->execute(
				new PatchItemLabelsRequest(
					$item->getId()->getSerialization(),
					[ [ 'op' => 'add', 'path' => '/ar', 'value' => '' ] ],
					[],
					false,
					null,
					null
				)
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedUseCaseError, $e );
		}
	}

	public function testGivenEditIsUnauthorized_throwsUseCaseError(): void {
		$itemId = new ItemId( 'Q123' );

		$expectedError = new UseCaseError(
			UseCaseError::PERMISSION_DENIED_UNKNOWN_REASON,
			'You have no permission to edit this item.'
		);
		$this->assertUserIsAuthorized = $this->createMock( AssertUserIsAuthorized::class );
		$this->assertUserIsAuthorized->method( 'checkEditPermissions' )
			->with( $itemId, User::newAnonymous() )
			->willThrowException( $expectedError );

		try {
			$this->newUseCase()->execute(
				new PatchItemLabelsRequest(
					"$itemId",
					[ [ 'op' => 'remove', 'path' => '/en' ] ],
					[],
					false,
					null,
					null
				)
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedError, $e );
		}
	}

	private function newUseCase(): PatchItemLabels {
		return new PatchItemLabels(
			new AssertItemExists( $this->getRevisionMetadata ),
			$this->labelsRetriever,
			$this->labelsSerializer,
			$this->patcher,
			$this->patchedLabelsValidator,
			$this->itemRetriever,
			$this->itemUpdater,
			$this->validator,
			$this->assertUserIsAuthorized
		);
	}

	private function newUseCaseRequest( string $itemId, array $patch ): PatchItemLabelsRequest {
		return new PatchItemLabelsRequest( $itemId, $patch, [], false, '', null );
	}

}
