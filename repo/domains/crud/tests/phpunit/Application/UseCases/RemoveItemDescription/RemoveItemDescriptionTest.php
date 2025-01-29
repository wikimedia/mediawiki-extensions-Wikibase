<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Application\UseCases\RemoveItemDescription;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertItemExists;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveItemDescription\RemoveItemDescription;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveItemDescription\RemoveItemDescriptionRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveItemDescription\RemoveItemDescriptionValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseException;
use Wikibase\Repo\Domains\Crud\Domain\Model\DescriptionEditSummary;
use Wikibase\Repo\Domains\Crud\Domain\Model\EditMetadata;
use Wikibase\Repo\Domains\Crud\Domain\Model\User;
use Wikibase\Repo\Domains\Crud\Domain\Services\ItemUpdater;
use Wikibase\Repo\Domains\Crud\Domain\Services\ItemWriteModelRetriever;
use Wikibase\Repo\Tests\Domains\Crud\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\Domains\Crud\Infrastructure\DataAccess\InMemoryItemRepository;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveItemDescription\RemoveItemDescription
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 *
 */
class RemoveItemDescriptionTest extends TestCase {

	private RemoveItemDescriptionValidator $validator;
	private AssertItemExists $assertItemExists;
	private AssertUserIsAuthorized $assertUserIsAuthorized;
	private ItemWriteModelRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;

	protected function setUp(): void {
		parent::setUp();

		$this->validator = new TestValidatingRequestDeserializer();
		$this->assertItemExists = $this->createStub( AssertItemExists::class );
		$this->assertUserIsAuthorized = $this->createStub( AssertUserIsAuthorized::class );
		$this->itemRetriever = $this->createStub( ItemWriteModelRetriever::class );
		$this->itemUpdater = $this->createStub( ItemUpdater::class );
	}

	public function testHappyPath(): void {
		$itemId = new ItemId( 'Q123' );
		$languageCode = 'en';
		$description = 'test description';
		$tags = TestValidatingRequestDeserializer::ALLOWED_TAGS;
		$isBot = false;
		$comment = 'test';

		$itemRepo = new InMemoryItemRepository();
		$itemRepo->addItem( NewItem::withId( $itemId )->andDescription( $languageCode, $description )->build() );
		$this->itemRetriever = $itemRepo;
		$this->itemUpdater = $itemRepo;

		$this->newUseCase()->execute(
			new RemoveItemDescriptionRequest( "$itemId", $languageCode, $tags, $isBot, $comment, null )
		);

		$this->assertFalse( $itemRepo->getItemWriteModel( $itemId )->getDescriptions()->hasTermForLanguage( $languageCode ) );
		$this->assertEquals(
			new EditMetadata( $tags, $isBot, DescriptionEditSummary::newRemoveSummary(
				$comment,
				new Term( $languageCode, $description )
			) ),
			$itemRepo->getLatestRevisionEditMetadata( $itemId )
		);
	}

	public function testInvalidRequest_throwsException(): void {
		$expectedException = new UseCaseException( 'invalid-remove-description-test' );
		$this->validator = $this->createStub( RemoveItemDescriptionValidator::class );
		$this->validator->method( 'validateAndDeserialize' )->willThrowException( $expectedException );
		try {
			$this->newUseCase()->execute( $this->createStub( RemoveItemDescriptionRequest::class ) );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenItemNotFoundOrRedirect_throws(): void {
		$itemId = new ItemId( 'Q123' );
		$editTags = [ TestValidatingRequestDeserializer::ALLOWED_TAGS[ 0 ] ];

		$expectedException = $this->createStub( UseCaseException::class );
		$this->assertItemExists = $this->createMock( AssertItemExists::class );
		$this->assertItemExists->expects( $this->once() )
			->method( 'execute' )
			->with( $itemId )
			->willThrowException( $expectedException );

		try {
			$request = new RemoveItemDescriptionRequest( (string)$itemId, 'en', $editTags, false, 'test', null );
			$this->newUseCase()->execute( $request );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenDescriptionDoesNotExist_throws(): void {
		$itemId = new ItemId( 'Q123' );
		$language = 'en';
		$editTags = [ TestValidatingRequestDeserializer::ALLOWED_TAGS[ 1 ] ];

		$this->itemRetriever = $this->createStub( ItemWriteModelRetriever::class );
		$this->itemRetriever->method( 'getItemWriteModel' )->willReturn( NewItem::withId( $itemId )->build() );

		try {
			$request = new RemoveItemDescriptionRequest( (string)$itemId, $language, $editTags, false, 'test', null );
			$this->newUseCase()->execute( $request );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::RESOURCE_NOT_FOUND, $e->getErrorCode() );
			$this->assertSame( 'The requested resource does not exist', $e->getErrorMessage() );
			$this->assertSame( [ 'resource_type' => 'description' ], $e->getErrorContext() );
		}
	}

	public function testGivenEditIsUnauthorized_throwsUseCaseError(): void {
		$itemId = new ItemId( 'Q123' );

		$expectedError = new UseCaseError( UseCaseError::PERMISSION_DENIED_UNKNOWN_REASON, 'You have no permission to edit this item.' );
		$this->assertUserIsAuthorized = $this->createMock( AssertUserIsAuthorized::class );
		$this->assertUserIsAuthorized->method( 'checkEditPermissions' )
			->with( $itemId, User::newAnonymous() )
			->willThrowException( $expectedError );

		try {
			$this->newUseCase()->execute( new RemoveItemDescriptionRequest( "$itemId", 'en', [], false, null, null ) );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedError, $e );
		}
	}

	private function newUseCase(): RemoveItemDescription {
		return new RemoveItemDescription(
			$this->validator,
			$this->assertItemExists,
			$this->assertUserIsAuthorized,
			$this->itemRetriever,
			$this->itemUpdater
		);
	}

}
