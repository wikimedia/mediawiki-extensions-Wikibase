<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Application\UseCases\CreateProperty;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\Repo\Domains\Crud\Application\Serialization\AliasesDeserializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\AliasesInLanguageDeserializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\DescriptionsDeserializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\LabelsDeserializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\PropertyValuePairDeserializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\ReferenceDeserializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\StatementDeserializer;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AssertUserIsAuthorized;
use Wikibase\Repo\Domains\Crud\Application\UseCases\CreateProperty\CreateProperty;
use Wikibase\Repo\Domains\Crud\Application\UseCases\CreateProperty\CreatePropertyRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\CreateProperty\CreatePropertyValidator;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
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
use Wikibase\Repo\Domains\Crud\Domain\Model\CreatePropertyEditSummary;
use Wikibase\Repo\Domains\Crud\Domain\Model\EditMetadata;
use Wikibase\Repo\Domains\Crud\Domain\Services\PropertyCreator;
use Wikibase\Repo\Domains\Crud\Infrastructure\TermValidatorFactoryAliasesInLanguageValidator;
use Wikibase\Repo\Domains\Crud\Infrastructure\ValidatingRequestDeserializer;
use Wikibase\Repo\Domains\Crud\Infrastructure\ValueValidatorLanguageCodeValidator;
use Wikibase\Repo\Tests\Domains\Crud\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;
use Wikibase\Repo\Tests\Domains\Crud\Application\UseCaseRequestValidation\TestValidatingRequestDeserializerServiceContainer;
use Wikibase\Repo\Tests\Domains\Crud\Infrastructure\DataAccess\InMemoryPropertyRepository;
use Wikibase\Repo\Validators\MembershipValidator;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Application\UseCases\CreateProperty\CreateProperty
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class CreatePropertyTest extends TestCase {

	private PropertyCreator $propertyCreator;
	private AssertUserIsAuthorized $assertUserIsAuthorized;
	private array $dataTypesArray;

	protected function setUp(): void {
		parent::setUp();

		$this->propertyCreator = new InMemoryPropertyRepository();
		$this->assertUserIsAuthorized = $this->createStub( AssertUserIsAuthorized::class );
		$this->dataTypesArray = [ 'wikibase-item', 'wikibase-property', 'string' ];
	}

	public function testHappyPath(): void {
		$propertySerialization = [ 'data_type' => 'string' ];
		$editTags = [ TestValidatingRequestDeserializer::ALLOWED_TAGS[0] ];
		$isBot = false;
		$comment = 'comment';

		$propertyRepo = new InMemoryPropertyRepository();
		$this->propertyCreator = $propertyRepo;

		$response = $this->newUseCase()->execute(
			new CreatePropertyRequest(
				$propertySerialization,
				$editTags,
				$isBot,
				$comment,
				null
			)
		);

		$newProperty = $response->getProperty();
		$newPropertyId = $newProperty->getId();

		$this->assertEquals(
			new Property( $newPropertyId, new Fingerprint(), 'string', null ),
			$propertyRepo->getPropertyWriteModel( $newPropertyId )
		);
		$this->assertEquals( $propertyRepo->getProperty( $newPropertyId ), $newProperty );
		$this->assertSame( $propertyRepo->getLatestRevisionId( $newPropertyId ), $response->getRevisionId() );
		$this->assertSame( $propertyRepo->getLatestRevisionTimestamp( $newPropertyId ), $response->getLastModified() );
		$this->assertEquals(
			new EditMetadata( $editTags, $isBot, CreatePropertyEditSummary::newSummary( $comment ) ),
			$propertyRepo->getLatestRevisionEditMetadata( $newPropertyId )
		);
	}

	public function testGivenUserUnauthorized_throws(): void {
		$expectedException = $this->createStub( UseCaseError::class );
		$this->assertUserIsAuthorized = $this->createStub( AssertUserIsAuthorized::class );
		$this->assertUserIsAuthorized->method( 'checkCreatePropertyPermissions' )->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute( new CreatePropertyRequest( [ 'data_type' => 'string' ], [], false, null, null ) );
			$this->fail( 'expected exception not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	private function newUseCase(): CreateProperty {
		$propValPairDeserializer = $this->createStub( PropertyValuePairDeserializer::class );
		$propValPairDeserializer->method( 'deserialize' )->willReturnCallback(
			fn( array $p ) => new PropertySomeValueSnak( new NumericPropertyId( $p[ 'property' ][ 'id' ] ) )
		);

		return new CreateProperty(
			new CreatePropertyValidator(
				( new TestValidatingRequestDeserializerServiceContainer() )
					->get( ValidatingRequestDeserializer::EDIT_METADATA_REQUEST_VALIDATING_DESERIALIZER ),
				$this->dataTypesArray,
				new LabelsSyntaxValidator( new LabelsDeserializer(), $this->createStub( LabelLanguageCodeValidator::class ) ),
				new PropertyLabelsContentsValidator( $this->createStub( PropertyLabelValidator::class ) ),
				new DescriptionsSyntaxValidator(
					new DescriptionsDeserializer(),
					$this->createStub( DescriptionLanguageCodeValidator::class )
				),
				new PropertyDescriptionsContentsValidator( $this->createStub( PropertyDescriptionValidator::class ) ),
				new AliasesValidator(
					new TermValidatorFactoryAliasesInLanguageValidator( WikibaseRepo::getTermValidatorFactory() ),
					new ValueValidatorLanguageCodeValidator( new MembershipValidator( [ 'ar', 'de', 'en', 'en-gb' ] ) ),
					new AliasesDeserializer( new AliasesInLanguageDeserializer() )
				),
				new StatementsValidator( $this->newStatementValidator() )
			),
			$this->propertyCreator,
			$this->assertUserIsAuthorized
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
