<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\AddItemAliasesInLanguage;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\RestApi\Application\UseCases\AddItemAliasesInLanguage\AddItemAliasesInLanguage;
use Wikibase\Repo\RestApi\Application\UseCases\AddItemAliasesInLanguage\AddItemAliasesInLanguageRequest;
use Wikibase\Repo\RestApi\Application\UseCases\AddItemAliasesInLanguage\AddItemAliasesInLanguageResponse;
use Wikibase\Repo\RestApi\Application\UseCases\AssertItemExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Domain\Model\AliasesInLanguageEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\ReadModel\AliasesInLanguage;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\InMemoryItemRepository;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\AddItemAliasesInLanguage\AddItemAliasesInLanguage
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class AddItemAliasesInLanguageTest extends TestCase {

	private ItemRetriever $itemRetriever;
	private AssertItemExists $assertItemExists;
	private AssertUserIsAuthorized $assertUserIsAuthorized;
	private ItemUpdater $itemUpdater;

	protected function setUp(): void {
		parent::setUp();

		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->assertItemExists = $this->createStub( AssertItemExists::class );
		$this->assertUserIsAuthorized = $this->createStub( AssertUserIsAuthorized::class );
		$this->itemUpdater = $this->createStub( ItemUpdater::class );
	}

	public function testCreateAliases(): void {
		$languageCode = 'en';
		$itemId = new ItemId( 'Q123' );
		$aliasesToCreate = [ 'alias 1', 'alias 2' ];
		$editTags = [ TestValidatingRequestDeserializer::ALLOWED_TAGS[0] ];
		$isBot = false;
		$comment = 'potato';

		$itemRepo = new InMemoryItemRepository();
		$itemRepo->addItem( new Item( $itemId ) );
		$this->itemRetriever = $itemRepo;
		$this->itemUpdater = $itemRepo;

		$response = $this->newUseCase()->execute(
			new AddItemAliasesInLanguageRequest(
				"$itemId",
				$languageCode,
				$aliasesToCreate,
				$editTags,
				$isBot,
				$comment,
				null
			)
		);

		$this->assertInstanceOf( AddItemAliasesInLanguageResponse::class, $response );
		$this->assertEquals( new AliasesInLanguage( $languageCode, $aliasesToCreate ), $response->getAliases() );
		$this->assertFalse( $response->wasAddedToExistingAliasGroup() );
		$this->assertSame( $itemRepo->getLatestRevisionId( $itemId ), $response->getRevisionId() );
		$this->assertSame( $itemRepo->getLatestRevisionTimestamp( $itemId ), $response->getLastModified() );
		$this->assertEquals(
			new EditMetadata(
				$editTags,
				$isBot,
				AliasesInLanguageEditSummary::newAddSummary( $comment, new AliasGroup( $languageCode, $aliasesToCreate ) )
			),
			$itemRepo->getLatestRevisionEditMetadata( $itemId )
		);
	}

	public function testAddAliases(): void {
		$languageCode = 'en';
		$existingAliases = [ 'alias 1', 'alias 2' ];
		$item = NewItem::withId( 'Q123' )->andAliases( $languageCode, $existingAliases )->build();
		$aliasesToAdd = [ 'alias 3', 'alias 4' ];

		$request = $this->newRequest( "{$item->getId()}", $languageCode, $aliasesToAdd );

		$itemRepo = new InMemoryItemRepository();
		$itemRepo->addItem( $item );
		$this->itemRetriever = $itemRepo;
		$this->itemUpdater = $itemRepo;

		$response = $this->newUseCase()->execute( $request );

		$this->assertInstanceOf( AddItemAliasesInLanguageResponse::class, $response );
		$this->assertEquals(
			new AliasesInLanguage( $languageCode, array_merge( $existingAliases, $aliasesToAdd ) ),
			$response->getAliases()
		);
		$this->assertEquals(
			new EditMetadata(
				[],
				false,
				AliasesInLanguageEditSummary::newAddSummary( null, new AliasGroup( $languageCode, $aliasesToAdd ) )
			),
			$itemRepo->getLatestRevisionEditMetadata( $item->getId() )
		);
		$this->assertTrue( $response->wasAddedToExistingAliasGroup() );
	}

	public function testValidationError_throwsUseCaseError(): void {
		try {
			$this->newUseCase()->execute(
				new AddItemAliasesInLanguageRequest( 'Q123', 'en', [ '' ], [], false, null, null )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::ALIAS_EMPTY, $e->getErrorCode() );
		}
	}

	public function testGivenItemDoesNotExistOrRedirect_throws(): void {
		$expectedException = $this->createStub( UseCaseException::class );
		$this->assertItemExists = $this->createStub( AssertItemExists::class );
		$this->assertItemExists->method( 'execute' )->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute( $this->newRequest( 'Q999', 'en', [ 'a' ] ) );
			$this->fail( 'expected exception not thrown' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenUserUnauthorized_throws(): void {
		$expectedException = $this->createStub( UseCaseError::class );
		$this->assertUserIsAuthorized = $this->createStub( AssertUserIsAuthorized::class );
		$this->assertUserIsAuthorized->method( 'execute' )->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute( $this->newRequest( 'Q1', 'en', [ 'a' ] ) );
			$this->fail( 'expected exception not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	private function newUseCase(): AddItemAliasesInLanguage {
		return new AddItemAliasesInLanguage(
			$this->itemRetriever,
			$this->assertItemExists,
			$this->assertUserIsAuthorized,
			$this->itemUpdater,
			new TestValidatingRequestDeserializer()
		);
	}

	private function newRequest(
		string $itemId,
		string $languageCode,
		array $aliases,
		array $tags = [],
		bool $isBot = false,
		string $comment = null,
		string $username = null
	): AddItemAliasesInLanguageRequest {
		return new AddItemAliasesInLanguageRequest( $itemId, $languageCode, $aliases, $tags, $isBot, $comment, $username );
	}
}
