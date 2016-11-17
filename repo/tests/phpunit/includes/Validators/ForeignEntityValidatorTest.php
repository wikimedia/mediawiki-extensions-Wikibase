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

	public function testGivenLocalEntity_validateEntityReturnsTrue() {
		$validator = new ForeignEntityValidator( [] );

		$this->assertTrue(
			$validator->validateEntity( new Item( new ItemId( 'Q123' ) ) )->isValid()
		);
		$this->assertTrue(
			$validator->validateEntity( new Property( new PropertyId( 'P123' ), null, 'string' ) )->isValid()
		);
	}

	public function testGivenRepositoryIsUnknown_validateEntityReturnsFalse() {
		$validator = new ForeignEntityValidator( [
			'foo' => [],
		] );
		$result = $validator->validateEntity( new Item( new ItemId( 'bar:Q123' ) ) );

		$this->assertFalse( $result->isValid() );
		$this->assertSame(
			$result->getErrors()[0]->getCode(),
			'unknown-repository-name'
		);
	}

	public function testGivenEntityTypeNotSupportedByForeignRepo_validateEntityReturnsFalse() {
		$validator = new ForeignEntityValidator( [
			'foo' => [
				'supportedEntityTypes' => [ 'lexeme' ],
			]
		] );
		$result = $validator->validateEntity( new Item( new ItemId( 'foo:Q42' ) ) );

		$this->assertFalse( $result->isValid() );
		$this->assertSame(
			$result->getErrors()[0]->getCode(),
			'unsupported-entity-type'
		);
	}

	public function testGivenSupportedEntityFromKnownRepository_validateEntityReturnsTrue() {
		$validator = new ForeignEntityValidator( [
			'foo' => [
				'supportedEntityTypes' => [ Item::ENTITY_TYPE, Property::ENTITY_TYPE ],
			],
			'bar' => [
				'supportedEntityTypes' => [ Item::ENTITY_TYPE ],
			],
		] );

		$this->assertTrue(
			$validator->validateEntity( new Item( new ItemId( 'foo:Q123' ) ) )->isValid()
		);
		$this->assertTrue(
			$validator->validateEntity( new Item( new ItemId( 'bar:Q123' ) ) )->isValid()
		);
		$this->assertTrue(
			$validator->validateEntity( new Property( new PropertyId( 'foo:P123' ), null, 'string' ) )->isValid()
		);
	}

}
