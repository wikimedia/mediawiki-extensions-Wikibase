<?php

namespace Wikibase\Repo\Tests\Validators;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\Validators\LabelDescriptionNotEqualValidator;
use Wikibase\Repo\Validators\NotEqualViolation;

/**
 * @covers \Wikibase\Repo\Validators\LabelDescriptionNotEqualValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Greta Doci
 */
class LabelDescriptionNotEqualValidatorTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider provideLabelsDescriptionsAndExpectedErrors
	 */
	public function testValidateEntity(
		array $labels,
		array $descriptions,
		array $expectedErrors,
		array $languages = null
	) {
		if ( $languages !== null ) {
			$this->markTestSkipped( 'No need to test specific languages check' );
		}

		$validator = new LabelDescriptionNotEqualValidator();
		$labelsTermList = new TermList();
		$descriptionsTermList = new TermList();
		foreach ( $labels as $languageCode => $label ) {
			$labelsTermList->setTextForLanguage( $languageCode, $label );
		}
		foreach ( $descriptions as $languageCode => $description ) {
			$descriptionsTermList->setTextForLanguage( $languageCode, $description );
		}
		$item = new Item( null, new Fingerprint( $labelsTermList, $descriptionsTermList ) );

		$result = $validator->validateEntity( $item );

		$this->assertResult( $result, $expectedErrors );
	}

	/**
	 * @dataProvider provideLabelsDescriptionsAndExpectedErrors
	 */
	public function testValidateFingerprint(
		array $labels,
		array $descriptions,
		array $expectedErrors,
		array $languages = null
	) {
		$validator = new LabelDescriptionNotEqualValidator();
		$labelsTermList = new TermList();
		$descriptionsTermList = new TermList();
		foreach ( $labels as $languageCode => $label ) {
			$labelsTermList->setTextForLanguage( $languageCode, $label );
		}
		foreach ( $descriptions as $languageCode => $description ) {
			$descriptionsTermList->setTextForLanguage( $languageCode, $description );
		}

		$result = $validator->validateLabelAndDescription(
			$labelsTermList,
			$descriptionsTermList,
			$languages
		);

		$this->assertResult( $result, $expectedErrors );
	}

	public function provideLabelsDescriptionsAndExpectedErrors() {
		yield 'no data' => [ [], [], [] ];
		yield 'no label' => [ [], [ 'en' => 'description' ], [] ];
		yield 'no description' => [ [ 'en' => 'label' ], [], [] ];
		yield 'label different from description' => [
			[ 'en' => 'label' ],
			[ 'en' => 'description' ],
			[],
		];
		yield 'label = description in different languages' => [
			[ 'en' => 'abc' ],
			[ 'de' => 'abc' ],
			[],
		];

		yield 'label = description' => [
			[ 'en' => 'foo' ],
			[ 'en' => 'foo' ],
			[
				new NotEqualViolation( 'label should not be equal to description',
					'label-equals-description', [ 'en' ] ),
			],
		];
		yield 'label = description in non-English language' => [
			[ 'de' => 'foo' ],
			[ 'de' => 'foo' ],
			[
				new NotEqualViolation( 'label should not be equal to description',
					'label-equals-description', [ 'de' ] ),
			],
		];
		yield 'label = description in German and check only German' => [
			[ 'de' => 'foo' ],
			[ 'de' => 'foo' ],
			[
				new NotEqualViolation( 'label should not be equal to description',
					'label-equals-description', [ 'de' ] ),
			],
			[ 'de' ],
		];
		yield 'label = description in German check only English' => [
			[ 'de' => 'foo' ],
			[ 'de' => 'foo' ],
			[],
			[ 'en' ],
		];
		yield 'label = description with extra terms' => [
			[ 'en' => 'foo', 'de' => 'German label' ],
			[ 'en' => 'foo', 'sq' => 'Albanian description' ],
			[
				new NotEqualViolation( 'label should not be equal to description',
					'label-equals-description', [ 'en' ] ),
			],
		];
	}

	/**
	 * @param Result $result
	 * @param NotEqualViolation[] $expectedErrors
	 */
	protected function assertResult( Result $result, array $expectedErrors ) {
		$this->assertEquals( empty( $expectedErrors ), $result->isValid(), 'isValid()' );
		$errors = $result->getErrors();

		foreach ( $expectedErrors as $i => $expectedError ) {
			$error = $errors[$i];

			$this->assertInstanceOf( NotEqualViolation::class, $error );

			$this->assertEquals( $expectedError->getText(), $error->getText() );
			$this->assertEquals( $expectedError->getCode(), $error->getCode(), 'Error code:' );
			$this->assertEquals( $expectedError->getParameters(), $error->getParameters() );
		}
	}

}
