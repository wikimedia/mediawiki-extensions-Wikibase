<?php

namespace Wikibase\Repo\Tests\Validators;

use InvalidArgumentException;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Repo\Validators\ForeignEntityValidator;

/**
 * @covers \Wikibase\Repo\Validators\ForeignEntityValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ForeignEntityValidatorTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	/**
	 * @dataProvider invalidRepositorySettingsProvider
	 */
	public function testGivenInvalidRepositorySettings_exceptionIsThrown( $settings ) {
		$this->setExpectedException( InvalidArgumentException::class );
		new ForeignEntityValidator( $settings );
	}

	public function invalidRepositorySettingsProvider() {
		return [
			'keys contain numbers' => [ [
				'foo' => [],
				123 => [],
			] ],
			'keys contain colons' => [ [
				'' => [],
				'foo:bar' => []
			] ],
			'values are not array' => [ [
				'foo' => 'bar',
			] ]
		];
	}

	/**
	 * @dataProvider invalidValidateArgumentProvider
	 */
	public function testGivenNotAnEntityId_validateThrowsException( $notAnEntityId ) {
		$this->setExpectedException( InvalidArgumentException::class );
		$validator = new ForeignEntityValidator( [] );
		$validator->validate( $notAnEntityId );
	}

	public function invalidValidateArgumentProvider() {
		return [
			[ 'Q123' ],
			[ false ],
			[ null ],
			[ 123 ]
		];
	}

	public function testGivenEntityIdValue_validateExtractsEntityId() {
		$validator = new ForeignEntityValidator( [ '' => [ Item::ENTITY_TYPE ] ] );
		$this->assertTrue( $validator->validate( new EntityIdValue( new ItemId( 'Q123' ) ) )->isValid() );
		$this->assertFalse( $validator->validate( new EntityIdValue( new ItemId( 'foo:Q123' ) ) )->isValid() );
	}

	public function testGivenRepositoryIsUnknown_validateEntityReturnsFalse() {
		$validator = new ForeignEntityValidator( [
			'foo' => [],
		] );
		$result = $validator->validate( new ItemId( 'bar:Q123' ) );

		$this->assertFalse( $result->isValid() );
		$this->assertSame(
			$result->getErrors()[0]->getCode(),
			'unknown-repository-name'
		);
	}

	public function testGivenEntityTypeNotSupportedFromRepo_validateEntityReturnsFalse() {
		$validator = new ForeignEntityValidator( [
			'' => [ 'mediainfo' ],
			'foo' => [ 'lexeme' ],
		] );
		$localItemResult = $validator->validate( new ItemId( 'Q42' ) );
		$fooItemResult = $validator->validate( new ItemId( 'foo:Q42' ) );

		$this->assertFalse( $localItemResult->isValid() );
		$this->assertSame(
			$localItemResult->getErrors()[0]->getCode(),
			'unsupported-entity-type'
		);
		$this->assertFalse( $fooItemResult->isValid() );
		$this->assertSame(
			$fooItemResult->getErrors()[0]->getCode(),
			'unsupported-entity-type'
		);
	}

	public function testGivenSupportedEntityFromKnownRepository_validateEntityReturnsTrue() {
		$validator = new ForeignEntityValidator( [
			'foo' => [ Item::ENTITY_TYPE, Property::ENTITY_TYPE ],
			'bar' => [ Item::ENTITY_TYPE ],
		] );

		$this->assertTrue( $validator->validate( new ItemId( 'foo:Q123' ) )->isValid() );
		$this->assertTrue( $validator->validate( new ItemId( 'bar:Q123' ) )->isValid() );
		$this->assertTrue( $validator->validate( new PropertyId( 'foo:P123' ) )->isValid() );
	}

}
