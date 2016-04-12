<?php

namespace Wikibase\DataModel\Tests\Entity;

use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;

/**
 * @covers Wikibase\DataModel\Entity\Property
 *
 * @group Wikibase
 * @group WikibaseProperty
 * @group WikibaseDataModel
 * @group PropertyTest
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyTest extends PHPUnit_Framework_TestCase {

	private function getNewEmpty() {
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

	// Below are tests copied from EntityTest

	public function labelProvider() {
		return array(
			array( 'en', 'spam' ),
			array( 'en', 'spam', 'spam' ),
			array( 'de', 'foo bar baz' ),
		);
	}

	/**
	 * @dataProvider labelProvider
	 * @param string $languageCode
	 * @param string $labelText
	 * @param string $moarText
	 */
	public function testSetLabel( $languageCode, $labelText, $moarText = 'ohi there' ) {
		$entity = $this->getNewEmpty();

		$entity->setLabel( $languageCode, $labelText );

		$this->assertEquals( $labelText, $entity->getFingerprint()->getLabel( $languageCode )->getText() );

		$entity->setLabel( $languageCode, $moarText );

		$this->assertEquals( $moarText, $entity->getFingerprint()->getLabel( $languageCode )->getText() );
	}

	public function descriptionProvider() {
		return array(
			array( 'en', 'spam' ),
			array( 'en', 'spam', 'spam' ),
			array( 'de', 'foo bar baz' ),
		);
	}

	/**
	 * @dataProvider descriptionProvider
	 * @param string $languageCode
	 * @param string $description
	 * @param string $moarText
	 */
	public function testSetDescription( $languageCode, $description, $moarText = 'ohi there' ) {
		$entity = $this->getNewEmpty();

		$entity->setDescription( $languageCode, $description );

		$this->assertEquals( $description, $entity->getFingerprint()->getDescription( $languageCode )->getText() );

		$entity->setDescription( $languageCode, $moarText );

		$this->assertEquals( $moarText, $entity->getFingerprint()->getDescription( $languageCode )->getText() );
	}

	public function aliasesProvider() {
		return array(
			array( array(
				       'en' => array( array( 'spam' ) )
			       ) ),
			array( array(
				       'en' => array( array( 'foo', 'bar', 'baz' ) )
			       ) ),
			array( array(
				       'en' => array( array( 'foo', 'bar' ), array( 'baz', 'spam' ) )
			       ) ),
			array( array(
				       'en' => array( array( 'foo', 'bar', 'baz' ) ),
				       'de' => array( array( 'foobar' ), array( 'baz' ) ),
			       ) ),
			// with duplicates
			array( array(
				       'en' => array( array( 'spam', 'ham', 'ham' ) )
			       ) ),
			array( array(
				       'en' => array( array( 'foo', 'bar' ), array( 'bar', 'spam' ) )
			       ) ),
		);
	}

	/**
	 * @dataProvider aliasesProvider
	 */
	public function testSetAliases( array $aliasesLists ) {
		$entity = $this->getNewEmpty();

		foreach ( $aliasesLists as $langCode => $aliasesList ) {
			foreach ( $aliasesList as $aliases ) {
				$entity->setAliases( $langCode, $aliases );
			}
		}

		foreach ( $aliasesLists as $langCode => $aliasesList ) {
			$expected = array_values( array_unique( array_pop( $aliasesList ) ) );
			asort( $aliasesList );

			$actual = $entity->getFingerprint()->getAliasGroup( $langCode )->getAliases();
			asort( $actual );

			$this->assertEquals( $expected, $actual );
		}
	}

	/**
	 * @dataProvider aliasesProvider
	 */
	public function testSetEmptyAlias( array $aliasesLists ) {
		$entity = $this->getNewEmpty();

		foreach ( $aliasesLists as $langCode => $aliasesList ) {
			foreach ( $aliasesList as $aliases ) {
				$entity->setAliases( $langCode, $aliases );
			}
		}
		$entity->setAliases( 'zh', array( 'wind', 'air', '', 'fire' ) );
		$entity->setAliases( 'zu', array( '', '' ) );

		foreach ( $aliasesLists as $langCode => $aliasesList ) {
			$expected = array_values( array_unique( array_pop( $aliasesList ) ) );
			asort( $aliasesList );

			$actual = $entity->getFingerprint()->getAliasGroup( $langCode )->getAliases();
			asort( $actual );

			$this->assertEquals( $expected, $actual );
		}
	}

	public function instanceProvider() {
		$entities = array();

		// empty
		$entity = $this->getNewEmpty();
		$entities[] = $entity;

		// ID only
		$entity = clone $entity;
		$entity->setId( 44 );

		$entities[] = $entity;

		// with labels and stuff
		$entity = $this->getNewEmpty();
		$entity->setAliases( 'en', array( 'o', 'noez' ) );
		$entity->setLabel( 'de', 'spam' );
		$entity->setDescription( 'en', 'foo bar baz' );

		$entities[] = $entity;

		// with labels etc and ID
		$entity = clone $entity;
		$entity->setId( 42 );

		$entities[] = $entity;

		$argLists = array();

		foreach ( $entities as $entity ) {
			$argLists[] = array( $entity );
		}

		return $argLists;
	}

	/**
	 * @dataProvider instanceProvider
	 * @param Property $entity
	 */
	public function testCopy( Property $entity ) {
		$copy = $entity->copy();

		// The equality method alone is not enough since it does not check the IDs.
		$this->assertTrue( $entity->equals( $copy ) );
		$this->assertEquals( $entity->getId(), $copy->getId() );

		$this->assertNotSame( $entity, $copy );
	}

	public function testCopyRetainsLabels() {
		$property = Property::newFromType( 'string' );

		$property->getFingerprint()->setLabel( 'en', 'foo' );
		$property->getFingerprint()->setLabel( 'de', 'bar' );

		$newProperty = $property->copy();

		$this->assertTrue( $newProperty->getFingerprint()->getLabels()->hasTermForLanguage( 'en' ) );
		$this->assertTrue( $newProperty->getFingerprint()->getLabels()->hasTermForLanguage( 'de' ) );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param Property $entity
	 */
	public function testSerialize( Property $entity ) {
		$string = serialize( $entity );

		$this->assertInternalType( 'string', $string );

		$instance = unserialize( $string );

		$this->assertTrue( $entity->equals( $instance ) );
		$this->assertEquals( $entity->getId(), $instance->getId() );
	}

	public function testWhenNoStuffIsSet_getFingerprintReturnsEmptyFingerprint() {
		$entity = $this->getNewEmpty();

		$this->assertEquals(
			new Fingerprint(),
			$entity->getFingerprint()
		);
	}

	public function testWhenLabelsAreSet_getFingerprintReturnsFingerprintWithLabels() {
		$entity = $this->getNewEmpty();

		$entity->setLabel( 'en', 'foo' );
		$entity->setLabel( 'de', 'bar' );

		$this->assertEquals(
			new Fingerprint(
				new TermList( array(
					new Term( 'en', 'foo' ),
					new Term( 'de', 'bar' ),
				) )
			),
			$entity->getFingerprint()
		);
	}

	public function testWhenTermsAreSet_getFingerprintReturnsFingerprintWithTerms() {
		$entity = $this->getNewEmpty();

		$entity->setLabel( 'en', 'foo' );
		$entity->setDescription( 'en', 'foo bar' );
		$entity->setAliases( 'en', array( 'foo', 'bar' ) );

		$this->assertEquals(
			new Fingerprint(
				new TermList( array(
					new Term( 'en', 'foo' ),
				) ),
				new TermList( array(
					new Term( 'en', 'foo bar' )
				) ),
				new AliasGroupList( array(
					new AliasGroup( 'en', array( 'foo', 'bar' ) )
				) )
			),
			$entity->getFingerprint()
		);
	}

	public function testGivenEmptyFingerprint_noTermsAreSet() {
		$entity = $this->getNewEmpty();
		$entity->setFingerprint( new Fingerprint() );

		$this->assertTrue( $entity->getFingerprint()->isEmpty() );
	}

	public function testGivenEmptyFingerprint_existingTermsAreRemoved() {
		$entity = $this->getNewEmpty();

		$entity->setLabel( 'en', 'foo' );
		$entity->setDescription( 'en', 'foo bar' );
		$entity->setAliases( 'en', array( 'foo', 'bar' ) );

		$entity->setFingerprint( new Fingerprint() );

		$this->assertTrue( $entity->getFingerprint()->isEmpty() );
	}

	public function testWhenSettingFingerprint_getFingerprintReturnsIt() {
		$fingerprint = new Fingerprint(
			new TermList( array(
				new Term( 'en', 'english label' ),
			) ),
			new TermList( array(
				new Term( 'en', 'english description' )
			) ),
			new AliasGroupList( array(
				new AliasGroup( 'en', array( 'first en alias', 'second en alias' ) )
			) )
		);

		$entity = $this->getNewEmpty();
		$entity->setFingerprint( $fingerprint );
		$newFingerprint = $entity->getFingerprint();

		$this->assertSame( $fingerprint, $newFingerprint );
	}

	public function testGetLabels() {
		$property = Property::newFromType( 'string' );
		$property->setLabel( 'en', 'foo' );

		$this->assertEquals(
			new TermList( array(
				new Term( 'en', 'foo' )
			) ),
			$property->getLabels()
		);
	}

	public function testGetDescriptions() {
		$property = Property::newFromType( 'string' );
		$property->setDescription( 'en', 'foo bar' );

		$this->assertEquals(
			new TermList( array(
				new Term( 'en', 'foo bar' )
			) ),
			$property->getDescriptions()
		);
	}

	public function testGetAliasGroups() {
		$property = Property::newFromType( 'string' );
		$property->setAliases( 'en', array( 'foo', 'bar' ) );

		$this->assertEquals(
			new AliasGroupList( array(
				new AliasGroup( 'en', array( 'foo', 'bar' ) )
			) ),
			$property->getAliasGroups()
		);
	}

	public function testGetLabels_sameListAsFingerprint() {
		$property = Property::newFromType( 'string' );

		$this->assertSame(
			$property->getFingerprint()->getLabels(),
			$property->getLabels()
		);
	}

	public function testGetDescriptions_sameListAsFingerprint() {
		$property = Property::newFromType( 'string' );

		$this->assertSame(
			$property->getFingerprint()->getDescriptions(),
			$property->getDescriptions()
		);
	}

	public function testGetAliasGroups_sameListAsFingerprint() {
		$property = Property::newFromType( 'string' );

		$this->assertSame(
			$property->getFingerprint()->getAliasGroups(),
			$property->getAliasGroups()
		);
	}

}
