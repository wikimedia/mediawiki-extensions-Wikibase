<?php

namespace Wikibase\Test\Repo\Validators;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Repo\Validators\ForeignEntityValidator;

/**
 * @covers \Wikibase\Repo\Validators\ForeignEntityValidator
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @license GPL-2.0+
 */
class ForeignEntityValidatorTest extends \PHPUnit_Framework_TestCase {

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

	public function testGivenLocalEntity_validateEntityReturnsTrue() {
		$validator = new ForeignEntityValidator( [] );

		$this->assertTrue(
			$validator->validate( new ItemId( 'Q123' ) )->isValid()
		);
		$this->assertTrue(
			$validator->validate( new PropertyId( 'P123' ) )->isValid()
		);
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

	public function testGivenEntityTypeNotSupportedByForeignRepo_validateEntityReturnsFalse() {
		$validator = new ForeignEntityValidator( [
			'foo' => [ 'lexeme' ],
		] );
		$result = $validator->validate( new ItemId( 'foo:Q42' ) );

		$this->assertFalse( $result->isValid() );
		$this->assertSame(
			$result->getErrors()[0]->getCode(),
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
