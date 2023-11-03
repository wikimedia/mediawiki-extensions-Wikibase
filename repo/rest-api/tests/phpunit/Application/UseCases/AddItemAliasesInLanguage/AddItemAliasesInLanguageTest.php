<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\AddItemAliasesInLanguage;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\RestApi\Application\UseCases\AddItemAliasesInLanguage\AddItemAliasesInLanguage;
use Wikibase\Repo\RestApi\Application\UseCases\AddItemAliasesInLanguage\AddItemAliasesInLanguageRequest;
use Wikibase\Repo\RestApi\Application\UseCases\AddItemAliasesInLanguage\AddItemAliasesInLanguageResponse;
use Wikibase\Repo\RestApi\Application\UseCases\AssertItemExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Domain\Model\EditSummary;
use Wikibase\Repo\RestApi\Domain\ReadModel\Aliases;
use Wikibase\Repo\RestApi\Domain\ReadModel\AliasesInLanguage;
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
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\AddItemAliasesInLanguage\AddItemAliasesInLanguage
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class AddItemAliasesInLanguageTest extends TestCase {

	use EditMetadataHelper;

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
		$item = NewItem::withId( 'Q123' )->build();
		$aliasesToCreate = [ 'alias 1', 'alias 2' ];
		$postModificationRevisionId = 322;
		$modificationTimestamp = '20221111070707';
		$editTags = [ TestValidatingRequestDeserializer::ALLOWED_TAGS[0] ];
		$isBot = false;
		$comment = 'potato';

		$request = new AddItemAliasesInLanguageRequest(
			$item->getId()->getSerialization(),
			$languageCode,
			$aliasesToCreate,
			$editTags,
			$isBot,
			$comment,
			null
		);

		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemRetriever->method( 'getItem' )->willReturn( $item );

		$updatedItem = new ReadModelItem(
			new Labels(),
			new Descriptions(),
			new Aliases( new AliasesInLanguage( $languageCode, $aliasesToCreate ) ),
			new StatementList()
		);
		$this->itemUpdater = $this->createMock( ItemUpdater::class );
		$this->itemUpdater->method( 'update' )
			->with(
				$this->callback(
					fn( Item $item ) => $item->getAliasGroups()
						->getByLanguage( $languageCode )
						->equals( new AliasGroup( $languageCode, $aliasesToCreate ) )
				),
				$this->expectEquivalentMetadata( $editTags, $isBot, $comment, EditSummary::ADD_ACTION )
			)
			->willReturn( new ItemRevision( $updatedItem, $modificationTimestamp, $postModificationRevisionId ) );

		$response = $this->newUseCase()->execute( $request );

		$this->assertInstanceOf( AddItemAliasesInLanguageResponse::class, $response );
		$this->assertEquals( new AliasesInLanguage( $languageCode, $aliasesToCreate ), $response->getAliases() );
		$this->assertFalse( $response->wasAddedToExistingAliasGroup() );
		$this->assertSame( $postModificationRevisionId, $response->getRevisionId() );
		$this->assertSame( $modificationTimestamp, $response->getLastModified() );
	}

	public function testAddAliases(): void {
		$languageCode = 'en';
		$existingAliases = [ 'alias 1', 'alias 2' ];
		$item = NewItem::withId( 'Q123' )->andAliases( $languageCode, $existingAliases )->build();
		$aliasesToAdd = [ 'alias 3', 'alias 4' ];
		$postModificationRevisionId = 322;
		$modificationTimestamp = '20221111070707';

		$request = $this->newRequest( "{$item->getId()}", $languageCode, $aliasesToAdd );

		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemRetriever->method( 'getItem' )->willReturn( $item );

		$updatedAliases = array_merge( $existingAliases, $aliasesToAdd );
		$updatedItem = new ReadModelItem(
			new Labels(),
			new Descriptions(),
			new Aliases( new AliasesInLanguage( $languageCode, $updatedAliases ) ),
			new StatementList()
		);
		$this->itemUpdater = $this->createMock( ItemUpdater::class );
		$this->itemUpdater->method( 'update' )
			->with(
				$this->callback(
					fn( Item $item ) => $item->getAliasGroups()
						->getByLanguage( $languageCode )
						->equals( new AliasGroup( $languageCode, $updatedAliases ) )
				),
			)
			->willReturn( new ItemRevision( $updatedItem, $modificationTimestamp, $postModificationRevisionId ) );

		$response = $this->newUseCase()->execute( $request );

		$this->assertInstanceOf( AddItemAliasesInLanguageResponse::class, $response );
		$this->assertEquals( new AliasesInLanguage( $languageCode, $updatedAliases ), $response->getAliases() );
		$this->assertTrue( $response->wasAddedToExistingAliasGroup() );
		$this->assertSame( $postModificationRevisionId, $response->getRevisionId() );
		$this->assertSame( $modificationTimestamp, $response->getLastModified() );
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

	public function testAddDuplicateAlias_throwsUseCaseError(): void {
		$itemId = new ItemId( 'Q123' );
		$aliases = new AliasGroupList( [ new AliasGroup( 'en', [ 'duplicate alias' ] ) ] );
		$item = new Item( $itemId, new Fingerprint( null, null, $aliases ) );

		$this->itemRetriever = $this->createMock( ItemRetriever::class );
		$this->itemRetriever->method( 'getItem' )
			->with( $itemId )
			->willReturn( $item );

		try {
			$this->newUseCase()->execute(
				$this->newRequest( "$itemId", 'en', [ 'duplicate alias' ] )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::ALIAS_DUPLICATE, $e->getErrorCode() );
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
