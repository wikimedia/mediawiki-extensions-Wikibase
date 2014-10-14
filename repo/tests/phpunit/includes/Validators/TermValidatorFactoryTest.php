<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Validators\TermValidatorFactory;

/**
 * @covers Wikibase\Validators\TermValidatorFactory
 *
 * @group Wikibase
 * @group WikibaseRepo
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

		$mockProvider = new ChangeOpTestMockProvider( $this );
		$dupeDetector = $mockProvider->getMockLabelDescriptionDuplicateDetector();
		$siteLinkLookup = $mockProvider->getMockSitelinkCache();

		$builders = new TermValidatorFactory( $maxLength, $languages, $idParser, $dupeDetector, $siteLinkLookup );
		return $builders;
	}

	public function testGetFingerprintValidator() {
		$builders = $this->newFactory( 20, array( 'ja', 'ru' ) );

		$validator = $builders->getFingerprintValidator( Item::ENTITY_TYPE );

		$this->assertInstanceOf( 'Wikibase\Validators\FingerprintValidator', $validator );

		$goodFingerprint = new Fingerprint(
			new TermList( array(
				new Term( 'en', 'DUPE' ),
			) ),
			new TermList( array(
				new Term( 'en', 'bla' ),
			) ),
			new AliasGroupList( array() )
		);

		$labelDupeFingerprint = new Fingerprint(
			new TermList( array(
				new Term( 'en', 'DUPE' ),
			) ),
			new TermList( array(
				new Term( 'en', 'DUPE' ),
			) ),
			new AliasGroupList( array() )
		);

		$q99 = new ItemId( 'Q99' );

		$this->assertTrue( $validator->validateFingerprint( $goodFingerprint, $q99 )->isValid(), 'isValid(good)' );
		$this->assertFalse( $validator->validateFingerprint( $labelDupeFingerprint, $q99 )->isValid(), 'isValid(bad): label/description' );
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
