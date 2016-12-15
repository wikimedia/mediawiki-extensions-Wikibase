<?php

namespace Wikibase\Repo\Tests\Specials;

use SpecialPageTestBase;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Repo\Specials\SpecialNewProperty;

/**
 * @covers Wikibase\Repo\Specials\SpecialNewProperty
 * @covers Wikibase\Repo\Specials\SpecialNewEntity
 * @covers Wikibase\Repo\Specials\SpecialWikibaseRepoPage
 * @covers Wikibase\Repo\Specials\SpecialWikibasePage
 *
 * @group Wikibase
 * @group WikibaseRepo
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
		return new SpecialNewProperty();
	}

	public function testAllNecessaryFormFieldsArePresent_WhenRendered() {

		list( $html ) = $this->executeSpecialPage();

		$this->assertHtmlContainsInputWithName( $html, 'lang' );
		$this->assertHtmlContainsInputWithName( $html, 'label' );
		$this->assertHtmlContainsInputWithName( $html, 'description' );
		$this->assertHtmlContainsInputWithName( $html, 'aliases' );
		$this->assertHtmlContainsSelectWithName( $html, 'datatype' );
		$this->assertHtmlContainsSubmitControl( $html );
	}

	public function testLabelAndDescriptionAndDataTypeValuesAreSetAccordingToSubpagePath_WhenRendered() {
		$subPagePart1 = 'LabelText';
		$subPagePart2 = 'DescriptionText';
		$subPagePart3 = 'url';
		$subPage = "{$subPagePart1}/{$subPagePart2}/{$subPagePart3}";

		list( $html ) = $this->executeSpecialPage( $subPage );

		$this->assertHtmlContainsInputWithNameAndValue( $html, 'label', $subPagePart1 );
		$this->assertHtmlContainsInputWithNameAndValue( $html, 'description', $subPagePart2 );
		$this->assertHtmlContainsSelectWithNameAndSelectedValue( $html, 'datatype', $subPagePart3 );
	}

	public function testFailsAndDisplaysAnError_WhenTryToCreateSecondPropertyWithTheSameLabel() {
		$formData = [
			'lang' => 'en',
			'label' => 'label',
			'description' => '',
			'aliases' => '',
			'datatype' => 'string',
		];
		$this->executeSpecialPage( '', new \FauxRequest( $formData, true ) );

		list( $html ) = $this->executeSpecialPage( '', new \FauxRequest( $formData, true ) );

		$this->assertHtmlContainsErrorMessage( $html, "already has label" );
	}

	/**
	 * @return array[][]
	 */
	public function provideValidEntityCreationRequests() {
		$labelIndex = 1;

		return [
			'only label is set' => [
				[
					'lang' => 'en',
					'label' => 'label' . $labelIndex ++,
					'description' => '',
					'aliases' => '',
					'datatype' => 'string',
				],
			],
			'another language' => [
				[
					'lang' => 'fr',
					'label' => 'label' . $labelIndex ++,
					'description' => '',
					'aliases' => '',
					'datatype' => 'string',
				],
			],
			'only description is set' => [
				[
					'lang' => 'en',
					'label' => '',
					'description' => 'desc',
					'aliases' => '',
					'datatype' => 'string',
				],
			],
			'single alias' => [
				[
					'lang' => 'en',
					'label' => '',
					'description' => '',
					'aliases' => 'alias',
					'datatype' => 'string',
				],
			],
			'multiple aliases' => [
				[
					'lang' => 'en',
					'label' => '',
					'description' => '',
					'aliases' => 'alias1|alias2|alias3',
					'datatype' => 'string',
				],
			],
			'another datatype is set' => [
				[
					'lang' => 'en',
					'label' => 'label' . $labelIndex ++,
					'description' => '',
					'aliases' => '',
					'datatype' => 'url',
				],
			],
			'all input is present' => [
				[
					'lang' => 'en',
					'label' => 'label' . $labelIndex,
					'description' => 'desc',
					'aliases' => 'a1|a2',
					'datatype' => 'url',
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
					'lang' => 'some-wierd-language',
					'label' => 'label-that-does-not-exist-1',
					'description' => '',
					'aliases' => '',
					'datatype' => 'string',
				],
				'language code was not recognized',
			],
			'unknown datatype' => [
				[
					'lang' => 'en',
					'label' => 'label-that-does-not-exist-2',
					'description' => '',
					'aliases' => '',
					'datatype' => 'unknown-datatype',
				],
				'Invalid data type specified',
			],
//			'bad user token' => [  // TODO Probably should be implemented
//				[
//				],
//				'try again',
//			],
//			'all fields are empty' => [  // TODO Probably should be implemented
//				[
//				],
//				'???'
//			],
		];
	}

	/**
	 * @param string $url
	 * @return EntityId
	 */
	protected function extractEntityIdFromUrl( $url ) {
		$itemIdSerialization = preg_replace( '@^.*(P\d+)$@', '$1', $url );

		return new PropertyId( $itemIdSerialization );
	}

	/**
	 * @param array $form
	 * @param EntityDocument $entity
	 * @return void
	 * @throws \Exception
	 */
	protected function assertEntityMatchesFormData( array $form, EntityDocument $entity ) {
		$this->assertInstanceOf( Property::class, $entity );
		/** @var Property $entity */

		$language = $form['lang'];
		if ( $form['label'] !== '' ) {
			$this->assertSame(
				$form['label'],
				$entity->getLabels()->getByLanguage( $language )->getText()
			);
		}

		if ( $form['description'] !== '' ) {
			$this->assertSame(
				$form['description'],
				$entity->getDescriptions()->getByLanguage( $language )->getText()
			);
		}

		if ( $form['aliases'] !== '' ) {
			$this->assertArrayEquals(
				explode( '|', $form['aliases'] ),
				$entity->getAliasGroups()->getByLanguage( $language )->getAliases()
			);
		}

		$this->assertEquals( $form['datatype'], $entity->getDataTypeId() );
	}

}
