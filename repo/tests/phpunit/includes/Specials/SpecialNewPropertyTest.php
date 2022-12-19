<?php

namespace Wikibase\Repo\Tests\Specials;

use FauxRequest;
use MediaWiki\Languages\LanguageNameUtils;
use PHPUnit\Framework\MockObject\MockObject;
use ValueValidators\Error;
use ValueValidators\Result;
use ValueValidators\ValueValidator;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Repo\Specials\SpecialNewProperty;
use Wikibase\Repo\Tests\WikibaseTablesUsed;
use Wikibase\Repo\Validators\NotMulValidator;
use Wikibase\Repo\Validators\TermValidatorFactory;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Specials\SpecialNewProperty
 * @covers \Wikibase\Repo\Specials\SpecialNewEntity
 * @covers \Wikibase\Repo\Specials\SpecialWikibaseRepoPage
 * @covers \Wikibase\Repo\Specials\SpecialWikibasePage
 *
 * @group Wikibase
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @group Database
 *        ^---- needed because we rely on Title objects internally
 *
 * @license GPL-2.0-or-later
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Addshore
 */
class SpecialNewPropertyTest extends SpecialNewEntityTestCase {
	use WikibaseTablesUsed;

	protected function setUp(): void {
		parent::setUp();
		$this->setUserLang( 'qqx' );
		$this->markTablesUsedForEntityEditing();

		WikibaseRepo::getSettings()->setSetting( 'tmpEnableMulLanguageCode', true );
	}

	protected function newSpecialPage() {
		$namespaceNumber = 123;
		return new SpecialNewProperty(
			self::TAGS,
			$this->copyrightView,
			new EntityNamespaceLookup( [ Property::ENTITY_TYPE => $namespaceNumber ] ),
			WikibaseRepo::getSummaryFormatter(),
			WikibaseRepo::getEntityTitleLookup(),
			WikibaseRepo::getEditEntityFactory(),
			WikibaseRepo::getDataTypeFactory(),
			$this->getTermValidatorFactoryMock(),
			WikibaseRepo::getPropertyTermsCollisionDetector(),
			WikibaseRepo::getValidatorErrorLocalizer()
		);
	}

	public function testAllNecessaryFormFieldsArePresent_WhenRendered() {

		list( $html ) = $this->executeSpecialPage();

		$this->assertHtmlContainsInputWithName( $html, SpecialNewProperty::FIELD_LANG );
		$this->assertHtmlContainsInputWithName( $html, SpecialNewProperty::FIELD_LABEL );
		$this->assertHtmlContainsInputWithName( $html, SpecialNewProperty::FIELD_DESCRIPTION );
		$this->assertHtmlContainsInputWithName( $html, SpecialNewProperty::FIELD_ALIASES );
		$this->assertHtmlContainsSelectWithName( $html, SpecialNewProperty::FIELD_DATATYPE );
		$this->assertHtmlContainsSubmitControl( $html );
	}

	public function testLabelAndDescriptionAndDataTypeValuesAreSetAccordingToSubpagePath_WhenRendered() {
		$subPagePart1 = 'LabelText';
		$subPagePart2 = 'DescriptionText';
		$subPagePart3 = 'url';
		$subPage = "{$subPagePart1}/{$subPagePart2}/{$subPagePart3}";

		list( $html ) = $this->executeSpecialPage( $subPage );

		$this->assertHtmlContainsInputWithNameAndValue(
			$html,
			SpecialNewProperty::FIELD_LABEL,
			$subPagePart1
		);
		$this->assertHtmlContainsInputWithNameAndValue(
			$html,
			SpecialNewProperty::FIELD_DESCRIPTION,
			$subPagePart2
		);
		$this->assertHtmlContainsSelectWithNameAndSelectedValue(
			$html,
			SpecialNewProperty::FIELD_DATATYPE,
			$subPagePart3
		);
	}

	public function testFailsAndDisplaysAnError_WhenTryToCreateSecondPropertyWithTheSameLabel() {
		$formData = [
			SpecialNewProperty::FIELD_LANG => 'en',
			SpecialNewProperty::FIELD_LABEL => 'label',
			SpecialNewProperty::FIELD_DESCRIPTION => '',
			SpecialNewProperty::FIELD_ALIASES => '',
			SpecialNewProperty::FIELD_DATATYPE => 'string',
		];
		$this->executeSpecialPage( '', new FauxRequest( $formData, true ) );

		list( $html ) = $this->executeSpecialPage( '', new FauxRequest( $formData, true ) );

		$this->assertHtmlContainsErrorMessage( $html, '(wikibase-validator-label-conflict: label, en, ' );
	}

