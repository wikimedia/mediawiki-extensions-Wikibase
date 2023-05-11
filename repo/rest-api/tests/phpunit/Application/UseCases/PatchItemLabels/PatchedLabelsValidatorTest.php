<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\PatchItemLabels;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemLabels\PatchedLabelsValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\ItemLabelTextValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\PatchItemLabels\PatchedLabelsValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchedLabelsValidatorTest extends TestCase {

	/**
	 * @dataProvider validLabelsProvider
	 */
	public function testWithValidLabels( array $labelsSerialization, TermList $expectedResult ): void {
		$this->assertEquals(
			$expectedResult,
			( new PatchedLabelsValidator(
				new LabelsDeserializer(),
				$this->createStub( ItemLabelTextValidator::class )
			)
			)->validateAndDeserialize( $labelsSerialization )
		);
	}

	public function validLabelsProvider(): Generator {
		yield 'no labels' => [
			[],
			new TermList(),
		];
		yield 'valid labels' => [
			[
				'en' => 'potato',
				'de' => 'Kartoffel',
			],
			new TermList( [ new Term( 'en', 'potato' ), new Term( 'de', 'Kartoffel' ) ] ),
		];
	}

	/**
	 * @dataProvider invalidLabelsProvider
	 */
	public function testWithInvalidLabels(
		array $labelsSerialization,
		ValidationError $validationError,
		string $expectedErrorCode,
		string $expectedErrorMessage,
		array $expectedContext = null
	): void {
		$labelTextValidator = $this->createStub( ItemLabelTextValidator::class );
		$labelTextValidator->method( 'validate' )->willReturn( $validationError );

		try {
			( new PatchedLabelsValidator( new LabelsDeserializer(), $labelTextValidator ) )
				->validateAndDeserialize( $labelsSerialization );

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $error ) {
			$this->assertSame( $expectedErrorCode, $error->getErrorCode() );
			$this->assertSame( $expectedErrorMessage, $error->getErrorMessage() );
			$this->assertSame( $expectedContext, $error->getErrorContext() );
		}
	}

	public function invalidLabelsProvider(): Generator {
		$language = 'en';
		$label = "tab characters \t not allowed";
		yield 'invalid label' => [
			[ $language => $label ],
			new ValidationError(
				ItemLabelTextValidator::CODE_INVALID,
				[ ItemLabelTextValidator::CONTEXT_VALUE => $label ],
			),
			UseCaseError::PATCHED_LABEL_INVALID,
			"Changed label for '$language' is invalid: {$label}",
			[
				PatchedLabelsValidator::CONTEXT_LANGUAGE => $language,
				PatchedLabelsValidator::CONTEXT_VALUE => $label,
			],
		];

		$tooLongLabel = 'This label is too long.';
		$context = [
			ItemLabelTextValidator::CONTEXT_VALUE => $tooLongLabel,
			ItemLabelTextValidator::CONTEXT_LIMIT => 250,
			PatchedLabelsValidator::CONTEXT_LANGUAGE => $language,
		];
		yield 'label too long' => [
			[ $language => $tooLongLabel ],
			new ValidationError(
				ItemLabelTextValidator::CODE_TOO_LONG,
				$context
			),
			UseCaseError::PATCHED_LABEL_TOO_LONG,
			"Changed label for '$language' must not be more than 250 characters long",
			$context,
		];
	}

	public function testGivenEmptyLabel_throwsEmptyLabelError(): void {
		try {
			( new PatchedLabelsValidator(
				new LabelsDeserializer(),
				$this->createStub( ItemLabelTextValidator::class )
			) )->validateAndDeserialize( [ 'en' => '' ] );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::PATCHED_LABEL_EMPTY, $e->getErrorCode() );
			$this->assertSame( "Changed label for 'en' cannot be empty", $e->getErrorMessage() );
			$this->assertEquals( [ 'language' => 'en' ], $e->getErrorContext() );
		}
	}

}
