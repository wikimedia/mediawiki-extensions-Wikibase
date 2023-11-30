<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\SetItemDescription;

use Wikibase\DataModel\Entity\Item as DataModelItem;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\RestApi\Application\UseCases\AssertItemExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemDescription\SetItemDescription;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemDescription\SetItemDescriptionRequest;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemDescription\SetItemDescriptionValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Domain\Model\EditSummary;
use Wikibase\Repo\RestApi\Domain\Model\User;
use Wikibase\Repo\RestApi\Domain\ReadModel\Aliases;
use Wikibase\Repo\RestApi\Domain\ReadModel\Description;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\Domain\ReadModel\Item as ReadModelItem;
use Wikibase\Repo\RestApi\Domain\ReadModel\ItemRevision;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\RestApi\Domain\Model\EditMetadataHelper;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\SetItemDescription\SetItemDescription
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SetItemDescriptionTest extends \PHPUnit\Framework\TestCase {

	use EditMetadataHelper;

	private SetItemDescriptionValidator $validator;
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

	public function testAddDescription(): void {
		$language = 'en';
		$description = 'Hello world again.';
		$itemId = 'Q123';
		$editTags = TestValidatingRequestDeserializer::ALLOWED_TAGS;
		$isBot = false;
		$comment = 'add description edit comment';

		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemRetriever->method( 'getItem' )->willReturn( new DataModelItem() );

		$updatedItem = $this->newReadModelItemWithDescriptions(
			new Descriptions( new Description( $language, $description ) )
		);
		$revisionId = 123;
		$lastModified = '20221212040506';

		$this->itemUpdater = $this->createMock( ItemUpdater::class );
		$this->itemUpdater->expects( $this->once() )
			->method( 'update' )
			->with(
				$this->callback( fn( DataModelItem $item ) => $item->getDescriptions()->toTextArray() === [ $language => $description ] ),
				$this->expectEquivalentMetadata( $editTags, $isBot, $comment, EditSummary::ADD_ACTION )
			)
			->willReturn( new ItemRevision( $updatedItem, $lastModified, $revisionId ) );

		$response = $this->newUseCase()->execute(
			new SetItemDescriptionRequest( $itemId, $language, $description, $editTags, $isBot, $comment, null )
		);

		$this->assertEquals( new Description( $language, $description ), $response->getDescription() );
		$this->assertSame( $revisionId, $response->getRevisionId() );
		$this->assertSame( $lastModified, $response->getLastModified() );
		$this->assertFalse( $response->wasReplaced() );
	}

	public function testReplaceDescription(): void {
		$language = 'en';
		$newDescription = 'Hello world again.';
		$itemId = 'Q123';
		$editTags = TestValidatingRequestDeserializer::ALLOWED_TAGS;
		$isBot = false;
		$item = NewItem::withId( $itemId )->andDescription( $language, 'Hello world' )->build();
		$comment = 'replace description edit comment';

		$this->itemRetriever = $this->createMock( ItemRetriever::class );
		$this->itemRetriever
			->expects( $this->once() )
			->method( 'getItem' )
			->with( $itemId )
			->willReturn( $item );

		$updatedItem = $this->newReadModelItemWithDescriptions(
			new Descriptions( new Description( $language, $newDescription ) )
		);
		$revisionId = 123;
		$lastModified = '20221212040506';

		$this->itemUpdater = $this->createMock( ItemUpdater::class );
		$this->itemUpdater->expects( $this->once() )
			->method( 'update' )
			->with(
				$this->callback(
					fn( DataModelItem $item ) => $item->getDescriptions()->toTextArray() === [ $language => $newDescription ]
				),
				$this->expectEquivalentMetadata( $editTags, $isBot, $comment, EditSummary::REPLACE_ACTION )
			)
			->willReturn( new ItemRevision( $updatedItem, $lastModified, $revisionId ) );

		$response = $this->newUseCase()->execute(
			new SetItemDescriptionRequest( $itemId, $language, $newDescription, $editTags, $isBot, $comment, null )
		);

		$this->assertEquals( new Description( $language, $newDescription ), $response->getDescription() );
		$this->assertSame( $revisionId, $response->getRevisionId() );
		$this->assertSame( $lastModified, $response->getLastModified() );
		$this->assertTrue( $response->wasReplaced() );
	}

	public function testGivenInvalidRequest_throwsUseCaseException(): void {
		$expectedException = new UseCaseException( 'invalid-description-test' );

		$this->validator = $this->createStub( SetItemDescriptionValidator::class );
		$this->validator->method( 'validateAndDeserialize' )->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute(
				new SetItemDescriptionRequest( 'Q123', 'en', 'description', [], false, null, null )
			);

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenItemNotFoundOrRedirect_throws(): void {
		$itemId = 'Q789';
		$expectedException = $this->createStub( UseCaseException::class );
		$this->assertItemExists->method( 'execute' )
			->willThrowException( $expectedException );
		try {
			$this->newUseCase()->execute( new SetItemDescriptionRequest( $itemId, 'en', 'test description', [], false, null, null ) );
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
			$this->newUseCase()->execute( new SetItemDescriptionRequest(
				"$itemId",
				'en',
				'test description',
				[],
				false,
				null,
				null
			) );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedError, $e );
		}
	}

	public function newReadModelItemWithDescriptions( Descriptions $descriptions ): ReadModelItem {
		return new ReadModelItem(
			new Labels(),
			$descriptions,
			new Aliases(),
			new StatementList()
		);
	}

	private function newUseCase(): SetItemDescription {
		return new SetItemDescription(
			$this->validator,
			$this->assertItemExists,
			$this->itemRetriever,
			$this->itemUpdater,
			$this->assertUserIsAuthorized
		);
	}

}