	public function provideValidEntityCreationRequests() {
		$labelIndex = 1;

		return [
			'only label is set' => [
				[
					SpecialNewProperty::FIELD_LANG => 'en',
					SpecialNewProperty::FIELD_LABEL => 'label' . $labelIndex++,
					SpecialNewProperty::FIELD_DESCRIPTION => '',
					SpecialNewProperty::FIELD_ALIASES => '',
					SpecialNewProperty::FIELD_DATATYPE => 'string',
				],
			],
			'another language' => [
				[
					SpecialNewProperty::FIELD_LANG => 'fr',
					SpecialNewProperty::FIELD_LABEL => 'label' . $labelIndex++,
					SpecialNewProperty::FIELD_DESCRIPTION => '',
					SpecialNewProperty::FIELD_ALIASES => '',
					SpecialNewProperty::FIELD_DATATYPE => 'string',
				],
			],
			'only description is set' => [
				[
					SpecialNewProperty::FIELD_LANG => 'en',
					SpecialNewProperty::FIELD_LABEL => '',
					SpecialNewProperty::FIELD_DESCRIPTION => 'desc',
					SpecialNewProperty::FIELD_ALIASES => '',
					SpecialNewProperty::FIELD_DATATYPE => 'string',
				],
			],
			'single alias' => [
				[
					SpecialNewProperty::FIELD_LANG => 'en',
					SpecialNewProperty::FIELD_LABEL => '',
					SpecialNewProperty::FIELD_DESCRIPTION => '',
					SpecialNewProperty::FIELD_ALIASES => 'alias',
					SpecialNewProperty::FIELD_DATATYPE => 'string',
				],
			],
			'multiple aliases' => [
				[
					SpecialNewProperty::FIELD_LANG => 'en',
					SpecialNewProperty::FIELD_LABEL => '',
					SpecialNewProperty::FIELD_DESCRIPTION => '',
					SpecialNewProperty::FIELD_ALIASES => 'alias1|alias2|alias3',
					SpecialNewProperty::FIELD_DATATYPE => 'string',
				],
			],
			'another datatype is set' => [
				[
					SpecialNewProperty::FIELD_LANG => 'en',
					SpecialNewProperty::FIELD_LABEL => 'label' . $labelIndex++,
					SpecialNewProperty::FIELD_DESCRIPTION => '',
					SpecialNewProperty::FIELD_ALIASES => '',
					SpecialNewProperty::FIELD_DATATYPE => 'url',
				],
			],
			'all input is present' => [
				[
					SpecialNewProperty::FIELD_LANG => 'en',
					SpecialNewProperty::FIELD_LABEL => 'label' . $labelIndex,
					SpecialNewProperty::FIELD_DESCRIPTION => 'desc',
					SpecialNewProperty::FIELD_ALIASES => 'a1|a2',
					SpecialNewProperty::FIELD_DATATYPE => 'url',
				],
			],
		];
	}

	/**
	 * Data provider method
	 *
	 * @return array[]
	 */
	public function provideInvalidEntityCreationRequests() {
		return [
			'unknown language' => [
				[
					SpecialNewProperty::FIELD_LANG => 'some-weird-language',
					SpecialNewProperty::FIELD_LABEL => 'label-that-does-not-exist-1',
					SpecialNewProperty::FIELD_DESCRIPTION => '',
					SpecialNewProperty::FIELD_ALIASES => '',
					SpecialNewProperty::FIELD_DATATYPE => 'string',
				],
				'(wikibase-content-language-edit-not-recognized-language)',
			],
			'unknown datatype' => [
				[
					SpecialNewProperty::FIELD_LANG => 'en',
					SpecialNewProperty::FIELD_LABEL => 'label-that-does-not-exist-2',
					SpecialNewProperty::FIELD_DESCRIPTION => '',
					SpecialNewProperty::FIELD_ALIASES => '',
					SpecialNewProperty::FIELD_DATATYPE => 'unknown-datatype',
				],
				'(wikibase-newproperty-invalid-datatype)',
			],
			'all fingerprint fields are empty' => [
				[
					SpecialNewProperty::FIELD_LANG => 'en',
					SpecialNewProperty::FIELD_LABEL => '',
					SpecialNewProperty::FIELD_DESCRIPTION => '',
					SpecialNewProperty::FIELD_ALIASES => '',
					SpecialNewProperty::FIELD_DATATYPE => 'string',
				],
				'(wikibase-newproperty-insufficient-data)',
			],
			'empty label and description, aliases contain only spaces and pipe symbols' => [
				[
					SpecialNewProperty::FIELD_LANG => 'en',
					SpecialNewProperty::FIELD_LABEL => '',
					SpecialNewProperty::FIELD_DESCRIPTION => '',
					SpecialNewProperty::FIELD_ALIASES => ' | || | ',
					SpecialNewProperty::FIELD_DATATYPE => 'string',
				],
				'(wikibase-newproperty-insufficient-data)',
			],
			'empty datatype' => [
				[
					SpecialNewProperty::FIELD_LANG => 'en',
					SpecialNewProperty::FIELD_LABEL => 'empty datatype label',
					SpecialNewProperty::FIELD_DESCRIPTION => 'desc',
					SpecialNewProperty::FIELD_ALIASES => 'a1|a2',
					SpecialNewProperty::FIELD_DATATYPE => '',
				],
				'(wikibase-newproperty-invalid-datatype)',
			],
			'label and description are identical' => [
				[
					SpecialNewProperty::FIELD_LANG => 'en',
					SpecialNewProperty::FIELD_LABEL => 'something',
					SpecialNewProperty::FIELD_DESCRIPTION => 'something',
					SpecialNewProperty::FIELD_ALIASES => '',
					SpecialNewProperty::FIELD_DATATYPE => 'string',
				],
				'(wikibase-newproperty-same-label-and-description)',
			],
			'mul descriptions' => [
				[
					SpecialNewProperty::FIELD_LANG => 'mul',
					SpecialNewProperty::FIELD_LABEL => 'blah',
					SpecialNewProperty::FIELD_DESCRIPTION => 'a mul description',
					SpecialNewProperty::FIELD_ALIASES => '',
					SpecialNewProperty::FIELD_DATATYPE => 'string',
				],
				'(wikibase-validator-no-mul-descriptions: mul-language-name)',
			],
		];
	}

