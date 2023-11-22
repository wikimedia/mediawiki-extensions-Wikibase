<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\PatchItemAliases;

use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item as DataModelItem;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\AssertItemExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemAliases\PatchedAliasesValidator;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemAliases\PatchItemAliases;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemAliases\PatchItemAliasesRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemAliases\PatchItemAliasesValidator;
use Wikibase\Repo\RestApi\Application\UseCases\PatchJson;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Domain\Model\AliasesEditSummary;
use Wikibase\Repo\RestApi\Domain\ReadModel\Aliases;
use Wikibase\Repo\RestApi\Domain\ReadModel\AliasesInLanguage;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\Domain\ReadModel\Item;
use Wikibase\Repo\RestApi\Domain\ReadModel\ItemRevision;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;
use Wikibase\Repo\RestApi\Domain\Services\ItemAliasesRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Infrastructure\JsonDiffJsonPatcher;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\RestApi\Domain\Model\EditMetadataHelper;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\PatchItemAliases\PatchItemAliases
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchItemAliasesTest extends TestCase {

	use EditMetadataHelper;

	private PatchItemAliasesValidator $validator;
	private AssertItemExists $assertItemExists;
	private AssertUserIsAuthorized $assertUserIsAuthorized;
	private ItemAliasesRetriever $aliasesRetriever;
	private AliasesSerializer $aliasesSerializer;
	private PatchJson $patchJson;
	private PatchedAliasesValidator $patchedAliasesValidator;
	private ItemRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;

	protected function setUp(): void {
		parent::setUp();

		$this->validator = new TestValidatingRequestDeserializer();
		$this->assertItemExists = $this->createStub( AssertItemExists::class );
		$this->assertUserIsAuthorized = $this->createStub( AssertUserIsAuthorized::class );
		$this->aliasesRetriever = $this->createStub( ItemAliasesRetriever::class );
		$this->aliasesRetriever->method( 'getAliases' )->willReturn( new Aliases() );
		$this->aliasesSerializer = new AliasesSerializer();
		$this->patchJson = new PatchJson( new JsonDiffJsonPatcher() );
		$this->patchedAliasesValidator = $this->createStub( PatchedAliasesValidator::class );
		$this->patchedAliasesValidator->method( 'validateAndDeserialize' )
			->willReturnCallback( [ new AliasesDeserializer(), 'deserialize' ] );
		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemUpdater = $this->createStub( ItemUpdater::class );
	}

	public function testHappyPath(): void {
		$newAliasText = 'another English alias';
		$aliasLanguage = 'en';

		$itemId = new ItemId( 'Q42' );
		$item = NewItem::withId( $itemId )->andAliases( $aliasLanguage, [ 'English alias' ] )->build();

		$this->aliasesRetriever = $this->createStub( ItemAliasesRetriever::class );
		$this->aliasesRetriever->method( 'getAliases' )
			->willReturn( new Aliases( new AliasesInLanguage( $aliasLanguage, [ 'English alias' ] ) ) );

		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemRetriever->method( 'getItem' )->willReturn( $item );

		$revisionId = 657;
		$lastModified = '20221212040506';
		$editTags = TestValidatingRequestDeserializer::ALLOWED_TAGS;
		$isBot = false;
		$comment = 'aliases patched by ' . __method__;

		$updatedItem = new Item(
			new Labels(),
			new Descriptions(),
			new Aliases( new AliasesInLanguage( $aliasLanguage, [ 'English alias', $newAliasText ] ) ),
			new StatementList()
		);
		$this->itemUpdater = $this->createMock( ItemUpdater::class );
		$this->itemUpdater->expects( $this->once() )
			->method( 'update' )
			->with(
				$this->expectItemWithAliases( $aliasLanguage, [ 'English alias', $newAliasText ] ),
				$this->expectEquivalentMetadata( $editTags, $isBot, $comment, AliasesEditSummary::PATCH_ACTION )
			)
			->willReturn( new ItemRevision( $updatedItem, $lastModified, $revisionId ) );

		$response = $this->newUseCase()->execute(
			new PatchItemAliasesRequest(
				(string)$itemId,
				[ [ 'op' => 'add', 'path' => "/$aliasLanguage/-", 'value' => $newAliasText ] ],
				$editTags,
				$isBot,
				$comment,
				null
			)
		);

		$this->assertSame( $response->getAliases(), $updatedItem->getAliases() );
	}

	public function testGivenInvalidRequest_throws(): void {
		$expectedException = new UseCaseException( 'invalid-alias-patch-test' );
		$this->validator = $this->createStub( PatchItemAliasesValidator::class );
		$this->validator->method( 'validateAndDeserialize' )->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute( $this->createStub( PatchItemAliasesRequest::class ) );
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
			$this->newUseCase()->execute( $this->newUseCaseRequest( $itemId, [] ) );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenErrorWhilePatch_throws(): void {
		$expectedException = $this->createStub( UseCaseError::class );
		$this->patchJson = $this->createStub( PatchJson::class );
		$this->patchJson->method( 'execute' )->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute( $this->newUseCaseRequest( 'Q123', [] ) );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenUnauthorizedRequest_throws(): void {
		$user = 'bad-user';
		$itemId = new ItemId( 'Q123' );
		$expectedException = $this->createStub( UseCaseError::class );

		$this->assertUserIsAuthorized = $this->createMock( AssertUserIsAuthorized::class );
		$this->assertUserIsAuthorized->expects( $this->once() )
			->method( 'execute' )
			->with( $itemId, $user )
			->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute( new PatchItemAliasesRequest( (string)$itemId, [], [], false, null, $user ) );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenPatchedAliasesInvalid_throws(): void {
		$expectedException = $this->createStub( UseCaseError::class );
		$this->patchedAliasesValidator = $this->createStub( PatchedAliasesValidator::class );
		$this->patchedAliasesValidator->method( 'validateAndDeserialize' )->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute(
				$this->newUseCaseRequest(
					'Q123',
					[ [ 'op' => 'add', 'path' => '/bad-language-code', 'value' => [ 'alias' ] ] ]
				)
			);
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	private function newUseCase(): PatchItemAliases {
		return new PatchItemAliases(
			$this->validator,
			$this->assertItemExists,
			$this->assertUserIsAuthorized,
			$this->aliasesRetriever,
			$this->aliasesSerializer,
			$this->patchJson,
			$this->patchedAliasesValidator,
			$this->itemRetriever,
			$this->itemUpdater
		);
	}

	private function newUseCaseRequest( string $itemId, array $patch ): PatchItemAliasesRequest {
		return new PatchItemAliasesRequest( $itemId, $patch, [], false, null, null );
	}

	private function expectItemWithAliases( string $languageCode, array $aliasesInLanguage ): Callback {
		return $this->callback(
			fn( DataModelItem $item ) => $item->getAliasGroups()->getByLanguage( $languageCode )->getAliases() === $aliasesInLanguage
		);
	}

}
