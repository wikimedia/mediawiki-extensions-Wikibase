<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Validators\TermValidatorFactory;
use Wikibase\SettingsArray;

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

	/**
	 * @param $maxLength
	 * @param $languages
	 *
	 * @return TermValidatorFactory
	 */
	protected function newFactory( $maxLength, $languages ) {
		$idParser = new BasicEntityIdParser();

		$builders = new TermValidatorFactory( $maxLength, $languages, $idParser );
		return $builders;
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