	/**
	 * @param string $url
	 *
	 * @return NumericPropertyId
	 */
	protected function extractEntityIdFromUrl( $url ) {
		preg_match( '/\bP\d+$/i', $url, $matches );
		return new NumericPropertyId( $matches[0] );
	}

	/**
	 * @param array $form
	 * @param EntityDocument $entity
	 *
	 * @return void
	 * @throws \Exception
	 */
	protected function assertEntityMatchesFormData( array $form, EntityDocument $entity ) {
		$this->assertInstanceOf( Property::class, $entity );
		/** @var Property $entity */

		$language = $form[SpecialNewProperty::FIELD_LANG];
		if ( $form[SpecialNewProperty::FIELD_LABEL] !== '' ) {
			$this->assertSame(
				$form[SpecialNewProperty::FIELD_LABEL],
				$entity->getLabels()->getByLanguage( $language )->getText()
			);
		}

		if ( $form[SpecialNewProperty::FIELD_DESCRIPTION] !== '' ) {
			$this->assertSame(
				$form[SpecialNewProperty::FIELD_DESCRIPTION],
				$entity->getDescriptions()->getByLanguage( $language )->getText()
			);
		}

		if ( $form[SpecialNewProperty::FIELD_ALIASES] !== '' ) {
			$this->assertArrayEquals(
				explode( '|', $form[SpecialNewProperty::FIELD_ALIASES] ),
				$entity->getAliasGroups()->getByLanguage( $language )->getAliases()
			);
		}

		$this->assertEquals( $form[SpecialNewProperty::FIELD_DATATYPE], $entity->getDataTypeId() );
	}

	public function testWhenLabelIsInvalid_ThenHtmlContainsErrorMessage() {
		$formData = [
			SpecialNewProperty::FIELD_LABEL => 'TOO_LONG_ERROR',
		];

		$this->assertHtmlContainsErrorTooLongMessage( $formData );
	}

	public function testWhenDescriptionIsInvalid_ThenHtmlContainsErrorMessage() {
		$formData = [
			SpecialNewProperty::FIELD_DESCRIPTION => 'TOO_LONG_ERROR',
		];

		$this->assertHtmlContainsErrorTooLongMessage( $formData );
	}

	public function testWhenAliasIsInvalid_ThenHtmlContainsErrorMessage() {
		$formData = [
			SpecialNewProperty::FIELD_ALIASES => 'TOO_LONG_ERROR',
		];

		$this->assertHtmlContainsErrorTooLongMessage( $formData );
	}

	public function testWhenAliasesAreInvalid_ThenHtmlContainsErrorMessage() {
		$formData = [
			SpecialNewProperty::FIELD_ALIASES => 'TOO_LONG_ERROR|TOO_LONG_ERROR',
		];

		$this->assertHtmlContainsErrorTooLongMessage( $formData );
	}

	private function assertHtmlContainsErrorTooLongMessage( $formData ) {
		list( $html ) = $this->executeSpecialPage( '', new FauxRequest( $formData, true ) );

		$this->assertHtmlContainsErrorMessage( $html, '(htmlform-invalid-input)' );
	}

	private function getTermValidatorFactoryMock() {
		$validatorMock = $this->getValidatorMock();

		$languageNameUtilsMock = $this->createMock( LanguageNameUtils::class );
		$languageNameUtilsMock->method( 'getLanguageName' )
			->with( 'mul', 'qqx' )
			->willReturn( 'mul-language-name' );

		/** @var MockObject|TermValidatorFactory $mock */
		$mock = $this->createMock( TermValidatorFactory::class );
		$mock->method( 'getDescriptionLanguageValidator' )
			->willReturn( new NotMulValidator(
				$languageNameUtilsMock
			) );
		$mock->method( $this->anything() )
			->willReturn( $validatorMock );

		return $mock;
	}

	private function getValidatorMock() {
		/** @var MockObject|ValueValidator $validatorMock */
		$validatorMock = $this->createMock( ValueValidator::class );
		$validatorMock->method( 'validate' )->willReturnCallback(
			function ( $value ) {
				if ( $value === 'TOO_LONG_ERROR' ) {
					return Result::newError( [ Error::newError( 'This is the too long error', null, 'too-long' ) ] );
				}
				return Result::newSuccess();
			}
		);

		return $validatorMock;
	}

}
