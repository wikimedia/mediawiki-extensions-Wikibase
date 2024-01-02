<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\PatchItemAliases;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
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
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\User;
use Wikibase\Repo\RestApi\Domain\ReadModel\Aliases;
use Wikibase\Repo\RestApi\Domain\ReadModel\AliasesInLanguage;
use Wikibase\Repo\RestApi\Domain\Services\ItemAliasesRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Infrastructure\JsonDiffJsonPatcher;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\InMemoryItemRepository;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\PatchItemAliases\PatchItemAliases
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchItemAliasesTest extends TestCase {

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
		$aliasText = 'English alias';
		$newAliasText = 'another English alias';
		$aliasLanguage = 'en';

		$itemId = new ItemId( 'Q42' );

		$editTags = TestValidatingRequestDeserializer::ALLOWED_TAGS;
		$isBot = false;
		$comment = 'aliases patched by ' . __method__;

		$itemRepo = new InMemoryItemRepository();
		$itemRepo->addItem( NewItem::withId( $itemId )->andAliases( $aliasLanguage, [ $aliasText ] )->build() );
		$this->aliasesRetriever = $itemRepo;
		$this->itemRetriever = $itemRepo;
		$this->itemUpdater = $itemRepo;

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

		$this->assertEquals(
			$response->getAliases(),
			new Aliases( new AliasesInLanguage( $aliasLanguage, [ $aliasText, $newAliasText ] ) )
		);
		$this->assertSame( $itemRepo->getLatestRevisionId( $itemId ), $response->getRevisionId() );
		$this->assertSame( $itemRepo->getLatestRevisionTimestamp( $itemId ), $response->getLastModified() );
		$this->assertEquals(
			new EditMetadata(
				$editTags,
				$isBot,
				AliasesEditSummary::newPatchSummary(
					$comment,
					new AliasGroupList( [ new AliasGroup( $aliasLanguage, [ $aliasText ] ) ] ),
					new AliasGroupList( [ new AliasGroup( $aliasLanguage, [ $aliasText, $newAliasText ] ) ] )
				)
			),
			$itemRepo->getLatestRevisionEditMetadata( $itemId )
		);
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
			->with( $itemId, User::withUsername( $user ) )
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

}
