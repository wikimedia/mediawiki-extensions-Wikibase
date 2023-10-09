<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Term\Term;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\ItemDescriptionEditRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\ItemDescriptionEditRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\ItemDescriptionValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\ItemDescriptionEditRequestValidatingDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ItemDescriptionEditRequestValidatingDeserializerTest extends TestCase {

	public function testGivenValidRequest_returnsDescription(): void {
		$request = $this->createStub( ItemDescriptionEditRequest::class );
		$request->method( 'getItemId' )->willReturn( 'Q123' );
		$request->method( 'getLanguageCode' )->willReturn( 'en' );
		$request->method( 'getDescription' )->willReturn( 'root vegetable' );

		$this->assertEquals(
			new Term( 'en', 'root vegetable' ),
			( new ItemDescriptionEditRequestValidatingDeserializer( $this->createStub( ItemDescriptionValidator::class ) ) )
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
		$request = $this->createStub( ItemDescriptionEditRequest::class );
		$request->method( 'getItemId' )->willReturn( 'Q123' );
		$request->method( 'getLanguageCode' )->willReturn( 'en' );
		$request->method( 'getDescription' )->willReturn( 'my description' );

		$itemDescriptionValidator = $this->createStub( ItemDescriptionValidator::class );
		$itemDescriptionValidator->method( 'validate' )->willReturn( $validationError );

		try {
			( new ItemDescriptionEditRequestValidatingDeserializer( $itemDescriptionValidator ) )
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
			new ValidationError( ItemDescriptionValidator::CODE_EMPTY ),
			UseCaseError::DESCRIPTION_EMPTY,
			'Description must not be empty',
		];

		$description = 'description that is too long...';
		$limit = 40;
		yield 'description too long' => [
			new ValidationError(
				ItemDescriptionValidator::CODE_TOO_LONG,
				[
					ItemDescriptionValidator::CONTEXT_DESCRIPTION => $description,
					ItemDescriptionValidator::CONTEXT_LIMIT => $limit,
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
				ItemDescriptionValidator::CODE_INVALID,
				[ ItemDescriptionValidator::CONTEXT_DESCRIPTION => $description ],
			),
			UseCaseError::INVALID_DESCRIPTION,
			"Not a valid description: $description",
		];

		$language = 'en';
		yield 'label and description are equal' => [
			new ValidationError(
				ItemDescriptionValidator::CODE_LABEL_DESCRIPTION_EQUAL,
				[ ItemDescriptionValidator::CONTEXT_LANGUAGE => $language ],
			),
			UseCaseError::LABEL_DESCRIPTION_SAME_VALUE,
			"Label and description for language code '$language' can not have the same value",
			[ UseCaseError::CONTEXT_LANGUAGE => $language ],
		];

		$language = 'en';
		$label = 'test label';
		$description = 'test description';
		$matchingItemId = 'Q213';
		yield 'label and description duplicate' => [
			new ValidationError(
				ItemDescriptionValidator::CODE_LABEL_DESCRIPTION_DUPLICATE,
				[
					ItemDescriptionValidator::CONTEXT_LANGUAGE => $language,
					ItemDescriptionValidator::CONTEXT_LABEL => $label,
					ItemDescriptionValidator::CONTEXT_DESCRIPTION => $description,
					ItemDescriptionValidator::CONTEXT_MATCHING_ITEM_ID => $matchingItemId,

				],
			),
			UseCaseError::ITEM_LABEL_DESCRIPTION_DUPLICATE,
			"Item '$matchingItemId' already has label '$label' associated with "
			. "language code '$language', using the same description text",
			[
				UseCaseError::CONTEXT_LANGUAGE => $language,
				UseCaseError::CONTEXT_LABEL => $label,
				UseCaseError::CONTEXT_DESCRIPTION => $description,
				UseCaseError::CONTEXT_MATCHING_ITEM_ID => $matchingItemId,
			],
		];
	}

}
