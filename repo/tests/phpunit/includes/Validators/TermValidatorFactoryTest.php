<?php

namespace Wikibase\Repo\Tests\Validators;

use InvalidArgumentException;
use ValueValidators\ValueValidator;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\Validators\FingerprintValidator;
use Wikibase\Repo\Validators\TermValidatorFactory;
use Wikibase\Repo\Tests\ChangeOp\ChangeOpTestMockProvider;

/**
 * @covers Wikibase\Repo\Validators\TermValidatorFactory
 *
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Thiemo Mättig
 */
class TermValidatorFactoryTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider invalidConstructorArgumentProvider
	 */
	public function testInvalidConstructorArgument( $maxLength ) {
		$this->setExpectedException( InvalidArgumentException::class );
		$this->newFactory( $maxLength, [] );
	}

	public function invalidConstructorArgumentProvider() {
		return [
			[ null ],
			[ 1.0 ],
			[ 0 ],
		];
	}

	/**
	 * @param int $maxLength
	 * @param string[] $languageCodes
	 *
	 * @return TermValidatorFactory
	 */
	private function newFactory( $maxLength, array $languageCodes ) {
		$mockProvider = new ChangeOpTestMockProvider( $this );

		return new TermValidatorFactory(
			$maxLength,
			$languageCodes,
			new BasicEntityIdParser(),
			$mockProvider->getMockLabelDescriptionDuplicateDetector()
		);
	}

	public function testGetFingerprintValidator() {
		$builders = $this->newFactory( 20, [] );

		$validator = $builders->getFingerprintValidator( Item::ENTITY_TYPE );

		$this->assertInstanceOf( FingerprintValidator::class, $validator );

		$dupeTerms = new TermList( [ new Term( 'en', 'DUPE' ) ] );
		$blaTerms = new TermList( [ new Term( 'en', 'bla' ) ] );
		$q99 = new ItemId( 'Q99' );

		$result = $validator->validateFingerprint( $dupeTerms, $blaTerms, $q99 );
		$this->assertTrue( $result->isValid(), 'isValid(good)' );

		$result = $validator->validateFingerprint( $dupeTerms, $dupeTerms, $q99 );
		$this->assertFalse( $result->isValid(), 'isValid(bad): label/description' );
	}

	public function testGetLanguageValidator() {
		$builders = $this->newFactory( 20, [ 'ja', 'ru' ] );

		$validator = $builders->getLanguageValidator();

		$this->assertInstanceOf( ValueValidator::class, $validator );

		$this->assertTrue( $validator->validate( 'ja' )->isValid() );
		$this->assertFalse( $validator->validate( 'xx' )->isValid() );
	}

	/**
	 * @dataProvider provideCommonTerms
	 */
	public function testCommonTermValidation( $string, $expected ) {
		$factory = $this->newFactory( 8, [] );
		$entityType = 'does not matter';
		/** @var ValueValidator[] $validators */
		$validators = [
			$factory->getLabelValidator( $entityType ),
			$factory->getDescriptionValidator(),
			$factory->getAliasValidator( $entityType ),
		];

		foreach ( $validators as $validator ) {
			$result = $validator->validate( $string );
			$this->assertSame( $expected, $result->isValid() );
		}
	}

	public function provideCommonTerms() {
		return [
			'Space' => [ 'x x', true ],
			'Unicode support' => [ 'Äöü', true ],

			// Length checks
			'To short' => [ '', false ],
			'Minimum length' => [ 'x', true ],
			'Maximum length' => [ '12345678', true ],
			'Too long' => [ '123456789', false ],

			// Enforced trimming
			'Leading space' => [ ' x', false ],
			'Leading newline' => [ "\nx", false ],
			'Trailing space' => [ 'x ', false ],
			'Trailing newline' => [ "x\n", false ],

			// Disallowed whitespace characters
			'U+0009: Tabulator' => [ "x\tx", false ],
			'U+000A: Newline' => [ "x\nx", false ],
			'U+000D: Return' => [ "x\rx", false ],
		];
	}

	public function testGetLabelValidator_property() {
		$builders = $this->newFactory( 8, [] );

		$validator = $builders->getLabelValidator( Property::ENTITY_TYPE );

		$this->assertFalse( $validator->validate( 'P12' )->isValid() );
		$this->assertTrue( $validator->validate( 'Q12' )->isValid() );
	}

}
