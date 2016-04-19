<?php

namespace Wikibase\Test;

use InvalidArgumentException;
use ValueValidators\ValueValidator;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\Tests\ChangeOp\ChangeOpTestMockProvider;
use Wikibase\Repo\Validators\FingerprintValidator;
use Wikibase\Repo\Validators\TermValidatorFactory;

/**
 * @covers Wikibase\Repo\Validators\TermValidatorFactory
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseValidators
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class TermValidatorFactoryTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider invalidConstructorArgumentProvider
	 */
	public function testInvalidConstructorArgument( $maxLength ) {
		$this->setExpectedException( InvalidArgumentException::class );
		$this->newFactory( $maxLength, array() );
	}

	public function invalidConstructorArgumentProvider() {
		return array(
			array( null ),
			array( 1.0 ),
			array( 0 ),
		);
	}

	/**
	 * @param int $maxLength
	 * @param string[] $languageCodes
	 *
	 * @return TermValidatorFactory
	 */
	private function newFactory( $maxLength, array $languageCodes ) {
		$idParser = new BasicEntityIdParser();

		$mockProvider = new ChangeOpTestMockProvider( $this );
		$dupeDetector = $mockProvider->getMockLabelDescriptionDuplicateDetector();

		$builders = new TermValidatorFactory( $maxLength, $languageCodes, $idParser, $dupeDetector );
		return $builders;
	}

	public function testGetFingerprintValidator() {
		$builders = $this->newFactory( 20, array( 'ja', 'ru' ) );

		$validator = $builders->getFingerprintValidator( Item::ENTITY_TYPE );

		$this->assertInstanceOf( FingerprintValidator::class, $validator );

		$goodFingerprint = new Fingerprint(
			new TermList( array(
				new Term( 'en', 'DUPE' ),
			) ),
			new TermList( array(
				new Term( 'en', 'bla' ),
			) ),
			new AliasGroupList()
		);

		$labelDupeFingerprint = new Fingerprint(
			new TermList( array(
				new Term( 'en', 'DUPE' ),
			) ),
			new TermList( array(
				new Term( 'en', 'DUPE' ),
			) ),
			new AliasGroupList()
		);

		$q99 = new ItemId( 'Q99' );

		$this->assertTrue(
			$validator->validateFingerprint( $goodFingerprint, $q99 )->isValid(),
			'isValid(good)'
		);
		$this->assertFalse(
			$validator->validateFingerprint( $labelDupeFingerprint, $q99 )->isValid(),
			'isValid(bad): label/description'
		);
	}

	public function testGetLanguageValidator() {
		$builders = $this->newFactory( 20, array( 'ja', 'ru' ) );

		$validator = $builders->getLanguageValidator();

		$this->assertInstanceOf( ValueValidator::class, $validator );

		$this->assertTrue( $validator->validate( 'ja' )->isValid() );
		$this->assertFalse( $validator->validate( 'xx' )->isValid() );
	}

	public function testGetLabelValidator() {
		$builders = $this->newFactory( 8, array( 'en' ) );

		$validator = $builders->getLabelValidator( Item::ENTITY_TYPE );

		$this->assertInstanceOf( ValueValidator::class, $validator );

		$this->assertTrue( $validator->validate( 'foo' )->isValid() );
		$this->assertFalse( $validator->validate( '' )->isValid() );
		$this->assertFalse( $validator->validate( '0123456789' )->isValid() );
	}

	public function testGetLabelValidator_property() {
		$builders = $this->newFactory( 8, array( 'en' ) );

		$validator = $builders->getLabelValidator( Property::ENTITY_TYPE );

		$this->assertInstanceOf( ValueValidator::class, $validator );

		$this->assertTrue( $validator->validate( 'foo' )->isValid() );
		$this->assertFalse( $validator->validate( '' )->isValid() );
		$this->assertFalse( $validator->validate( '0123456789' )->isValid() );

		$this->assertFalse( $validator->validate( 'P12' )->isValid() );
		$this->assertTrue( $validator->validate( 'Q12' )->isValid() );
	}

	public function testGetDescriptionValidator() {
		$builders = $this->newFactory( 8, array( 'en' ) );

		$validator = $builders->getDescriptionValidator();

		$this->assertInstanceOf( ValueValidator::class, $validator );

		$this->assertTrue( $validator->validate( 'foo' )->isValid() );
		$this->assertFalse( $validator->validate( '' )->isValid() );
		$this->assertFalse( $validator->validate( '0123456789' )->isValid() );
	}

	public function testGetAliasValidator() {
		$builders = $this->newFactory( 8, array( 'en' ) );

		$validator = $builders->getAliasValidator( Item::ENTITY_TYPE );

		$this->assertInstanceOf( ValueValidator::class, $validator );

		$this->assertTrue( $validator->validate( 'foo' )->isValid() );
		$this->assertFalse( $validator->validate( '' )->isValid() );
		$this->assertFalse( $validator->validate( '0123456789' )->isValid() );
	}

}
