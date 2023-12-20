<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\PatchPropertyDescriptions;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Application\Serialization\DescriptionsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\DescriptionsSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\AssertPropertyExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\PatchJson;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyDescriptions\PatchedPropertyDescriptionsValidator;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyDescriptions\PatchPropertyDescriptions;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyDescriptions\PatchPropertyDescriptionsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyDescriptions\PatchPropertyDescriptionsValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Domain\Model\DescriptionsEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\User;
use Wikibase\Repo\RestApi\Domain\ReadModel\Description;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\Domain\Services\PropertyDescriptionsRetriever;
use Wikibase\Repo\RestApi\Domain\Services\PropertyRetriever;
use Wikibase\Repo\RestApi\Domain\Services\PropertyUpdater;
use Wikibase\Repo\RestApi\Infrastructure\JsonDiffJsonPatcher;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\InMemoryPropertyRepository;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyDescriptions\PatchPropertyDescriptions
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchPropertyDescriptionsTest extends TestCase {

	private PatchPropertyDescriptionsValidator $validator;
	private AssertPropertyExists $assertPropertyExists;
	private AssertUserIsAuthorized $assertUserIsAuthorized;
	private DescriptionsSerializer $descriptionsSerializer;
	private PropertyDescriptionsRetriever $descriptionsRetriever;
	private PatchJson $patcher;
	private PropertyRetriever $propertyRetriever;
	private PatchedPropertyDescriptionsValidator $patchedDescriptionsValidator;
	private PropertyUpdater $propertyUpdater;

	protected function setUp(): void {
		parent::setUp();

		$this->validator = new TestValidatingRequestDeserializer();
		$this->assertPropertyExists = $this->createStub( AssertPropertyExists::class );
		$this->assertUserIsAuthorized = $this->createStub( AssertUserIsAuthorized::class );
		$this->descriptionsRetriever = $this->createStub( PropertyDescriptionsRetriever::class );
		$this->descriptionsRetriever->method( 'getDescriptions' )->willReturn( new Descriptions() );
		$this->descriptionsSerializer = new DescriptionsSerializer();
		$this->patcher = new PatchJson( new JsonDiffJsonPatcher() );
		$this->propertyRetriever = $this->createStub( PropertyRetriever::class );
		$this->propertyRetriever->method( 'getProperty' )
			->willReturn( new Property( null, null, 'string' ) );
		$this->patchedDescriptionsValidator = $this->createStub( PatchedPropertyDescriptionsValidator::class );
		$this->patchedDescriptionsValidator->method( 'validateAndDeserialize' )
			->willReturnCallback(
				fn( PropertyId $id, TermList $descriptions, array $patchedDescriptions ) => ( new DescriptionsDeserializer() )
					->deserialize( $patchedDescriptions )
			);
		$this->propertyUpdater = $this->createStub( PropertyUpdater::class );
	}

	public function testHappyPath(): void {
		$propertyId = new NumericPropertyId( 'P31' );

		$newDescriptionText = 'وصف عربي جديد';
		$newDescriptionLanguage = 'ar';

		$editTags = TestValidatingRequestDeserializer::ALLOWED_TAGS;
		$isBot = false;
		$comment = 'descriptions patched by ' . __method__;

		$this->descriptionsRetriever = $this->createStub( PropertyDescriptionsRetriever::class );
		$this->descriptionsRetriever->method( 'getDescriptions' )->willReturn( new Descriptions() );

		$propertyRepo = new InMemoryPropertyRepository();
		$propertyRepo->addProperty( new Property( $propertyId, null, 'string' ) );
		$this->propertyRetriever = $propertyRepo;
		$this->propertyUpdater = $propertyRepo;

		$response = $this->newUseCase()->execute(
			new PatchPropertyDescriptionsRequest(
				"$propertyId",
				[ [ 'op' => 'add', 'path' => "/$newDescriptionLanguage", 'value' => $newDescriptionText ] ],
				$editTags,
				$isBot,
				$comment,
				null
			)
		);

		$this->assertSame( $propertyRepo->getLatestRevisionId( $propertyId ), $response->getRevisionId() );
		$this->assertSame( $propertyRepo->getLatestRevisionTimestamp( $propertyId ), $response->getLastModified() );
		$this->assertEquals(
			$response->getDescriptions(),
			new Descriptions( new Description( $newDescriptionLanguage, $newDescriptionText ) )
		);
		$this->assertEquals(
			new EditMetadata(
				$editTags,
				$isBot,
				DescriptionsEditSummary::newPatchSummary(
					$comment,
					new TermList(),
					new TermList( [ new Term( $newDescriptionLanguage, $newDescriptionText ) ] )
				)
			),
			$propertyRepo->getLatestRevisionEditMetadata( $propertyId )
		);
	}

	public function testInvalidRequest_throwsException(): void {
		$expectedException = new UseCaseException( 'invalid-description-patch-test' );
		$this->validator = $this->createStub( PatchPropertyDescriptionsValidator::class );
		$this->validator->method( 'validateAndDeserialize' )->willThrowException( $expectedException );
		try {
			$this->newUseCase()->execute( $this->createStub( PatchPropertyDescriptionsRequest::class ) );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenPropertyNotFound_throws(): void {
		$request = new PatchPropertyDescriptionsRequest( 'P999999', [], [], false, null, null );
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

	public function testGivenUnauthorizedRequest_throws(): void {
		$username = 'bad-user';
		$propertyId = new NumericPropertyId( 'P31' );
		$request = new PatchPropertyDescriptionsRequest( "$propertyId", [], [], false, null, $username );
		$expectedException = $this->createStub( UseCaseError::class );

		$this->assertUserIsAuthorized = $this->createMock( AssertUserIsAuthorized::class );
		$this->assertUserIsAuthorized->expects( $this->once() )
			->method( 'execute' )
			->with( $propertyId, User::withUsername( $username ) )
			->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute( $request );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenPatchJsonError_throwsUseCaseError(): void {
		$expectedError = $this->createStub( UseCaseError::class );

		$this->descriptionsRetriever = $this->createStub( PropertyDescriptionsRetriever::class );
		$this->descriptionsRetriever->method( 'getDescriptions' )
			->willReturn( new Descriptions( new Description( 'en', 'English Description' ) ) );

		$this->patcher = $this->createMock( PatchJson::class );
		$this->patcher->expects( $this->once() )
			->method( 'execute' )
			->with( [ 'en' => 'English Description' ], [] )
			->willThrowException( $expectedError );

		try {
			$this->newUseCase()->execute( $this->newUseCaseRequest( 'P123', [] ) );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedError, $e );
		}
	}

	public function testGivenPatchedDescriptionsInvalid_throws(): void {
		$propertyId = new NumericPropertyId( 'P123' );
		$request = new PatchPropertyDescriptionsRequest(
			"$propertyId",
			[
				[ 'op' => 'add', 'path' => '/bad-language-code', 'value' => 'description text' ],
			],
			[],
			false,
			null,
			null
		);

		$expectedException = $this->createStub( UseCaseError::class );
		$this->patchedDescriptionsValidator = $this->createStub( PatchedPropertyDescriptionsValidator::class );
		$this->patchedDescriptionsValidator->method( 'validateAndDeserialize' )->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute( $request );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	private function newUseCase(): PatchPropertyDescriptions {
		return new PatchPropertyDescriptions(
			$this->validator,
			$this->assertPropertyExists,
			$this->assertUserIsAuthorized,
			$this->descriptionsRetriever,
			$this->descriptionsSerializer,
			$this->patcher,
			$this->propertyRetriever,
			$this->patchedDescriptionsValidator,
			$this->propertyUpdater
		);
	}

	private function newUseCaseRequest( string $itemId, array $patch ): PatchPropertyDescriptionsRequest {
		return new PatchPropertyDescriptionsRequest( $itemId, $patch, [], false, null, null );
	}

}
