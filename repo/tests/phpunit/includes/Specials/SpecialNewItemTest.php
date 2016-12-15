<?php

namespace Wikibase\Repo\Tests\Specials;

use FauxRequest;
use RequestContext;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Specials\SpecialNewItem;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\Specials\SpecialNewItem
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
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Addshore
 */
class SpecialNewItemTest extends SpecialNewEntityTest {

	protected function newSpecialPage() {
		return new SpecialNewItem();
	}

	public function testAllNecessaryFormFieldsArePresent_WhenRendered() {

		list( $html ) = $this->executeSpecialPage();

		$this->assertHtmlContainsInputWithName( $html, 'lang' );
		$this->assertHtmlContainsInputWithName( $html, 'label' );
		$this->assertHtmlContainsInputWithName( $html, 'description' );
		$this->assertHtmlContainsInputWithName( $html, 'aliases' );
		$this->assertHtmlContainsSubmitControl( $html );
	}

	public function testSiteAndPageInputFieldsWithPredefinedValuesPresent_WhenRenderedWithGetParametersPassed() {
		$getParameters = [
			'site' => 'some-site',
			'page' => 'some-page'
		];

		list( $html ) = $this->executeSpecialPage( '', new FauxRequest( $getParameters ) );

		$this->assertHtmlContainsInputWithNameAndValue( $html, 'site', 'some-site' );
		$this->assertHtmlContainsInputWithNameAndValue( $html, 'page', 'some-page' );
	}

	public function testLabelAndDescriptionValuesAreSetAccordingToSubpagePath_WhenRendered() {
		$subPagePart1 = 'LabelText';
		$subPagePart2 = 'DescriptionText';
		$subPage = "{$subPagePart1}/{$subPagePart2}";

		list( $html, ) = $this->executeSpecialPage( $subPage );

		$this->assertHtmlContainsInputWithNameAndValue( $html, 'label', $subPagePart1 );
		$this->assertHtmlContainsInputWithNameAndValue( $html, 'description', $subPagePart2 );
	}

	public function provideValidEntityCreationRequests() {
		return [
			'only label is set' => [
				[
					'lang' => 'en',
					'label' => 'label',
					'description' => '',
					'aliases' => '',
				],
			],
			'another language' => [
				[
					'lang' => 'fr',
					'label' => 'label',
					'description' => '',
					'aliases' => '',
				],
			],
			'only description is set' => [
				[
					'lang' => 'en',
					'label' => '',
					'description' => 'desc',
					'aliases' => '',
				],
			],
			'single alias' => [
				[
					'lang' => 'en',
					'label' => '',
					'description' => '',
					'aliases' => 'alias',
				],
			],
			'multiple aliases' => [
				[
					'lang' => 'en',
					'label' => '',
					'description' => '',
					'aliases' => 'alias1|alias2|alias3',
				],
			],
			'all input is present' => [
				[
					'lang' => 'en',
					'label' => 'label',
					'description' => 'desc',
					'aliases' => 'a1|a2',
				],
			],
		];
	}

	public function provideInvalidEntityCreationRequests() {
		return [
			'unknown language' => [
				[
					'lang' => 'some-wierd-language',
					'label' => 'label',
					'description' => '',
					'aliases' => '',
				],
				'language code was not recognized',
			],
			'unknown site identifier' => [
				[
					'lang' => 'en',
					'label' => 'label',
					'description' => '',
					'aliases' => '',
					'site' => 'unknown',
					'page' => 'some page'
				],
				'site identifier was not recognized',
			],
			//Property - uniq: label(in language)

			//			'bad user token' => [  // TODO Probably should be implemented
			//				[
			//					'lang' => 'en',
			//					'label' => 'label',
			//					'description' => '',
			//					'aliases' => '',
			//					'wpEditToken' => 'some bad token'
			//				],
			//				'try again',
			//			],
			//			'all fields are empty' => [  // TODO Probably should be implemented
			//				[
			//					'lang' => 'en',
			//					'label' => '',
			//					'description' => '',
			//					'aliases' => '',
			//				],
			//				'???'
			//			],
		];
	}

	public function testErrorBeingDisplayed_WhenItemWithTheSameLabelAndDescriptionInThisLanguageAlreadyExists() {
		if ( $this->db->getType() === 'mysql' ) {
			$this->markTestSkipped( 'MySQL doesn\'t support self-joins on temporary tables' );
		}

		$formData = [
			'lang' => 'en',
			'label' => 'label1',
			'description' => 'description1',
			'aliases' => '',
		];
		$this->executeSpecialPage( '', new FauxRequest( $formData, true ) );

		list( $html ) = $this->executeSpecialPage( '', new FauxRequest( $formData, true ) );

		$this->assertHtmlContainsErrorMessage( $html, 'already has label' );
	}

	/**
	 * @param $itemUrl
	 * @return ItemId
	 */
	protected function extractEntityIdFromUrl( $itemUrl ) {
		$itemIdSerialization = preg_replace( '@^.*(Q\d+)$@', '$1', $itemUrl );
		$itemId = new ItemId( $itemIdSerialization );

		return $itemId;
	}

	/**
	 * @param array $form
	 * @param EntityDocument $entity
	 */
	protected function assertEntityMatchesFormData( array $form, EntityDocument $entity ) {
		$this->assertInstanceOf( Item::class, $entity );
		/** @var Item $entity */

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
	}

}
