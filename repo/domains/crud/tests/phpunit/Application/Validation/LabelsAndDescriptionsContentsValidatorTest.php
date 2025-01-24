<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Application\Validation;

use Generator;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\Domains\Crud\Application\Validation\ItemDescriptionsContentsValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\ItemDescriptionValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\ItemLabelsContentsValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\ItemLabelValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\PartiallyValidatedDescriptions;
use Wikibase\Repo\Domains\Crud\Application\Validation\PartiallyValidatedLabels;
use Wikibase\Repo\Domains\Crud\Application\Validation\PropertyDescriptionsContentsValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\PropertyDescriptionValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\PropertyLabelsContentsValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\PropertyLabelValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\ValidationError;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Application\Validation\ItemDescriptionsContentsValidator
 * @covers \Wikibase\Repo\Domains\Crud\Application\Validation\ItemLabelsContentsValidator
 * @covers \Wikibase\Repo\Domains\Crud\Application\Validation\PropertyDescriptionsContentsValidator
 * @covers \Wikibase\Repo\Domains\Crud\Application\Validation\PropertyLabelsContentsValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LabelsAndDescriptionsContentsValidatorTest extends TestCase {

	/**
	 * @dataProvider validatorProvider
	 */
	public function testValid(
		callable $dataFactory
	): void {
		[ $singleTermValidator, $validator, $partialResultClass, $getValidationResult ] = $dataFactory( $this );

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
	 */
	public function testValidateSpecificLanguages(
		callable $dataFactory
	): void {
		[ $singleTermValidator, $validator, $partialResultClass, $getValidationResult ] = $dataFactory( $this );

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
	 * @throws Exception
	 */
	public function testInvalid(
		callable $dataFactory
	): void {
		[ $singleTermValidator, $validator, $partialResultClass ] = $dataFactory( $this );

		$termsToValidate = new $partialResultClass( [ new Term( 'de', 'de term' ) ] );
		$termsToCompareWith = new TermList();

		$expectedValidationError = $this->createStub( ValidationError::class );
		$singleTermValidator->method( 'validate' )->willReturn( $expectedValidationError );

		$this->assertSame( $expectedValidationError, $validator->validate( $termsToValidate, $termsToCompareWith ) );
	}

	public static function validatorProvider(): Generator {
		yield PropertyLabelsContentsValidator::class => [
			function ( self $self ) {
				$propertyLabelValidator = $self->createMock( PropertyLabelValidator::class );
				$propertyLabelsContentsValidator = new PropertyLabelsContentsValidator( $propertyLabelValidator );
				return [
					$propertyLabelValidator,
					$propertyLabelsContentsValidator,
					PartiallyValidatedLabels::class,
					fn() => $propertyLabelsContentsValidator->getValidatedLabels(),
				];
			},
		];

		yield PropertyDescriptionsContentsValidator::class => [
			function ( self $self ) {
				$propertyDescriptionValidator = $self->createMock( PropertyDescriptionValidator::class );
				$propertyDescriptionsContentsValidator = new PropertyDescriptionsContentsValidator( $propertyDescriptionValidator );
				return [
					$propertyDescriptionValidator,
					$propertyDescriptionsContentsValidator,
					PartiallyValidatedDescriptions::class,
					fn() => $propertyDescriptionsContentsValidator->getValidatedDescriptions(),
				];
			},
		];

		yield ItemLabelsContentsValidator::class => [
			function ( self $self ) {
				$itemLabelValidator = $self->createMock( ItemLabelValidator::class );
				$itemLabelsContentsValidator = new ItemLabelsContentsValidator( $itemLabelValidator );
				return [
					$itemLabelValidator,
					$itemLabelsContentsValidator,
					PartiallyValidatedLabels::class,
					fn() => $itemLabelsContentsValidator->getValidatedLabels(),
				];
			},
		];

		yield ItemDescriptionsContentsValidator::class => [
			function ( self $self ) {
				$itemDescriptionValidator = $self->createMock( ItemDescriptionValidator::class );
				$itemDescriptionsContentsValidator = new ItemDescriptionsContentsValidator( $itemDescriptionValidator );
				return [
					$itemDescriptionValidator,
					$itemDescriptionsContentsValidator,
					PartiallyValidatedDescriptions::class,
					fn() => $itemDescriptionsContentsValidator->getValidatedDescriptions(),
				];
			},
		];
	}

}
