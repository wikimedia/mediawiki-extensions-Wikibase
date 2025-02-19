<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Application\UseCases\PatchProperty;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property as PropertyWriteModel;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\Domains\Crud\Application\Serialization\AliasesDeserializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\AliasesInLanguageDeserializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\AliasesSerializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\DescriptionsDeserializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\DescriptionsSerializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\LabelsDeserializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\LabelsSerializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\PropertySerializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\PropertyValuePairDeserializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\ReferenceDeserializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\StatementDeserializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\StatementListSerializer;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertPropertyExists;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchJson;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchProperty\PatchedPropertyValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchProperty\PatchProperty;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchProperty\PatchPropertyRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchProperty\PatchPropertyValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\Application\Validation\AliasesInLanguageValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\AliasesValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\DescriptionLanguageCodeValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\DescriptionsSyntaxValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\LabelLanguageCodeValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\LabelsSyntaxValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\PropertyDescriptionsContentsValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\PropertyDescriptionValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\PropertyLabelsContentsValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\PropertyLabelValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\StatementsValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\StatementValidator;
use Wikibase\Repo\Domains\Crud\Domain\Model\EditMetadata;
use Wikibase\Repo\Domains\Crud\Domain\Model\PatchPropertyEditSummary;
use Wikibase\Repo\Domains\Crud\Domain\Model\User;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Aliases;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Description;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Descriptions;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Label;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Labels;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Property;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\StatementList;
use Wikibase\Repo\Domains\Crud\Domain\Services\PropertyRetriever;
use Wikibase\Repo\Domains\Crud\Domain\Services\PropertyUpdater;
use Wikibase\Repo\Domains\Crud\Domain\Services\PropertyWriteModelRetriever;
use Wikibase\Repo\Domains\Crud\Infrastructure\JsonDiffJsonPatcher;
use Wikibase\Repo\Domains\Crud\Infrastructure\ValueValidatorLanguageCodeValidator;
use Wikibase\Repo\Tests\Domains\Crud\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\Domains\Crud\Infrastructure\DataAccess\InMemoryPropertyRepository;
use Wikibase\Repo\Validators\MembershipValidator;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Application\UseCases\PatchProperty\PatchProperty
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchPropertyTest extends TestCase {

	private PatchPropertyValidator $validator;
	private AssertPropertyExists $assertPropertyExists;
	private AssertUserIsAuthorized $assertUserIsAuthorized;
	private PatchJson $patchJson;
	private PropertyRetriever $propertyRetriever;
	private PropertyUpdater $propertyUpdater;
	private PropertyWriteModelRetriever $propertyWriteModelRetriever;
	private PatchedPropertyValidator $patchedPropertyValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->validator = new TestValidatingRequestDeserializer();
		$this->assertPropertyExists = $this->createStub( AssertPropertyExists::class );
		$this->assertUserIsAuthorized = $this->createStub( AssertUserIsAuthorized::class );
		$this->propertyRetriever = $this->createStub( PropertyRetriever::class );
		$this->patchJson = new PatchJson( new JsonDiffJsonPatcher() );
		$this->propertyUpdater = $this->createStub( PropertyUpdater::class );
		$this->propertyWriteModelRetriever = $this->createStub( PropertyWriteModelRetriever::class );
		$this->patchedPropertyValidator = new PatchedPropertyValidator(
			new LabelsSyntaxValidator( new LabelsDeserializer(), $this->createStub( LabelLanguageCodeValidator::class ) ),
			new PropertyLabelsContentsValidator( $this->createStub( PropertyLabelValidator::class ) ),
			new DescriptionsSyntaxValidator( new DescriptionsDeserializer(), $this->createStub( DescriptionLanguageCodeValidator::class ) ),
			new PropertyDescriptionsContentsValidator( $this->createStub( PropertyDescriptionValidator::class ) ),
			new AliasesValidator(
				$this->createStub( AliasesInLanguageValidator::class ),
				new ValueValidatorLanguageCodeValidator( new MembershipValidator( [ 'ar', 'de', 'en', 'fr' ] ) ),
				new AliasesDeserializer( new AliasesInLanguageDeserializer() ),
			),
			new StatementsValidator( $this->newStatementValidator() )
		);
	}

	public function testHappyPath(): void {
		$propertyId = new NumericPropertyId( 'P123' );
		$editTags = TestValidatingRequestDeserializer::ALLOWED_TAGS;
		$isBot = false;
		$comment = 'statement replaced by ' . __METHOD__;

		$propertyRepo = new InMemoryPropertyRepository();
		$originalProperty = new PropertyWriteModel(
			$propertyId,
			new Fingerprint( new TermList( [ new Term( 'en', 'potato' ), new Term( 'de', 'Kartoffel' ) ] ) ),
			'string',
			null
		);
		$propertyRepo->addProperty( $originalProperty->copy() );
		$this->propertyRetriever = $this->propertyUpdater = $this->propertyWriteModelRetriever = $propertyRepo;

		$response = $this->newUseCase()->execute(
			new PatchPropertyRequest(
				"$propertyId",
				[
					[ 'op' => 'add', 'path' => '/descriptions/en', 'value' => 'staple food' ],
					[ 'op' => 'replace', 'path' => '/labels/en', 'value' => 'Solanum tuberosum' ],
					[ 'op' => 'remove', 'path' => '/labels/de' ],
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
			new Property(
				$propertyId,
				'string',
				new Labels( new Label( 'en', 'Solanum tuberosum' ) ),
				new Descriptions( new Description( 'en', 'staple food' ) ),
				new Aliases(),
				new StatementList()
			),
			$response->getProperty()
		);
		$this->assertEquals(
			new EditMetadata(
				$editTags,
				$isBot,
				PatchPropertyEditSummary::newSummary(
					$comment,
					$originalProperty,
					$propertyRepo->getPropertyWriteModel( $propertyId )
				),
			),
			$propertyRepo->getLatestRevisionEditMetadata( $propertyId )
		);
	}

	public function testGivenInvalidRequest_throws(): void {
		try {
			$this->newUseCase()->execute( new PatchPropertyRequest( 'X321', [], [], false, null, null ) );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_PATH_PARAMETER, $e->getErrorCode() );
		}
	}

	public function testGivenPropertyNotFound_throws(): void {
		$request = new PatchPropertyRequest( 'P999999', [], [], false, null, null );
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
		$request = new PatchPropertyRequest( "$propertyId", [], [], false, null, $user );
		$expectedException = $this->createStub( UseCaseError::class );

		$this->assertUserIsAuthorized = $this->createMock( AssertUserIsAuthorized::class );
		$this->assertUserIsAuthorized->expects( $this->once() )
			->method( 'checkEditPermissions' )
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
		$propertyId = new NumericPropertyId( 'P123' );

		$propertyRepo = new InMemoryPropertyRepository();
		$propertyRepo->addProperty( new PropertyWriteModel( $propertyId, new Fingerprint(), 'string', null ) );

		$this->propertyRetriever = $propertyRepo;

		$expectedException = $this->createStub( UseCaseError::class );

		$this->patchJson = $this->createStub( PatchJson::class );
		$this->patchJson->method( 'execute' )->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute( new PatchPropertyRequest( "$propertyId", [], [], false, null, null ) );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testGivenPropertyInvalidAfterPatching_throws(): void {
		$propertyId = new NumericPropertyId( 'P123' );

		$propertyRepo = new InMemoryPropertyRepository();
		$propertyRepo->addProperty( new PropertyWriteModel( $propertyId, new Fingerprint(), 'string', null ) );

		$this->propertyRetriever = $propertyRepo;
		$this->propertyWriteModelRetriever = $propertyRepo;

		$expectedException = $this->createStub( UseCaseError::class );

		$this->patchedPropertyValidator = $this->createStub( PatchedPropertyValidator::class );
		$this->patchedPropertyValidator->method( 'validateAndDeserialize' )->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute( new PatchPropertyRequest( "$propertyId", [], [], false, null, null ) );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	private function newUseCase(): PatchProperty {
		return new PatchProperty(
			$this->validator,
			$this->assertPropertyExists,
			$this->assertUserIsAuthorized,
			$this->propertyRetriever,
			new PropertySerializer(
				new LabelsSerializer(),
				new DescriptionsSerializer(),
				new AliasesSerializer(),
				$this->createStub( StatementListSerializer::class )
			),
			$this->patchJson,
			$this->propertyUpdater,
			$this->propertyWriteModelRetriever,
			$this->patchedPropertyValidator
		);
	}

	private function newStatementValidator(): StatementValidator {
		$propValPairDeserializer = $this->createStub( PropertyValuePairDeserializer::class );
		$propValPairDeserializer->method( 'deserialize' )->willReturnCallback(
			fn( array $p ) => new PropertySomeValueSnak( new NumericPropertyId( $p[ 'property' ][ 'id' ] ) )
		);

		return new StatementValidator(
			new StatementDeserializer( $propValPairDeserializer, $this->createStub( ReferenceDeserializer::class ) )
		);
	}
}
