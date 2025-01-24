<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\RemoveItemLabel;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\RestApi\Application\UseCases\AssertItemExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveItemLabel\RemoveItemLabel;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveItemLabel\RemoveItemLabelRequest;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveItemLabel\RemoveItemLabelValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\LabelEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\User;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Domain\Services\ItemWriteModelRetriever;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\InMemoryItemRepository;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\RemoveItemLabel\RemoveItemLabel
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 *
 */
class RemoveItemLabelTest extends TestCase {

	private RemoveItemLabelValidator $validator;
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
		$label = 'test label';
		$tags = TestValidatingRequestDeserializer::ALLOWED_TAGS;
		$isBot = false;
		$comment = 'test';

		$itemRepo = new InMemoryItemRepository();
		$itemRepo->addItem( NewItem::withId( $itemId )->andLabel( $languageCode, $label )->build() );
		$this->itemRetriever = $itemRepo;
		$this->itemUpdater = $itemRepo;

		$this->newUseCase()->execute(
			new RemoveItemLabelRequest( (string)$itemId, $languageCode, $tags, $isBot, $comment, null )
		);

		$this->assertFalse( $itemRepo->getItemWriteModel( $itemId )->getLabels()->hasTermForLanguage( $languageCode ) );
		$this->assertEquals(
			new EditMetadata( $tags, $isBot, LabelEditSummary::newRemoveSummary(
				$comment,
				new Term( $languageCode, $label )
			) ),
			$itemRepo->getLatestRevisionEditMetadata( $itemId )
		);
	}

	public function testInvalidRequest_throwsException(): void {
		$expectedException = new UseCaseException( 'invalid-remove-label-test' );
		$this->validator = $this->createStub( RemoveItemLabelValidator::class );
		$this->validator->method( 'validateAndDeserialize' )->willThrowException( $expectedException );
		try {
			$this->newUseCase()->execute( $this->createStub( RemoveItemLabelRequest::class ) );
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
			$request = new RemoveItemLabelRequest( (string)$itemId, 'en', $editTags, false, 'test', null );
			$this->newUseCase()->execute( $request );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenLabelDoesNotExist_throws(): void {
		$itemId = new ItemId( 'Q123' );
		$language = 'en';
		$editTags = [ TestValidatingRequestDeserializer::ALLOWED_TAGS[ 1 ] ];

		$this->itemRetriever = $this->createStub( ItemWriteModelRetriever::class );
		$this->itemRetriever->method( 'getItemWriteModel' )->willReturn( NewItem::withId( $itemId )->build() );

		try {
			$request = new RemoveItemLabelRequest( (string)$itemId, $language, $editTags, false, 'test', null );
			$this->newUseCase()->execute( $request );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::RESOURCE_NOT_FOUND, $e->getErrorCode() );
			$this->assertSame( 'The requested resource does not exist', $e->getErrorMessage() );
			$this->assertSame( [ 'resource_type' => 'label' ], $e->getErrorContext() );
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
			$this->newUseCase()->execute( new RemoveItemLabelRequest( "$itemId", 'en', [], false, null, null ) );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedError, $e );
		}
	}

	private function newUseCase(): RemoveItemLabel {
		return new RemoveItemLabel(
			$this->validator,
			$this->assertItemExists,
			$this->assertUserIsAuthorized,
			$this->itemRetriever,
			$this->itemUpdater
		);
	}

}
