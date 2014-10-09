<?php

namespace Wikibase\Test\Entity;

use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\StatementList;

/**
 * @covers Wikibase\DataModel\Entity\Property
 * @covers Wikibase\DataModel\Entity\Entity
 *
 * @group Wikibase
 * @group WikibaseProperty
 * @group WikibaseDataModel
 * @group PropertyTest
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyTest extends EntityTest {

	/**
	 * Returns no claims
	 *
	 * @return array
	 */
	public function makeClaims() {
		return array();
	}

	/**
	 * @see EntityTest::getNewEmpty
	 *
	 * @since 0.1
	 *
	 * @return Property
	 */
	protected function getNewEmpty() {
		return Property::newFromType( 'string' );
	}

	public function testNewFromType() {
		$property = Property::newFromType( 'string' );
		$this->assertInstanceOf( 'Wikibase\DataModel\Entity\Property', $property );
		$this->assertEquals( 'string', $property->getDataTypeId() );
	}

	public function testSetAndGetDataTypeId() {
		$property = Property::newFromType( 'string' );

		foreach ( array( 'string', 'foobar', 'nyan', 'string' ) as $typeId ) {
			$property->setDataTypeId( $typeId );
			$this->assertEquals( $typeId, $property->getDataTypeId() );
		}
	}

	public function testWhenIdSetWithNumber_GetIdReturnsPropertyId() {
		$property = Property::newFromType( 'string' );
		$property->setId( 42 );

		$this->assertHasCorrectIdType( $property );
	}

	protected function assertHasCorrectIdType( Property $property ) {
		$this->assertInstanceOf( 'Wikibase\DataModel\Entity\PropertyId', $property->getId() );
	}

	public function testWhenIdSetWithEntityId_GetIdReturnsPropertyId() {
		$property = Property::newFromType( 'string' );
		$property->setId( new PropertyId( 'P42' ) );

		$this->assertHasCorrectIdType( $property );
	}

	public function testPropertyWithTypeIsEmpty() {
		$this->assertTrue( Property::newFromType( 'string' )->isEmpty() );
	}

	public function testPropertyWithIdIsEmpty() {
		$property = Property::newFromType( 'string' );
		$property->setId( 1337 );
		$this->assertTrue( $property->isEmpty() );
	}

	public function testPropertyWithFingerprintIsNotEmpty() {
		$property = Property::newFromType( 'string' );
		$property->getFingerprint()->setAliasGroup( 'en', array( 'foo' ) );
		$this->assertFalse( $property->isEmpty() );
	}

	public function testClearRemovesAllButId() {
		$property = Property::newFromType( 'string' );

		$property->setId( 42 );
		$property->getFingerprint()->setLabel( 'en', 'foo' );

		$property->clear();

		$this->assertEquals( new PropertyId( 'P42' ), $property->getId() );
		$this->assertTrue( $property->getFingerprint()->isEmpty() );
	}

	public function testGetStatementsReturnsEmptyListForEmptyProperty() {
		$property = Property::newFromType( 'string' );

		$this->assertEquals( new StatementList(), $property->getStatements() );
	}

	public function testSetAndGetStatements() {
		$property = Property::newFromType( 'string' );

		$statementList = $this->newNonEmptyStatementList();
		$property->setStatements( $statementList );

		$this->assertEquals( $statementList, $property->getStatements() );
	}

	private function newNonEmptyStatementList() {
		$statementList = new StatementList();
		$statementList->addNewStatement( new PropertyNoValueSnak( 42 ) );
		$statementList->addNewStatement( new PropertyNoValueSnak( 1337 ) );

		return $statementList;
	}

	public function testPropertiesWithDifferentStatementsAreNotEqual() {
		$firstProperty = Property::newFromType( 'string' );
		$secondProperty = Property::newFromType( 'string' );

		$secondProperty->setStatements( $this->newNonEmptyStatementList() );

		$this->assertFalse( $firstProperty->equals( $secondProperty ) );
		$this->assertFalse( $secondProperty->equals( $firstProperty ) );
	}

	public function testPropertyWithStatementsIsNotEmpty() {
		$property = Property::newFromType( 'string' );
		$property->setStatements( $this->newNonEmptyStatementList() );

		$this->assertFalse( $property->isEmpty() );
	}

}
