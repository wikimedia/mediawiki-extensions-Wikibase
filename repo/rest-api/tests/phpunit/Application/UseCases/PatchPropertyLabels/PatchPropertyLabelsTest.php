<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\PatchPropertyLabels;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsSerializer;
use Wikibase\Repo\RestApi\Application\UseCases\AssertPropertyExists;
use Wikibase\Repo\RestApi\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\RestApi\Application\UseCases\PatchJson;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyLabels\PatchedLabelsValidator;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyLabels\PatchPropertyLabels;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyLabels\PatchPropertyLabelsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyLabels\PatchPropertyLabelsValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;
use Wikibase\Repo\RestApi\Application\Validation\PropertyLabelValidator;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\LabelsEditSummary;
use Wikibase\Repo\RestApi\Domain\Model\User;
use Wikibase\Repo\RestApi\Domain\ReadModel\Label;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Domain\Services\PropertyLabelsRetriever;
use Wikibase\Repo\RestApi\Domain\Services\PropertyRetriever;
use Wikibase\Repo\RestApi\Domain\Services\PropertyUpdater;
use Wikibase\Repo\RestApi\Infrastructure\JsonDiffJsonPatcher;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\InMemoryPropertyRepository;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyLabels\PatchPropertyLabels
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchPropertyLabelsTest extends TestCase {

	private PropertyLabelsRetriever $labelsRetriever;
	private LabelsSerializer $labelsSerializer;
	private PatchJson $patcher;
	private PropertyRetriever $propertyRetriever;
	private PropertyUpdater $propertyUpdater;
	private PatchPropertyLabelsValidator $validator;
	private PatchedLabelsValidator $patchedLabelsValidator;
	private AssertPropertyExists $assertPropertyExists;
	private AssertUserIsAuthorized $assertUserIsAuthorized;

	protected function setUp(): void {
		parent::setUp();

		$this->labelsRetriever = $this->createStub( PropertyLabelsRetriever::class );
		$this->labelsRetriever->method( 'getLabels' )->willReturn( new Labels() );
		$this->labelsSerializer = new LabelsSerializer();
		$this->patcher = new PatchJson( new JsonDiffJsonPatcher() );
		$this->propertyRetriever = $this->createStub( PropertyRetriever::class );
		$this->propertyUpdater = $this->createStub( PropertyUpdater::class );
		$this->validator = new TestValidatingRequestDeserializer();
		$this->patchedLabelsValidator = new PatchedLabelsValidator(
			new LabelsDeserializer(),
			$this->createStub( PropertyLabelValidator::class ),
			$this->createStub( LanguageCodeValidator::class )
		);
		$this->assertPropertyExists = $this->createStub( AssertPropertyExists::class );
		$this->assertUserIsAuthorized = $this->createStub( AssertUserIsAuthorized::class );
	}

	public function testHappyPath(): void {
		$propertyId = new NumericPropertyId( 'P31' );

		$newLabelText = 'nature de l’élément';
		$newLabelLanguage = 'fr';

		$editTags = TestValidatingRequestDeserializer::ALLOWED_TAGS;
		$isBot = false;
		$comment = 'labels replaced by ' . __method__;

		$propertyRepo = new InMemoryPropertyRepository();
		$propertyRepo->addProperty( new Property( $propertyId, null, 'string' ) );
		$this->labelsRetriever = $propertyRepo;
		$this->propertyRetriever = $propertyRepo;
		$this->propertyUpdater = $propertyRepo;

		$response = $this->newUseCase()->execute(
			new PatchPropertyLabelsRequest(
				"$propertyId",
				[
					[
						'op' => 'add',
						'path' => "/$newLabelLanguage",
						'value' => $newLabelText,
					],
				],
				$editTags,
				$isBot,
				$comment,
				null
			)
		);

		$this->assertSame( $propertyRepo->getLatestRevisionId( $propertyId ), $response->getRevisionId() );
		$this->assertSame( $propertyRepo->getLatestRevisionTimestamp( $propertyId ), $response->getLastModified() );
		$this->assertEquals(
			$response->getLabels(),
			new Labels( new Label( $newLabelLanguage, $newLabelText ) )
		);
		$this->assertEquals(
			new EditMetadata(
				$editTags,
				$isBot,
				LabelsEditSummary::newPatchSummary(
					$comment,
					new TermList(),
					new TermList( [ new Term( $newLabelLanguage, $newLabelText ) ] )
				)
			),
			$propertyRepo->getLatestRevisionEditMetadata( $propertyId )
		);
	}

	public function testGivenInvalidRequest_throws(): void {
		$expectedException = new UseCaseException( 'invalid-label-patch-test' );
		$this->validator = $this->createStub( PatchPropertyLabelsValidator::class );
		$this->validator->method( 'validateAndDeserialize' )->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute( $this->createStub( PatchPropertyLabelsRequest::class ) );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenPropertyNotFound_throws(): void {
		$request = new PatchPropertyLabelsRequest( 'P999999', [], [], false, null, null );
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
		$user = 'bad-user';
		$propertyId = new NumericPropertyId( 'P123' );
		$request = new PatchPropertyLabelsRequest( "$propertyId", [], [], false, null, $user );
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

	public function testGivenErrorWhilePatch_throws(): void {
		$request = new PatchPropertyLabelsRequest( 'P123', [], [], false, null, null );
		$expectedException = $this->createStub( UseCaseError::class );

		$this->patcher = $this->createStub( PatchJson::class );
		$this->patcher->method( 'execute' )->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute( $request );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenPatchedLabelsInvalid_throwsUseCaseError(): void {
		$property = new Property( new NumericPropertyId( 'P31' ), null, 'string' );

		$patchResult = [ 'ar' => '' ];

		$propertyRepo = new InMemoryPropertyRepository();
		$propertyRepo->addProperty( $property );
		$this->labelsRetriever = $propertyRepo;
		$this->propertyRetriever = $propertyRepo;

		$expectedUseCaseError = $this->createStub( UseCaseError::class );
		$this->patchedLabelsValidator = $this->createMock( PatchedLabelsValidator::class );
		$this->patchedLabelsValidator->expects( $this->once() )
			->method( 'validateAndDeserialize' )
			->with( $property->getId(), new TermList(), $patchResult )
			->willThrowException( $expectedUseCaseError );

		try {
			$this->newUseCase()->execute(
				new PatchPropertyLabelsRequest(
					$property->getId()->getSerialization(),
					[ [ 'op' => 'add', 'path' => '/ar', 'value' => '' ] ],
					[],
					false,
					null,
					null
				)
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedUseCaseError, $e );
		}
	}

	private function newUseCase(): PatchPropertyLabels {
		return new PatchPropertyLabels(
			$this->labelsRetriever,
			$this->labelsSerializer,
			$this->patcher,
			$this->propertyRetriever,
			$this->propertyUpdater,
			$this->validator,
			$this->patchedLabelsValidator,
			$this->assertPropertyExists,
			$this->assertUserIsAuthorized
		);
	}

}
