<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Validation;

use Generator;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Application\Validation\ItemDescriptionsContentsValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemDescriptionValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemLabelsContentsValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemLabelValidator;
use Wikibase\Repo\RestApi\Application\Validation\PartiallyValidatedDescriptions;
use Wikibase\Repo\RestApi\Application\Validation\PartiallyValidatedLabels;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;

/**
 * @covers \Wikibase\Repo\RestApi\Application\Validation\ItemLabelsContentsValidator
 * @covers \Wikibase\Repo\RestApi\Application\Validation\ItemDescriptionsContentsValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LabelsAndDescriptionsContentsValidatorTest extends TestCase {

	/**
	 * @dataProvider validatorProvider
	 *
	 * @param MockObject $singleTermValidator
	 * @param mixed $validator
	 * @param string $partialResultClass
	 * @param callable $getValidationResult
	 */
	public function testValid(
		MockObject $singleTermValidator,
		$validator,
		string $partialResultClass,
		callable $getValidationResult
	): void {
		$termsToValidate = new $partialResultClass( [
			new Term( 'de', 'de term' ),
			new Term( 'en', 'en term' ),
		] );
		$termsToCompareWith = new TermList( [ new Term( 'en', 'some other term' ) ] );

		$matcher = $this->exactly( count( $termsToValidate ) );
		$singleTermValidator->expects( $matcher )
			->method( 'validate' )
			->willReturnCallback(
				function ( $language, $text, $otherTerms ) use ( $matcher, $termsToValidate, $termsToCompareWith ) {
					$termBeingValidated = array_values( iterator_to_array( $termsToValidate ) )[$matcher->getInvocationCount() - 1];

					$this->assertSame( $termBeingValidated->getLanguageCode(), $language );
					$this->assertSame( $termBeingValidated->getText(), $text );
					$this->assertSame( $termsToCompareWith, $otherTerms );

					return null;
				}
			);

		$this->assertNull( $validator->validate( $termsToValidate, $termsToCompareWith ) );
		$this->assertEquals( $termsToValidate->asPlainTermList(), $getValidationResult() );
		$this->assertNotInstanceOf( $partialResultClass, $getValidationResult() );
	}

	/**
	 * @dataProvider validatorProvider
	 *
	 * @param MockObject $singleTermValidator
	 * @param mixed $validator
	 * @param string $partialResultClass
	 * @param callable $getValidationResult
	 */
	public function testValidateSpecificLanguages(
		MockObject $singleTermValidator,
		$validator,
		string $partialResultClass,
		callable $getValidationResult
	): void {
		$languagesToValidate = [ 'ar', 'en' ];
		$inputTerms = new $partialResultClass( [
			new Term( 'ar', 'ar term' ),
			new Term( 'de', 'de term' ),
			new Term( 'en', 'en term' ),
		] );
		$termsToCompareWith = new TermList( [ new Term( 'en', 'some other term' ) ] );

		$matcher = $this->exactly( count( $languagesToValidate ) );
		$singleTermValidator->expects( $matcher )
			->method( 'validate' )
			->willReturnCallback(
				function ( $language, $text, $otherTerms ) use ( $matcher, $languagesToValidate, $inputTerms, $termsToCompareWith ) {
					$languageBeingValidated = $languagesToValidate[$matcher->getInvocationCount() - 1];

					$this->assertSame( $languageBeingValidated, $language );
					$this->assertSame( $inputTerms->getByLanguage( $languageBeingValidated )->getText(), $text );
					$this->assertSame( $termsToCompareWith, $otherTerms );

					return null;
				}
			);

		$this->assertNull( $validator->validate( $inputTerms, $termsToCompareWith, $languagesToValidate ) );
		$this->assertEquals( $inputTerms->asPlainTermList(), $getValidationResult() );
		$this->assertNotInstanceOf( $partialResultClass, $getValidationResult() );
	}

	/**
	 * @dataProvider validatorProvider
	 *
	 * @param MockObject $singleTermValidator
	 * @param mixed $validator
	 * @param string $partialResultClass
	 * @throws Exception
	 */
	public function testInvalid(
		MockObject $singleTermValidator,
		$validator,
		string $partialResultClass
	): void {
		$termsToValidate = new $partialResultClass( [ new Term( 'de', 'de term' ) ] );
		$termsToCompareWith = new TermList();

		$expectedValidationError = $this->createStub( ValidationError::class );
		$singleTermValidator->method( 'validate' )->willReturn( $expectedValidationError );

		$this->assertSame( $expectedValidationError, $validator->validate( $termsToValidate, $termsToCompareWith ) );
	}

	public function validatorProvider(): Generator {
		$itemLabelValidator = $this->createMock( ItemLabelValidator::class );
		$itemLabelsContentsValidator = new ItemLabelsContentsValidator( $itemLabelValidator );
		yield ItemLabelsContentsValidator::class => [
			$itemLabelValidator,
			$itemLabelsContentsValidator,
			PartiallyValidatedLabels::class,
			fn() => $itemLabelsContentsValidator->getValidatedLabels(),
		];

		$itemDescriptionValidator = $this->createMock( ItemDescriptionValidator::class );
		$itemDescriptionsContentsValidator = new ItemDescriptionsContentsValidator( $itemDescriptionValidator );
		yield ItemDescriptionsContentsValidator::class => [
			$itemDescriptionValidator,
			$itemDescriptionsContentsValidator,
			PartiallyValidatedDescriptions::class,
			fn() => $itemDescriptionsContentsValidator->getValidatedDescriptions(),
		];
	}

}
