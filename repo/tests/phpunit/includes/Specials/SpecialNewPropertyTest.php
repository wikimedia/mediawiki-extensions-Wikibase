<?php

namespace Wikibase\Repo\Tests\Specials;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Repo\Specials\SpecialNewProperty;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\Specials\SpecialNewProperty
 * @covers Wikibase\Repo\Specials\SpecialNewEntity
 * @covers Wikibase\Repo\Specials\SpecialWikibaseRepoPage
 * @covers Wikibase\Repo\Specials\SpecialWikibasePage
 *
 * @group Wikibase
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @group Database
 *        ^---- needed because we rely on Title objects internally
 *
 * @license GPL-2.0+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Addshore
 */
class SpecialNewPropertyTest extends SpecialNewEntityTest {

	protected function newSpecialPage() {
		$namespaceNumber = 123;
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		return new SpecialNewProperty(
			$this->copyrightView,
			new EntityNamespaceLookup( [ Property::ENTITY_TYPE => $namespaceNumber ] ),
			$wikibaseRepo->getSummaryFormatter(),
			$wikibaseRepo->getEntityTitleLookup(),
			$wikibaseRepo->newEditEntityFactory()
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
		$this->executeSpecialPage( '', new \FauxRequest( $formData, true ) );

		list( $html ) = $this->executeSpecialPage( '', new \FauxRequest( $formData, true ) );

		$this->assertHtmlContainsErrorMessage( $html, "already has label" );
	}

	public function provideValidEntityCreationRequests() {
		$labelIndex = 1;

		return [
			'only label is set' => [
				[
					SpecialNewProperty::FIELD_LANG => 'en',
					SpecialNewProperty::FIELD_LABEL => 'label' . $labelIndex ++,
					SpecialNewProperty::FIELD_DESCRIPTION => '',
					SpecialNewProperty::FIELD_ALIASES => '',
					SpecialNewProperty::FIELD_DATATYPE => 'string',
				],
			],
			'another language' => [
				[
					SpecialNewProperty::FIELD_LANG => 'fr',
					SpecialNewProperty::FIELD_LABEL => 'label' . $labelIndex ++,
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
					SpecialNewProperty::FIELD_LABEL => 'label' . $labelIndex ++,
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
				'language code was not recognized',
			],
			'unknown datatype' => [
				[
					SpecialNewProperty::FIELD_LANG => 'en',
					SpecialNewProperty::FIELD_LABEL => 'label-that-does-not-exist-2',
					SpecialNewProperty::FIELD_DESCRIPTION => '',
					SpecialNewProperty::FIELD_ALIASES => '',
					SpecialNewProperty::FIELD_DATATYPE => 'unknown-datatype',
				],
				'Invalid data type specified',
			],
			'all fingerprint fields are empty' => [
				[
					SpecialNewProperty::FIELD_LANG => 'en',
					SpecialNewProperty::FIELD_LABEL => '',
					SpecialNewProperty::FIELD_DESCRIPTION => '',
					SpecialNewProperty::FIELD_ALIASES => '',
					SpecialNewProperty::FIELD_DATATYPE => 'string',
				],
				'you need to fill'
			],
			'empty label and description, aliases contain only spaces and pipe symbols' => [
				[
					SpecialNewProperty::FIELD_LANG => 'en',
					SpecialNewProperty::FIELD_LABEL => '',
					SpecialNewProperty::FIELD_DESCRIPTION => '',
					SpecialNewProperty::FIELD_ALIASES => ' | || | ',
					SpecialNewProperty::FIELD_DATATYPE => 'string',
				],
				'you need to fill',
			],
			'empty datatype' => [
				[
					SpecialNewProperty::FIELD_LANG => 'en',
					SpecialNewProperty::FIELD_LABEL => 'empty datatype label',
					SpecialNewProperty::FIELD_DESCRIPTION => 'desc',
					SpecialNewProperty::FIELD_ALIASES => 'a1|a2',
					SpecialNewProperty::FIELD_DATATYPE => '',
				],
				'Invalid data type specified',
			],
		];
	}

	/**
	 * @param string $url
	 *
	 * @return PropertyId
	 */
	protected function extractEntityIdFromUrl( $url ) {
		preg_match( '/\bP\d+$/i', $url, $matches );
		return new PropertyId( $matches[0] );
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

}
