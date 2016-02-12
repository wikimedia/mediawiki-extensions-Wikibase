<?php

namespace Wikibase\DataModel\Tests\Entity;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Fingerprint;

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
	 * @see EntityTest::getNewEmpty
	 *
	 * @since 0.1
	 *
	 * @return Property
	 */
	protected function getNewEmpty() {
		return Property::newFromType( 'string' );
	}

	public function testConstructorWithAllParameters() {
		$property = new Property(
			new PropertyId( 'P42' ),
			new Fingerprint(),
			'string',
			new StatementList()
		);
		$this->assertInstanceOf( 'Wikibase\DataModel\Entity\Property', $property );
		$this->assertEquals( new PropertyId( 'P42' ), $property->getId() );
		$this->assertEquals( new Fingerprint(), $property->getFingerprint() );
		$this->assertEquals( 'string', $property->getDataTypeId() );
		$this->assertEquals( new StatementList(), $property->getStatements() );
	}

	public function testConstructorWithMinimalParameters() {
		$property = new Property( null, null, '' );
		$this->assertInstanceOf( 'Wikibase\DataModel\Entity\Property', $property );
		$this->assertNull( $property->getId() );
		$this->assertEquals( new Fingerprint(), $property->getFingerprint() );
		$this->assertEquals( '', $property->getDataTypeId() );
		$this->assertEquals( new StatementList(), $property->getStatements() );
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testGivenInvalidType_ConstructorThrowsException() {
		new Property( null, null, null );
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

	public function testWhenIdSetWithPropertyId_GetIdReturnsPropertyId() {
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
		$property->getStatements()->addNewStatement( new PropertyNoValueSnak( 1 ) );

		$property->clear();

		$this->assertEquals( new PropertyId( 'P42' ), $property->getId() );
		$this->assertTrue( $property->isEmpty() );
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

		$secondPropertyWithId = $secondProperty->copy();
		$secondPropertyWithId->setId( 42 );

		$differentId = $secondPropertyWithId->copy();
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

	public function cloneProvider() {
		$property = new Property( new PropertyId( 'P1' ), null, 'string' );
		$property->setLabel( 'en', 'original' );
		$property->getStatements()->addNewStatement( new PropertyNoValueSnak( 1 ) );

		return array(
			'copy' => array( $property, $property->copy() ),
			'native clone' => array( $property, clone $property ),
		);
	}

	/**
	 * @dataProvider cloneProvider
	 */
	public function testCloneIsEqualButNotIdentical( Property $original, Property $clone ) {
		$this->assertNotSame( $original, $clone );
		$this->assertTrue( $original->equals( $clone ) );
		$this->assertSame(
			$original->getId(),
			$clone->getId(),
			'id is immutable and must not be cloned'
		);

		// The clone must not reference the same mutable objects
		$this->assertNotSame( $original->getFingerprint(), $clone->getFingerprint() );
		$this->assertNotSame( $original->getStatements(), $clone->getStatements() );
		$this->assertNotSame(
			$original->getStatements()->getFirstStatementWithGuid( null ),
			$clone->getStatements()->getFirstStatementWithGuid( null )
		);
	}

	/**
	 * @dataProvider cloneProvider
	 */
	public function testOriginalDoesNotChangeWithClone( Property $original, Property $clone ) {
		$originalStatement = $original->getStatements()->getFirstStatementWithGuid( null );
		$clonedStatement = $clone->getStatements()->getFirstStatementWithGuid( null );

		$clone->setLabel( 'en', 'clone' );
		$clone->setDescription( 'en', 'clone' );
		$clone->setAliases( 'en', array( 'clone' ) );
		$clonedStatement->setGuid( 'clone' );
		$clonedStatement->setMainSnak( new PropertySomeValueSnak( 666 ) );
		$clonedStatement->setRank( Statement::RANK_DEPRECATED );
		$clonedStatement->getQualifiers()->addSnak( new PropertyNoValueSnak( 1 ) );
		$clonedStatement->getReferences()->addNewReference( new PropertyNoValueSnak( 1 ) );

		$this->assertSame( 'original', $original->getFingerprint()->getLabel( 'en' )->getText() );
		$this->assertFalse( $original->getFingerprint()->hasDescription( 'en' ) );
		$this->assertFalse( $original->getFingerprint()->hasAliasGroup( 'en' ) );
		$this->assertNull( $originalStatement->getGuid() );
		$this->assertSame( 'novalue', $originalStatement->getMainSnak()->getType() );
		$this->assertSame( Statement::RANK_NORMAL, $originalStatement->getRank() );
		$this->assertTrue( $originalStatement->getQualifiers()->isEmpty() );
		$this->assertTrue( $originalStatement->getReferences()->isEmpty() );
	}

}
