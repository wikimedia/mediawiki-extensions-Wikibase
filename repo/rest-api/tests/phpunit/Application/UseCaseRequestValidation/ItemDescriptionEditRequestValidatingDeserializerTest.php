<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Term\Term;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\ItemDescriptionEditRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\ItemDescriptionEditRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\OldItemDescriptionValidator;
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
			( new ItemDescriptionEditRequestValidatingDeserializer( $this->createStub( OldItemDescriptionValidator::class ) ) )
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

		$itemDescriptionValidator = $this->createStub( OldItemDescriptionValidator::class );
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
			new ValidationError( OldItemDescriptionValidator::CODE_EMPTY ),
			UseCaseError::DESCRIPTION_EMPTY,
			'Description must not be empty',
		];

		$description = 'description that is too long...';
		$limit = 40;
		yield 'description too long' => [
			new ValidationError(
				OldItemDescriptionValidator::CODE_TOO_LONG,
				[
					OldItemDescriptionValidator::CONTEXT_DESCRIPTION => $description,
					OldItemDescriptionValidator::CONTEXT_LIMIT => $limit,
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
				OldItemDescriptionValidator::CODE_INVALID,
				[ OldItemDescriptionValidator::CONTEXT_DESCRIPTION => $description ],
			),
			UseCaseError::INVALID_DESCRIPTION,
			"Not a valid description: $description",
		];

		$language = 'en';
		yield 'label and description are equal' => [
			new ValidationError(
				OldItemDescriptionValidator::CODE_LABEL_DESCRIPTION_EQUAL,
				[ OldItemDescriptionValidator::CONTEXT_LANGUAGE => $language ],
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
				OldItemDescriptionValidator::CODE_LABEL_DESCRIPTION_DUPLICATE,
				[
					OldItemDescriptionValidator::CONTEXT_LANGUAGE => $language,
					OldItemDescriptionValidator::CONTEXT_LABEL => $label,
					OldItemDescriptionValidator::CONTEXT_DESCRIPTION => $description,
					OldItemDescriptionValidator::CONTEXT_MATCHING_ITEM_ID => $matchingItemId,

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
