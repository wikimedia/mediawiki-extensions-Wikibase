<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\PatchPropertyAliases;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\AssertPropertyExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\PatchJson;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyAliases\PatchedAliasesValidator;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyAliases\PatchPropertyAliases;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyAliases\PatchPropertyAliasesRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyAliases\PatchPropertyAliasesValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Domain\Model\AliasesEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\User;
use Wikibase\Repo\RestApi\Domain\ReadModel\Aliases;
use Wikibase\Repo\RestApi\Domain\ReadModel\AliasesInLanguage;
use Wikibase\Repo\RestApi\Domain\Services\PropertyAliasesRetriever;
use Wikibase\Repo\RestApi\Domain\Services\PropertyRetriever;
use Wikibase\Repo\RestApi\Domain\Services\PropertyUpdater;
use Wikibase\Repo\RestApi\Infrastructure\JsonDiffJsonPatcher;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\InMemoryPropertyRepository;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyAliases\PatchPropertyAliases
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchPropertyAliasesTest extends TestCase {

	private PatchPropertyAliasesValidator $validator;
	private AssertPropertyExists $assertPropertyExists;
	private AssertUserIsAuthorized $assertUserIsAuthorized;
	private PropertyAliasesRetriever $aliasesRetriever;
	private AliasesSerializer $aliasesSerializer;
	private PatchJson $patchJson;
	private PatchedAliasesValidator $patchedAliasesValidator;
	private PropertyRetriever $propertyRetriever;
	private PropertyUpdater $propertyUpdater;

	protected function setUp(): void {
		parent::setUp();

		$this->validator = new TestValidatingRequestDeserializer();
		$this->assertPropertyExists = $this->createStub( AssertPropertyExists::class );
		$this->assertUserIsAuthorized = $this->createStub( AssertUserIsAuthorized::class );
		$this->aliasesRetriever = $this->createStub( PropertyAliasesRetriever::class );
		$this->aliasesRetriever->method( 'getAliases' )->willReturn( new Aliases() );
		$this->aliasesSerializer = new AliasesSerializer();
		$this->patchJson = new PatchJson( new JsonDiffJsonPatcher() );
		$this->patchedAliasesValidator = $this->createStub( PatchedAliasesValidator::class );
		$this->patchedAliasesValidator->method( 'validateAndDeserialize' )
			->willReturnCallback( [ new AliasesDeserializer(), 'deserialize' ] );
		$this->propertyRetriever = $this->createStub( PropertyRetriever::class );
		$this->propertyUpdater = $this->createStub( PropertyUpdater::class );
	}

	public function testHappyPath(): void {
		$propertyId = new NumericPropertyId( 'P123' );

		$originalAliases = new AliasGroupList( [
			new AliasGroup( 'en', [ 'spud', 'tater' ] ),
			new AliasGroup( 'de', [ 'Erdapfel' ] ),
		] );
		$patchedAliasesSerialization = [ 'en' => [ 'spud', 'tater', 'Solanum tuberosum' ] ];

		$editTags = TestValidatingRequestDeserializer::ALLOWED_TAGS;
		$isBot = false;
		$comment = 'statement replaced by ' . __method__;

		$propertyRepo = new InMemoryPropertyRepository();
		$propertyRepo->addProperty(
			new Property( $propertyId, new Fingerprint( null, null, $originalAliases ), 'string' )
		);
		$this->aliasesRetriever = $propertyRepo;
		$this->propertyRetriever = $propertyRepo;
		$this->propertyUpdater = $propertyRepo;

		$response = $this->newUseCase()->execute(
			new PatchPropertyAliasesRequest(
				"$propertyId",
				[
					[ 'op' => 'remove', 'path' => '/de' ],
					[ 'op' => 'add', 'path' => '/en/-', 'value' => 'Solanum tuberosum' ],
				],
				$editTags,
				$isBot,
				$comment,
				null
			)
		);

		$this->assertSame( $propertyRepo->getLatestRevisionId( $propertyId ), $response->getRevisionId() );
		$this->assertSame( $propertyRepo->getLatestRevisionTimestamp( $propertyId ), $response->getLastModified() );
		$this->assertEquals( new Aliases( new AliasesInLanguage( 'en', $patchedAliasesSerialization['en'] ) ), $response->getAliases() );
		$this->assertEquals(
			new EditMetadata(
				$editTags,
				$isBot,
				AliasesEditSummary::newPatchSummary(
					$comment,
					$originalAliases,
					new AliasGroupList( [ new AliasGroup( 'en', $patchedAliasesSerialization[ 'en' ] ) ] )
				)
			),
			$propertyRepo->getLatestRevisionEditMetadata( $propertyId )
		);
	}

	public function testGivenInvalidRequest_throws(): void {
		$expectedException = new UseCaseException( 'invalid-alias-patch-test' );
		$this->validator = $this->createStub( PatchPropertyAliasesValidator::class );
		$this->validator->method( 'validateAndDeserialize' )->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute( $this->createStub( PatchPropertyAliasesRequest::class ) );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenPatchedAliasesInvalid_throws(): void {
		$propertyId = new NumericPropertyId( 'P123' );
		$request = new PatchPropertyAliasesRequest(
			"$propertyId",
			[
				[ 'op' => 'add', 'path' => '/bad-language-code', 'value' => [ 'alias' ] ],
			],
			[],
			false,
			null,
			null
		);

		$expectedException = $this->createStub( UseCaseError::class );
		$this->patchedAliasesValidator = $this->createStub( PatchedAliasesValidator::class );
		$this->patchedAliasesValidator->method( 'validateAndDeserialize' )->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute( $request );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenPropertyNotFound_throws(): void {
		$request = new PatchPropertyAliasesRequest( 'P999999', [], [], false, null, null );
		$expectedException = $this->createStub( UseCaseError::class );

		$this->assertPropertyExists = $this->createStub( AssertPropertyExists::class );
		$this->assertPropertyExists->method( 'execute' )->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute( $request );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenErrorWhilePatch_throws(): void {
		$request = new PatchPropertyAliasesRequest( 'P123', [], [], false, null, null );
		$expectedException = $this->createStub( UseCaseError::class );

		$this->patchJson = $this->createStub( PatchJson::class );
		$this->patchJson->method( 'execute' )->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute( $request );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenUnauthorizedRequest_throws(): void {
		$user = 'bad-user';
		$propertyId = new NumericPropertyId( 'P123' );
		$request = new PatchPropertyAliasesRequest( "$propertyId", [], [], false, null, $user );
		$expectedException = $this->createStub( UseCaseError::class );

		$this->assertUserIsAuthorized = $this->createMock( AssertUserIsAuthorized::class );
		$this->assertUserIsAuthorized->expects( $this->once() )
			->method( 'execute' )
			->with( $propertyId, User::withUsername( $user ) )
			->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute( $request );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	private function newUseCase(): PatchPropertyAliases {
		return new PatchPropertyAliases(
			$this->validator,
			$this->assertPropertyExists,
			$this->assertUserIsAuthorized,
			$this->aliasesRetriever,
			$this->aliasesSerializer,
			$this->patchJson,
			$this->patchedAliasesValidator,
			$this->propertyRetriever,
			$this->propertyUpdater
		);
	}

}
