<?php

namespace Wikibase\Repo\Tests\Validators;

use InvalidArgumentException;
use PHPUnit4And6Compat;
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
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Thiemo Kreuz
 */
class TermValidatorFactoryTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	const MAX_LENGTH = 8;

	/**
	 * @dataProvider invalidConstructorArgumentProvider
	 */
	public function testInvalidConstructorArgument( $maxLength ) {
		$this->setExpectedException( InvalidArgumentException::class );
		$this->newFactory( $maxLength );
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
	private function newFactory( $maxLength = self::MAX_LENGTH, array $languageCodes = [] ) {
		$mockProvider = new ChangeOpTestMockProvider( $this );

		return new TermValidatorFactory(
			$maxLength,
			$languageCodes,
			new BasicEntityIdParser(),
			$mockProvider->getMockLabelDescriptionDuplicateDetector()
		);
	}

	public function testGetFingerprintValidator() {
		$builders = $this->newFactory();

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
		$builders = $this->newFactory( self::MAX_LENGTH );
		$entityType = 'does not matter';

		$result = $builders->getLabelValidator( $entityType )->validate( $string );
		$this->assertSame( $expected, $result->isValid() );

		$result = $builders->getDescriptionValidator()->validate( $string );
		$this->assertSame( $expected, $result->isValid() );

		$result = $builders->getAliasValidator( $entityType )->validate( $string );
		$this->assertSame( $expected, $result->isValid() );
	}

	public function provideCommonTerms() {
		return [
			'Space' => [ 'x x', true ],
			'Unicode support' => [ 'Äöü', true ],
			'T161263' => [ 'Ӆ', true ],

			// Length checks
			'To short' => [ '', false ],
			'Minimum length' => [ 'x', true ],
			'Maximum length' => [ str_repeat( 'x', self::MAX_LENGTH ), true ],
			'Too long' => [ str_repeat( 'x', self::MAX_LENGTH + 1 ), false ],

			// Enforced trimming
			'Leading space' => [ ' x', false ],
			'Leading newline' => [ "\nx", false ],
			'Trailing space' => [ 'x ', false ],
			'Trailing newline' => [ "x\n", false ],

			// Disallowed whitespace characters
			'U+0009: Tabulator' => [ "x\tx", false ],
			'U+000A: Newline' => [ "x\nx", false ],
			'U+000B: Vertical tab' => [ "x\x0Bx", false ],
			'U+000C: Form feed' => [ "x\fx", false ],
			'U+000D: Return' => [ "x\rx", false ],
			'U+0085: Next line' => [ "x\xC2\x85x", false ],
		];
	}

	public function testGetLabelValidator_property() {
		$builders = $this->newFactory();

		$validator = $builders->getLabelValidator( Property::ENTITY_TYPE );

		$this->assertInstanceOf( ValueValidator::class, $validator );

		$this->assertFalse( $validator->validate( 'P12' )->isValid() );
		$this->assertTrue( $validator->validate( 'Q12' )->isValid() );
	}

}
