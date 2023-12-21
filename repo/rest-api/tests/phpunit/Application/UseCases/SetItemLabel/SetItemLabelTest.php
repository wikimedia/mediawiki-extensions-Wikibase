<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\SetItemLabel;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\RestApi\Application\UseCases\AssertItemExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemLabel\SetItemLabel;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemLabel\SetItemLabelRequest;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemLabel\SetItemLabelValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\LabelEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\User;
use Wikibase\Repo\RestApi\Domain\ReadModel\Label;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\InMemoryItemRepository;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\SetItemLabel\SetItemLabel
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SetItemLabelTest extends TestCase {

	private SetItemLabelValidator $validator;
	private AssertItemExists $assertItemExists;
	private ItemRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;
	private AssertUserIsAuthorized $assertUserIsAuthorized;

	protected function setUp(): void {
		parent::setUp();

		$this->validator = new TestValidatingRequestDeserializer();
		$this->assertItemExists = $this->createStub( AssertItemExists::class );
		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemUpdater = $this->createStub( ItemUpdater::class );
		$this->assertUserIsAuthorized = $this->createStub( AssertUserIsAuthorized::class );
	}

	public function testAddLabel(): void {
		$itemId = new ItemId( 'Q123' );
		$langCode = 'en';
		$newLabelText = 'New label';
		$editTags = TestValidatingRequestDeserializer::ALLOWED_TAGS;
		$isBot = false;
		$comment = "{$this->getName()} Comment";

		$itemRepo = new InMemoryItemRepository();
		$itemRepo->addItem( NewItem::withId( $itemId )->build() );
		$this->itemRetriever = $itemRepo;
		$this->itemUpdater = $itemRepo;

		$response = $this->newUseCase()->execute(
			new SetItemLabelRequest( "$itemId", $langCode, $newLabelText, $editTags, $isBot, $comment, null )
		);

		$this->assertEquals( new Label( $langCode, $newLabelText ), $response->getLabel() );
		$this->assertSame( $itemRepo->getLatestRevisionId( $itemId ), $response->getRevisionId() );
		$this->assertSame( $itemRepo->getLatestRevisionTimestamp( $itemId ), $response->getLastModified() );
		$this->assertEquals(
			new EditMetadata(
				$editTags,
				$isBot,
				LabelEditSummary::newAddSummary( $comment, new Term( $langCode, $newLabelText ) )
			),
			$itemRepo->getLatestRevisionEditMetadata( $itemId )
		);
		$this->assertFalse( $response->wasReplaced() );
	}

	public function testReplaceLabel(): void {
		$itemId = new ItemId( 'Q123' );
		$langCode = 'en';
		$updatedLabelText = 'Replaced label';
		$editTags = TestValidatingRequestDeserializer::ALLOWED_TAGS;
		$isBot = false;
		$comment = "{$this->getName()} Comment";

		$itemRepo = new InMemoryItemRepository();
		$itemRepo->addItem( NewItem::withId( $itemId )->andLabel( $langCode, 'Label to replace' )->build() );
		$this->itemRetriever = $itemRepo;
		$this->itemUpdater = $itemRepo;

		$response = $this->newUseCase()->execute(
			new SetItemLabelRequest( "$itemId", $langCode, $updatedLabelText, $editTags, $isBot, $comment, null )
		);

		$this->assertEquals( new Label( $langCode, $updatedLabelText ), $response->getLabel() );
		$this->assertSame( $itemRepo->getLatestRevisionId( $itemId ), $response->getRevisionId() );
		$this->assertSame( $itemRepo->getLatestRevisionTimestamp( $itemId ), $response->getLastModified() );
		$this->assertEquals(
			new EditMetadata(
				$editTags,
				$isBot,
				LabelEditSummary::newReplaceSummary( $comment, new Term( $langCode, $updatedLabelText ) )
			),
			$itemRepo->getLatestRevisionEditMetadata( $itemId )
		);
		$this->assertTrue( $response->wasReplaced() );
	}

	public function testGivenInvalidRequest_throwsUseCaseException(): void {
		$expectedException = new UseCaseException( 'invalid-label-test' );
		$this->validator = $this->createStub( SetItemLabelValidator::class );
		$this->validator->method( 'validateAndDeserialize' )->willThrowException( $expectedException );
		try {
			$this->newUseCase()->execute(
				new SetItemLabelRequest( 'Q123', 'en', 'label', [], false, null, null )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenItemNotFoundOrRedirect_throws(): void {
		$expectedException = $this->createStub( UseCaseException::class );
		$this->assertItemExists->method( 'execute' )
			->willThrowException( $expectedException );
		try {
			$this->newUseCase()->execute( new SetItemLabelRequest( 'Q321', 'en', 'test label', [], false, null, null ) );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenEditIsUnauthorized_throwsUseCaseError(): void {
		$itemId = new ItemId( 'Q123' );

		$expectedError = new UseCaseError(
			UseCaseError::PERMISSION_DENIED,
			'You have no permission to edit this item.'
		);
		$this->assertUserIsAuthorized = $this->createMock( AssertUserIsAuthorized::class );
		$this->assertUserIsAuthorized->method( 'execute' )
			->with( $itemId, User::newAnonymous() )
			->willThrowException( $expectedError );

		try {
			$this->newUseCase()->execute(
				new SetItemLabelRequest( "$itemId", 'en', 'test label', [], false, null, null )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedError, $e );
		}
	}

	private function newUseCase(): SetItemLabel {
		return new SetItemLabel(
			$this->validator,
			$this->assertItemExists,
			$this->itemRetriever,
			$this->itemUpdater,
			$this->assertUserIsAuthorized
		);
	}

}
