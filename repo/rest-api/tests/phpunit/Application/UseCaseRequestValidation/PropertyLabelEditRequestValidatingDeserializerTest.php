<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Term\Term;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyLabelEditRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyLabelEditRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\PropertyLabelValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyLabelEditRequestValidatingDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PropertyLabelEditRequestValidatingDeserializerTest extends TestCase {

	public function testGivenValidRequest_returnsLabel(): void {
		$request = $this->createStub( PropertyLabelEditRequest::class );
		$request->method( 'getPropertyId' )->willReturn( 'P1' );
		$request->method( 'getLanguageCode' )->willReturn( 'en' );
		$request->method( 'getLabel' )->willReturn( 'some-property-label' );

		$this->assertEquals(
			new Term( 'en', 'some-property-label' ),
			( new PropertyLabelEditRequestValidatingDeserializer( $this->createStub( PropertyLabelValidator::class ) ) )
				->validateAndDeserialize( $request )
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

		$propertyLabelValidator = $this->createStub( PropertyLabelValidator::class );
		$propertyLabelValidator->method( 'validate' )->willReturn( $validationError );

		try {
			( new PropertyLabelEditRequestValidatingDeserializer( $propertyLabelValidator ) )
				->validateAndDeserialize( $request );
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
			UseCaseError::INVALID_LABEL,
			"Not a valid label: $label",
		];

		yield 'label empty' => [
			new ValidationError( PropertyLabelValidator::CODE_EMPTY ),
			UseCaseError::LABEL_EMPTY,
			'Label must not be empty',
		];

		$label = 'This label is too long.';
		$limit = 250;
		yield 'label too long' => [
			new ValidationError( PropertyLabelValidator::CODE_TOO_LONG, [
				PropertyLabelValidator::CONTEXT_LABEL => $label,
				PropertyLabelValidator::CONTEXT_LIMIT => $limit,
			] ),
			UseCaseError::LABEL_TOO_LONG,
			"Label must be no more than $limit characters long",
			[
				UseCaseError::CONTEXT_VALUE => $label,
				UseCaseError::CONTEXT_CHARACTER_LIMIT => $limit,
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
		$label = 'My Label';
		$propertyId = 'P456';
		yield 'label not unique' => [
			new ValidationError( PropertyLabelValidator::CODE_LABEL_DUPLICATE, [
				PropertyLabelValidator::CONTEXT_LANGUAGE => $language,
				PropertyLabelValidator::CONTEXT_LABEL => $label,
				PropertyLabelValidator::CONTEXT_MATCHING_PROPERTY_ID => $propertyId,
			] ),
			UseCaseError::PROPERTY_LABEL_DUPLICATE,
			"Property $propertyId already has label '$label' associated with language code '$language'",
			[
				UseCaseError::CONTEXT_LANGUAGE => $language,
				UseCaseError::CONTEXT_LABEL => $label,
				UseCaseError::CONTEXT_MATCHING_PROPERTY_ID => $propertyId,
			],
		];
	}

}
