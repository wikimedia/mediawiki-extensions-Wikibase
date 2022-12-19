<?php

namespace Wikibase\Repo\Tests\Validators;

use InvalidArgumentException;
use MediaWiki\Languages\LanguageNameUtils;
use ValueValidators\ValueValidator;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\Store\TermsCollisionDetectorFactory;
use Wikibase\Repo\Validators\FingerprintUniquenessValidator;
use Wikibase\Repo\Validators\LabelDescriptionNotEqualValidator;
use Wikibase\Repo\Validators\TermValidatorFactory;

/**
 * @covers \Wikibase\Repo\Validators\TermValidatorFactory
 *
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Thiemo Kreuz
 */
class TermValidatorFactoryTest extends \PHPUnit\Framework\TestCase {

	private const MAX_LENGTH = 8;

	/**
	 * @dataProvider invalidConstructorArgumentProvider
	 */
	public function testInvalidConstructorArgument( $maxLength ) {
		$this->expectException( InvalidArgumentException::class );
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
		return new TermValidatorFactory(
			$maxLength,
			$languageCodes,
			new BasicEntityIdParser(),
			$this->createMock( TermsCollisionDetectorFactory::class ),
			$this->createMock( TermLookup::class ),
			$this->createMock( LanguageNameUtils::class )
		);
	}

	public function entityTypeToFingerprintUniquenessValidatorProvider() {
		return [

			'unsupported type' => [
				'entityType' => 'mediainfo',
				'expectedValidatorType' => false, // false means null (no validator) is returned
			],

			'item is supported' => [
				'entityType' => Item::ENTITY_TYPE,
				'expectedValidatorType' => FingerprintUniquenessValidator::class,
			],

			'property is supported' => [
				'entityType' => Property::ENTITY_TYPE,
				'expectedValidatorType' => FingerprintUniquenessValidator::class,
			],

		];
	}

	/**
	 * @dataProvider entityTypeToFingerprintUniquenessValidatorProvider
	 */
	public function testGetFingerprintUniquenessValidator( $entityType, $expectedValidatorType ) {
		$validator = $this->newFactory()->getFingerprintUniquenessValidator( $entityType );

		if ( $expectedValidatorType === false ) {
			$this->assertNull( $validator );
		} else {
			$this->assertInstanceOf( $expectedValidatorType, $validator );
		}
	}

	public function testGetLabelDescriptionNotEqualValidator() {
		$builders = $this->newFactory();

		$validator = $builders->getLabelDescriptionNotEqualValidator();

		$this->assertInstanceOf( LabelDescriptionNotEqualValidator::class, $validator );

		$dupeTerms = new TermList( [ new Term( 'en', 'DUPE' ) ] );
		$blaTerms = new TermList( [ new Term( 'en', 'bla' ) ] );

		$result = $validator->validateLabelAndDescription( $dupeTerms, $blaTerms );
		$this->assertTrue( $result->isValid(), 'isValid(good)' );

		$result = $validator->validateLabelAndDescription( $dupeTerms, $dupeTerms );
		$this->assertFalse( $result->isValid(), 'isValid(bad): label/description' );
	}

	public function testGetLabelLanguageValidator() {
		$builders = $this->newFactory( 20, [ 'ja', 'ru', 'mul' ] );

		$validator = $builders->getLabelLanguageValidator();

		$this->assertTrue( $validator->validate( 'ja' )->isValid() );
		$this->assertTrue( $validator->validate( 'mul' )->isValid() );
		$this->assertFalse( $validator->validate( 'xx' )->isValid() );
	}

	public function testGetAliasLanguageValidator() {
		$builders = $this->newFactory( 20, [ 'ja', 'ru', 'mul' ] );

		$validator = $builders->getAliasLanguageValidator();

		$this->assertTrue( $validator->validate( 'ja' )->isValid() );
		$this->assertTrue( $validator->validate( 'mul' )->isValid() );
		$this->assertFalse( $validator->validate( 'xx' )->isValid() );
	}

	public function testGetDescriptionLanguageValidator() {
		$builders = $this->newFactory( 20, [ 'ja', 'ru', 'mul' ] );

		$validator = $builders->getDescriptionLanguageValidator();

		$this->assertTrue( $validator->validate( 'ja' )->isValid() );
		$this->assertFalse( $validator->validate( 'mul' )->isValid() );
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

		$result = $builders->getAliasValidator()->validate( $string );
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
