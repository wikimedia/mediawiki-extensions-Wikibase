<?php

namespace Wikibase\Test;

use ValueValidators\Error;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\SiteLink;
use Wikibase\SiteLinkLookup;
use Wikibase\TermDuplicateDetector;
use Wikibase\Validators\TermValidatorFactory;

/**
 * @covers TermValidatorFactory
 *
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class TermValidatorFactoryTest extends \PHPUnit_Framework_TestCase {

	public function detectLabelConflictsForEntity( Entity $entity ) {
		foreach ( $entity->getLabels() as $lang => $label ) {
			if ( $label === 'DUPE' ) {
				return Result::newError( array(
					Error::newError(
						'found conflicting terms',
						'label',
						'label-conflict',
						array(
							'label',
							$lang,
							$label,
							'P666'
						)
					)
				) );
			}
		}

		return Result::newSuccess();
	}

	public function detectLabelDescriptionConflictsForEntity( Entity $entity ) {
		foreach ( $entity->getLabels() as $lang => $label ) {
			$description = $entity->getDescription( $lang );

			if ( $description === null ) {
				continue;
			}

			if ( $label === 'DUPE' && $description === 'DUPE' ) {
				return Result::newError( array(
					Error::newError(
						'found conflicting terms',
						'label',
						'label-with-description-conflict',
						array(
							'label',
							$lang,
							$label,
							'Q666'
						)
					)
				) );
			}
		}

		return Result::newSuccess();
	}

	public function getConflictsForItem( Item $item ) {
		$conflicts = array();

		foreach ( $item->getSiteLinks() as $link ) {
			$page = $link->getPageName();
			$site = $link->getSiteId();

			if ( $page === 'DUPE' ) {
				$conflicts[] = array(
					'itemId' => 666,
					'siteId' => $site,
					'sitePage' => $page
				);
			}
		}

		return $conflicts;
	}

	/**
	 * @return TermDuplicateDetector
	 */
	private function getMockDupeDetector() {
		$dupeDetector = $this->getMockBuilder( 'Wikibase\TermDuplicateDetector' )
			->disableOriginalConstructor()
			->getMock();

		$dupeDetector->expects( $this->any() )
			->method( 'detectLabelConflictsForEntity' )
			->will( $this->returnCallback( array( $this, 'detectLabelConflictsForEntity' ) ) );

		$dupeDetector->expects( $this->any() )
			->method( 'detectLabelDescriptionConflictsForEntity' )
			->will( $this->returnCallback( array( $this, 'detectLabelDescriptionConflictsForEntity' ) ) );

		return $dupeDetector;
	}

	/**
	 * @return SiteLinkLookup
	 */
	private function getMockSiteLinkLookup() {
		$siteLinkLookup = $this->getMockBuilder( 'Wikibase\SiteLinkLookup' )
			->disableOriginalConstructor()
			->getMock();

		$siteLinkLookup->expects( $this->any() )
			->method( 'getConflictsForItem' )
			->will( $this->returnCallback( array( $this, 'getConflictsForItem' ) ) );

		return $siteLinkLookup;
	}

	/**
	 * @param $maxLength
	 * @param $languages
	 *
	 * @return TermValidatorFactory
	 */
	protected function newFactory( $maxLength, $languages ) {
		$idParser = new BasicEntityIdParser();
		$dupeDetector = $this->getMockDupeDetector();
		$siteLinkLookup = $this->getMockSiteLinkLookup();

		$builders = new TermValidatorFactory( $maxLength, $languages, $idParser, $dupeDetector, $siteLinkLookup );
		return $builders;
	}

	public function testGetUniquenessValidator() {
		$builders = $this->newFactory( 20, array( 'ja', 'ru' ) );

		$validator = $builders->getUniquenessValidator(
			Item::ENTITY_TYPE,
			TermValidatorFactory::CONSTRAINTS_ALL
		);

		$this->assertInstanceOf( 'Wikibase\Validators\EntityValidator', $validator );

		$goodEntity = Item::newEmpty();
		$goodEntity->setLabel( 'en', 'DUPE' );
		$goodEntity->setDescription( 'en', 'bla' );
		$goodEntity->addSiteLink( new SiteLink( 'enwiki', 'Foo' ) );

		$labelDupeEntity = Item::newEmpty();
		$labelDupeEntity->setLabel( 'en', 'DUPE' );
		$labelDupeEntity->setDescription( 'en', 'DUPE' );

		$linkDupeEntity = Item::newEmpty();
		$linkDupeEntity->addSiteLink( new SiteLink( 'enwiki', 'DUPE' ) );

		$this->assertTrue( $validator->validateEntity( $goodEntity )->isValid(), 'isValid(good)' );
		$this->assertFalse( $validator->validateEntity( $labelDupeEntity )->isValid(), 'isValid(bad): label/description' );
		$this->assertFalse( $validator->validateEntity( $linkDupeEntity )->isValid(), 'isValid(bad): sitelink' );
	}

	public function testGetLanguageValidator() {
		$builders = $this->newFactory( 20, array( 'ja', 'ru' ) );

		$validator = $builders->getLanguageValidator();

		$this->assertInstanceOf( 'ValueValidators\ValueValidator', $validator );

		$this->assertTrue( $validator->validate( 'ja' )->isValid() );
		$this->assertFalse( $validator->validate( 'xx' )->isValid() );
	}

	public function testGetLabelValidator() {
		$builders = $this->newFactory( 8, array( 'en' ) );

		$validator = $builders->getLabelValidator( Item::ENTITY_TYPE );

		$this->assertInstanceOf( 'ValueValidators\ValueValidator', $validator );

		$this->assertTrue( $validator->validate( 'foo' )->isValid() );
		$this->assertFalse( $validator->validate( '' )->isValid() );
		$this->assertFalse( $validator->validate( '0123456789' )->isValid() );
	}

	public function testGetLabelValidator_property() {
		$builders = $this->newFactory( 8, array( 'en' ) );

		$validator = $builders->getLabelValidator( Property::ENTITY_TYPE );

		$this->assertInstanceOf( 'ValueValidators\ValueValidator', $validator );

		$this->assertTrue( $validator->validate( 'foo' )->isValid() );
		$this->assertFalse( $validator->validate( '' )->isValid() );
		$this->assertFalse( $validator->validate( '0123456789' )->isValid() );

		$this->assertFalse( $validator->validate( 'P12' )->isValid() );
		$this->assertTrue( $validator->validate( 'Q12' )->isValid() );
	}

	public function testGetDescriptionValidator() {
		$builders = $this->newFactory( 8, array( 'en' ) );

		$validator = $builders->getDescriptionValidator( Item::ENTITY_TYPE );

		$this->assertInstanceOf( 'ValueValidators\ValueValidator', $validator );

		$this->assertTrue( $validator->validate( 'foo' )->isValid() );
		$this->assertFalse( $validator->validate( '' )->isValid() );
		$this->assertFalse( $validator->validate( '0123456789' )->isValid() );
	}

	public function testGetAliasValidator() {
		$builders = $this->newFactory( 8, array( 'en' ) );

		$validator = $builders->getAliasValidator( Item::ENTITY_TYPE );

		$this->assertInstanceOf( 'ValueValidators\ValueValidator', $validator );

		$this->assertTrue( $validator->validate( 'foo' )->isValid() );
		$this->assertFalse( $validator->validate( '' )->isValid() );
		$this->assertFalse( $validator->validate( '0123456789' )->isValid() );
	}

}
