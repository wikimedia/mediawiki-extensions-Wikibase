<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyDescriptionEditRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyDescriptionEditRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\PropertyDescriptionValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;
use Wikibase\Repo\RestApi\Domain\Services\PropertyWriteModelRetriever;
use Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess\InMemoryPropertyRepository;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyDescriptionEditRequestValidatingDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PropertyDescriptionEditRequestValidatingDeserializerTest extends TestCase {

	private PropertyWriteModelRetriever $propertyRetriever;
	private PropertyDescriptionValidator $propertyDescriptionValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->propertyRetriever = new InMemoryPropertyRepository();
		$this->propertyRetriever->addProperty( new Property( new NumericPropertyId( 'P123' ), null, 'string' ) );
		$this->propertyDescriptionValidator = $this->createStub( PropertyDescriptionValidator::class );
	}

	public function testGivenValidRequest_returnsDescription(): void {
		$request = $this->createStub( PropertyDescriptionEditRequest::class );
		$request->method( 'getPropertyId' )->willReturn( 'P123' );
		$request->method( 'getLanguageCode' )->willReturn( 'en' );
		$request->method( 'getDescription' )->willReturn( 'that class of which this subject is a particular example and member' );

		$this->assertEquals(
			new Term( 'en', 'that class of which this subject is a particular example and member' ),
			$this->newValidatingDeserializer()->validateAndDeserialize( $request )
		);
	}

	public function testGivenPropertyDoesNotExist_skipsValidation(): void {
		$this->propertyRetriever = new InMemoryPropertyRepository();

		$this->propertyDescriptionValidator = $this->createMock( PropertyDescriptionValidator::class );
		$this->propertyDescriptionValidator->expects( $this->never() )->method( 'validate' );

		$request = $this->createStub( PropertyDescriptionEditRequest::class );
		$request->method( 'getPropertyId' )->willReturn( 'P123' );
		$request->method( 'getLanguageCode' )->willReturn( 'en' );
		$request->method( 'getDescription' )->willReturn( 'description' );

		$this->assertEquals(
			new Term( 'en', 'description' ),
			$this->newValidatingDeserializer()->validateAndDeserialize( $request )
		);
	}

	public function testGivenDescriptionIsUnchanged_skipsValidation(): void {
		$propertyId = new NumericPropertyId( 'P345' );
		$languageCode = 'en';
		$description = 'description';

		$this->propertyRetriever = new InMemoryPropertyRepository();
		$this->propertyRetriever->addProperty( new Property(
			$propertyId,
			new Fingerprint( null, new TermList( [ new Term( $languageCode, $description ) ] ) ),
			'string'
		) );
		$this->propertyDescriptionValidator = $this->createMock( PropertyDescriptionValidator::class );
		$this->propertyDescriptionValidator->expects( $this->never() )->method( 'validate' );

		$request = $this->createStub( PropertyDescriptionEditRequest::class );
		$request->method( 'getPropertyId' )->willReturn( "$propertyId" );
		$request->method( 'getLanguageCode' )->willReturn( $languageCode );
		$request->method( 'getDescription' )->willReturn( $description );

		$this->assertEquals(
			new Term( $languageCode, $description ),
			$this->newValidatingDeserializer()->validateAndDeserialize( $request )
		);
	}

	/**
	 * @dataProvider invalidDescriptionProvider
	 */
	public function testWithInvalidDescription(
		ValidationError $validationError,
		string $expectedErrorCode,
		string $expectedErrorMessage,
		array $expectedContext = []
	): void {
		$request = $this->createStub( PropertyDescriptionEditRequest::class );
		$request->method( 'getPropertyId' )->willReturn( 'P123' );
		$request->method( 'getLanguageCode' )->willReturn( 'en' );
		$request->method( 'getDescription' )->willReturn( 'my description' );

		$this->propertyDescriptionValidator = $this->createStub( PropertyDescriptionValidator::class );
		$this->propertyDescriptionValidator->method( 'validate' )->willReturn( $validationError );

		try {
			$this->newValidatingDeserializer()->validateAndDeserialize( $request );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $error ) {
			$this->assertSame( $expectedErrorCode, $error->getErrorCode() );
			$this->assertSame( $expectedErrorMessage, $error->getErrorMessage() );
			$this->assertSame( $expectedContext, $error->getErrorContext() );
		}
	}

	public static function invalidDescriptionProvider(): Generator {
		yield 'description empty' => [
			new ValidationError( PropertyDescriptionValidator::CODE_EMPTY ),
			UseCaseError::INVALID_VALUE,
			"Invalid value at '/description'",
			[ UseCaseError::CONTEXT_PATH => '/description' ],
		];

		$limit = 40;
		yield 'description too long' => [
			new ValidationError(
				PropertyDescriptionValidator::CODE_TOO_LONG,
				[
					PropertyDescriptionValidator::CONTEXT_DESCRIPTION => 'description that is too long...',
					PropertyDescriptionValidator::CONTEXT_LIMIT => $limit,
				]
			),
			UseCaseError::VALUE_TOO_LONG,
			'The input value is too long',
			[
				UseCaseError::CONTEXT_PATH => '/description',
				UseCaseError::CONTEXT_LIMIT => $limit,
			],
		];

		yield 'invalid description' => [
			new ValidationError(
				PropertyDescriptionValidator::CODE_INVALID,
				[ PropertyDescriptionValidator::CONTEXT_DESCRIPTION => "tab characters \t not allowed" ],
			),
			UseCaseError::INVALID_VALUE,
			"Invalid value at '/description'",
			[ UseCaseError::CONTEXT_PATH => '/description' ],
		];

		$language = 'en';
		yield 'label and description are equal' => [
			new ValidationError(
				PropertyDescriptionValidator::CODE_LABEL_DESCRIPTION_EQUAL,
				[ PropertyDescriptionValidator::CONTEXT_LANGUAGE => $language ],
			),
			UseCaseError::DATA_POLICY_VIOLATION,
			'Edit violates data policy',
			[
				UseCaseError::CONTEXT_VIOLATION => UseCaseError::POLICY_VIOLATION_LABEL_DESCRIPTION_SAME_VALUE,
				UseCaseError::CONTEXT_VIOLATION_CONTEXT => [ UseCaseError::CONTEXT_LANGUAGE => $language ],
			],
		];
	}

	private function newValidatingDeserializer(): PropertyDescriptionEditRequestValidatingDeserializer {
		return new PropertyDescriptionEditRequestValidatingDeserializer(
			$this->propertyDescriptionValidator,
			$this->propertyRetriever
		);
	}

}
