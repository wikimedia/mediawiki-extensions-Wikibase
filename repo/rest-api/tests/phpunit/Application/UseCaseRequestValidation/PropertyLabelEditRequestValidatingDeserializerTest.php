<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyLabelEditRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyLabelEditRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\PropertyLabelValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;
use Wikibase\Repo\RestApi\Domain\Services\PropertyWriteModelRetriever;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\InMemoryPropertyRepository;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyLabelEditRequestValidatingDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PropertyLabelEditRequestValidatingDeserializerTest extends TestCase {

	private PropertyWriteModelRetriever $propertyRetriever;
	private PropertyLabelValidator $propertyLabelValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->propertyRetriever = new InMemoryPropertyRepository();
		$this->propertyRetriever->addProperty( new Property( new NumericPropertyId( 'P123' ), null, 'string' ) );
		$this->propertyLabelValidator = $this->createStub( PropertyLabelValidator::class );
	}

	public function testGivenValidRequest_returnsLabel(): void {
		$request = $this->createStub( PropertyLabelEditRequest::class );
		$request->method( 'getPropertyId' )->willReturn( 'P1' );
		$request->method( 'getLanguageCode' )->willReturn( 'en' );
		$request->method( 'getLabel' )->willReturn( 'some-property-label' );

		$this->assertEquals(
			new Term( 'en', 'some-property-label' ),
			( new PropertyLabelEditRequestValidatingDeserializer( $this->propertyLabelValidator, $this->propertyRetriever ) )
				->validateAndDeserialize( $request )
		);
	}

	public function testGivenPropertyDoesNotExist_skipsValidation(): void {
		$this->propertyRetriever = new InMemoryPropertyRepository();

		$this->propertyLabelValidator = $this->createMock( PropertyLabelValidator::class );
		$this->propertyLabelValidator->expects( $this->never() )->method( 'validate' );

		$request = $this->createStub( PropertyLabelEditRequest::class );
		$request->method( 'getPropertyId' )->willReturn( 'P123' );
		$request->method( 'getLanguageCode' )->willReturn( 'en' );
		$request->method( 'getLabel' )->willReturn( 'potato' );

		$this->assertEquals(
			new Term( 'en', 'potato' ),
			$this->newValidatingDeserializer()->validateAndDeserialize( $request )
		);
	}

	public function testGivenLabelIsUnchanged_skipsValidation(): void {
		$propertyId = new NumericPropertyId( 'P345' );
		$languageCode = 'en';
		$label = 'potato';

		$this->propertyRetriever = new InMemoryPropertyRepository();
		$this->propertyRetriever->addProperty( new Property(
			$propertyId,
			new Fingerprint( new TermList( [ new Term( $languageCode, $label ) ] ) ),
			'string'
		) );
		$this->propertyLabelValidator = $this->createMock( PropertyLabelValidator::class );
		$this->propertyLabelValidator->expects( $this->never() )->method( 'validate' );

		$request = $this->createStub( PropertyLabelEditRequest::class );
		$request->method( 'getPropertyId' )->willReturn( "$propertyId" );
		$request->method( 'getLanguageCode' )->willReturn( $languageCode );
		$request->method( 'getLabel' )->willReturn( $label );

		$this->assertEquals(
			new Term( $languageCode, $label ),
			$this->newValidatingDeserializer()->validateAndDeserialize( $request )
		);
	}

	/**
	 * @dataProvider invalidLabelProvider
	 */
	public function testWithInvalidLabel(
		ValidationError $validationError,
		string $expectedErrorCode,
		string $expectedErrorMessage,
		array $expectedContext = []
	): void {
		$request = $this->createStub( PropertyLabelEditRequest::class );
		$request->method( 'getPropertyId' )->willReturn( 'P123' );
		$request->method( 'getLanguageCode' )->willReturn( 'en' );
		$request->method( 'getLabel' )->willReturn( 'my label' );

		$this->propertyLabelValidator = $this->createStub( PropertyLabelValidator::class );
		$this->propertyLabelValidator->method( 'validate' )->willReturn( $validationError );

		try {
			$this->newValidatingDeserializer()->validateAndDeserialize( $request );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $error ) {
			$this->assertSame( $expectedErrorCode, $error->getErrorCode() );
			$this->assertSame( $expectedErrorMessage, $error->getErrorMessage() );
			$this->assertSame( $expectedContext, $error->getErrorContext() );
		}
	}

	public static function invalidLabelProvider(): Generator {
		$label = "tab characters \t not allowed";
		yield 'invalid label' => [
			new ValidationError(
				PropertyLabelValidator::CODE_INVALID,
				[ PropertyLabelValidator::CONTEXT_LABEL => $label ],
			),
			UseCaseError::INVALID_VALUE,
			"Invalid value at '/label'",
			[ UseCaseError::CONTEXT_PATH => '/label' ],
		];

		yield 'label empty' => [
			new ValidationError( PropertyLabelValidator::CODE_EMPTY ),
			UseCaseError::INVALID_VALUE,
			"Invalid value at '/label'",
			[ UseCaseError::CONTEXT_PATH => '/label' ],
		];

		$limit = 250;
		yield 'label too long' => [
			new ValidationError( PropertyLabelValidator::CODE_TOO_LONG, [
				PropertyLabelValidator::CONTEXT_LABEL => 'This label is too long.',
				PropertyLabelValidator::CONTEXT_LIMIT => $limit,
			] ),
			UseCaseError::VALUE_TOO_LONG,
			'The input value is too long',
			[
				UseCaseError::CONTEXT_PATH => '/label',
				UseCaseError::CONTEXT_LIMIT => $limit,
			],
		];

		$language = 'en';
		yield 'label equals description' => [
			new ValidationError(
				PropertyLabelValidator::CODE_LABEL_DESCRIPTION_EQUAL,
				[ PropertyLabelValidator::CONTEXT_LANGUAGE => $language ]
			),
			UseCaseError::LABEL_DESCRIPTION_SAME_VALUE,
			"Label and description for language code '$language' can not have the same value.",
			[ UseCaseError::CONTEXT_LANGUAGE => $language ],
		];

		$language = 'en';
		$propertyId = 'P456';
		yield 'label not unique' => [
			new ValidationError( PropertyLabelValidator::CODE_LABEL_DUPLICATE, [
				PropertyLabelValidator::CONTEXT_LANGUAGE => $language,
				PropertyLabelValidator::CONTEXT_LABEL => 'My Label',
				PropertyLabelValidator::CONTEXT_MATCHING_PROPERTY_ID => $propertyId,
			] ),
			UseCaseError::DATA_POLICY_VIOLATION,
			'Edit violates data policy',
			[
				UseCaseError::CONTEXT_VIOLATION => UseCaseError::POLICY_VIOLATION_PROPERTY_LABEL_DUPLICATE,
				UseCaseError::CONTEXT_VIOLATION_CONTEXT => [
					UseCaseError::CONTEXT_LANGUAGE => $language,
					UseCaseError::CONTEXT_CONFLICTING_PROPERTY_ID => $propertyId,
				],
			],
		];
	}

	private function newValidatingDeserializer(): PropertyLabelEditRequestValidatingDeserializer {
		return new PropertyLabelEditRequestValidatingDeserializer( $this->propertyLabelValidator, $this->propertyRetriever );
	}

}
