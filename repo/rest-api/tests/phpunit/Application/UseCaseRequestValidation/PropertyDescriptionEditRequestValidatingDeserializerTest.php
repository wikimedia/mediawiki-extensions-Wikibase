<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Term\Term;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyDescriptionEditRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyDescriptionEditRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\PropertyDescriptionValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyDescriptionEditRequestValidatingDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PropertyDescriptionEditRequestValidatingDeserializerTest extends TestCase {

	public function testGivenValidRequest_returnsDescription(): void {
		$request = $this->createStub( PropertyDescriptionEditRequest::class );
		$request->method( 'getPropertyId' )->willReturn( 'P123' );
		$request->method( 'getLanguageCode' )->willReturn( 'en' );
		$request->method( 'getDescription' )->willReturn( 'that class of which this subject is a particular example and member' );

		$this->assertEquals(
			new Term( 'en', 'that class of which this subject is a particular example and member' ),
			( new PropertyDescriptionEditRequestValidatingDeserializer( $this->createStub( PropertyDescriptionValidator::class ) ) )
				->validateAndDeserialize( $request )
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

		$propertyDescriptionValidator = $this->createStub( PropertyDescriptionValidator::class );
		$propertyDescriptionValidator->method( 'validate' )->willReturn( $validationError );

		try {
			( new PropertyDescriptionEditRequestValidatingDeserializer( $propertyDescriptionValidator ) )
				->validateAndDeserialize( $request );
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
			UseCaseError::DESCRIPTION_EMPTY,
			'Description must not be empty',
		];

		$description = 'description that is too long...';
		$limit = 40;
		yield 'description too long' => [
			new ValidationError(
				PropertyDescriptionValidator::CODE_TOO_LONG,
				[
					PropertyDescriptionValidator::CONTEXT_DESCRIPTION => $description,
					PropertyDescriptionValidator::CONTEXT_LIMIT => $limit,
				]
			),
			UseCaseError::DESCRIPTION_TOO_LONG,
			'Description must be no more than 40 characters long',
			[
				UseCaseError::CONTEXT_VALUE => $description,
				UseCaseError::CONTEXT_CHARACTER_LIMIT => $limit,
			],
		];

		$description = "tab characters \t not allowed";
		yield 'invalid description' => [
			new ValidationError(
				PropertyDescriptionValidator::CODE_INVALID,
				[ PropertyDescriptionValidator::CONTEXT_DESCRIPTION => $description ],
			),
			UseCaseError::INVALID_DESCRIPTION,
			"Not a valid description: $description",
		];

		$language = 'en';
		yield 'label and description are equal' => [
			new ValidationError(
				PropertyDescriptionValidator::CODE_LABEL_DESCRIPTION_EQUAL,
				[ PropertyDescriptionValidator::CONTEXT_LANGUAGE => $language ],
			),
			UseCaseError::LABEL_DESCRIPTION_SAME_VALUE,
			"Label and description for language code '$language' can not have the same value",
			[ UseCaseError::CONTEXT_LANGUAGE => $language ],
		];
	}

}
