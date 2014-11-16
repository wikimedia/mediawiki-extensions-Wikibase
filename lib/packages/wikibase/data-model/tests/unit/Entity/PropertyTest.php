<?php

namespace Wikibase\Test\Entity;

use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Claim\Claims;

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
	 * @return Claim[]
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

	public function equalsProvider() {
		$firstProperty = Property::newFromType( 'string' );
		$firstProperty->setStatements( $this->newNonEmptyStatementList() );

		$secondProperty = Property::newFromType( 'string' );
		$secondProperty->setStatements( $this->newNonEmptyStatementList() );

		$secondPropertyWithId = unserialize( serialize( $secondProperty ) );
		$secondPropertyWithId->setId( 42 );

		$differentId = unserialize( serialize( $secondPropertyWithId ) );
		$differentId->setId( 43 );

		return array(
			array( Property::newFromType( 'string' ), Property::newFromType( 'string' ) ),
			array( $firstProperty, $secondProperty ),
			array( $secondProperty, $secondPropertyWithId ),
			array( $secondPropertyWithId, $differentId ),
		);
	}

	/**
	 * @dataProvider equalsProvider
	 */
	public function testEquals( Property $firstProperty, Property $secondProperty ) {
		$this->assertTrue( $firstProperty->equals( $secondProperty ) );
		$this->assertTrue( $secondProperty->equals( $firstProperty ) );
	}

	private function getBaseProperty() {
		$property = Property::newFromType( 'string' );

		$property->setId( 42 );
		$property->getFingerprint()->setLabel( 'en', 'Same' );
		$property->getFingerprint()->setDescription( 'en', 'Same' );
		$property->getFingerprint()->setAliasGroup( 'en', array( 'Same' ) );
		$property->setStatements( $this->newNonEmptyStatementList() );

		return $property;
	}

	public function notEqualsProvider() {
		$differentLabel = $this->getBaseProperty();
		$differentLabel->getFingerprint()->setLabel( 'en', 'Different' );

		$differentDescription = $this->getBaseProperty();
		$differentDescription->getFingerprint()->setDescription( 'en', 'Different' );

		$differentAlias = $this->getBaseProperty();
		$differentAlias->getFingerprint()->setAliasGroup( 'en', array( 'Different' ) );

		$differentStatement = $this->getBaseProperty();
		$differentStatement->setStatements( new StatementList() );

		$property = $this->getBaseProperty();

		return array(
			'empty' => array( $property, Property::newFromType( 'string' ) ),
			'label' => array( $property, $differentLabel ),
			'description' => array( $property, $differentDescription ),
			'alias' => array( $property, $differentAlias ),
			'dataType' => array( Property::newFromType( 'string' ), Property::newFromType( 'foo' ) ),
			'statement' => array( $property, $differentStatement ),
		);
	}

	/**
	 * @dataProvider notEqualsProvider
	 */
	public function testNotEquals( Property $firstProperty, Property $secondProperty ) {
		$this->assertFalse( $firstProperty->equals( $secondProperty ) );
		$this->assertFalse( $secondProperty->equals( $firstProperty ) );
	}

	public function testPropertyWithStatementsIsNotEmpty() {
		$property = Property::newFromType( 'string' );
		$property->setStatements( $this->newNonEmptyStatementList() );

		$this->assertFalse( $property->isEmpty() );
	}

	public function testNewClaimReturnsStatementWithProvidedMainSnak() {
		/** @var Snak $snak */
		$snak = $this->getMock( 'Wikibase\DataModel\Snak\Snak' );

		$property = Property::newFromType( 'string' );
		$statement = $property->newClaim( $snak );

		$this->assertInstanceOf( 'Wikibase\DataModel\Statement\Statement', $statement );
		$this->assertEquals( $snak, $statement->getMainSnak() );
	}

	public function testSetClaims() {
		$property = Property::newFromType( 'string' );

		$statement0 = new Statement( new Claim( new PropertyNoValueSnak( 42 ) ) );
		$statement0->setGuid( 'TEST$NVS42' );

		$statement1 = new Statement( new Claim( new PropertySomeValueSnak( 42 ) ) );
		$statement1->setGuid( 'TEST$SVS42' );

		$statements = array( $statement0, $statement1 );

		$property->setClaims( new Claims( $statements ) );
		$this->assertEquals( count( $statements ), $property->getStatements()->count(), "added some statements" );

		$property->setClaims( new Claims() );
		$this->assertTrue( $property->getStatements()->isEmpty(), "should be empty again" );
	}

}
