<?php

namespace Wikibase\DataModel\Tests\Entity;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
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
 * @covers \Wikibase\DataModel\Entity\Property
 *
 * @group Wikibase
 * @group WikibaseDataModel
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @return Property
	 */
	private function getNewEmpty() {
		return Property::newFromType( 'string' );
	}

	public function testConstructorWithAllParameters() {
		$property = new Property(
			new NumericPropertyId( 'P42' ),
			new Fingerprint(),
			'string',
			new StatementList()
		);
		$this->assertInstanceOf( Property::class, $property );
		$this->assertEquals( new NumericPropertyId( 'P42' ), $property->getId() );
		$this->assertEquals( new Fingerprint(), $property->getFingerprint() );
		$this->assertSame( 'string', $property->getDataTypeId() );
		$this->assertEquals( new StatementList(), $property->getStatements() );
	}

	public function testConstructorWithMinimalParameters() {
		$property = new Property( null, null, '' );
		$this->assertInstanceOf( Property::class, $property );
		$this->assertNull( $property->getId() );
		$this->assertEquals( new Fingerprint(), $property->getFingerprint() );
		$this->assertSame( '', $property->getDataTypeId() );
		$this->assertEquals( new StatementList(), $property->getStatements() );
	}

	public function testGivenInvalidType_ConstructorThrowsException() {
		$this->expectException( InvalidArgumentException::class );
		new Property( null, null, null );
	}

	public function testNewFromType() {
		$property = Property::newFromType( 'string' );
		$this->assertInstanceOf( Property::class, $property );
		$this->assertSame( 'string', $property->getDataTypeId() );
	}

	public function testSetAndGetDataTypeId() {
		$property = Property::newFromType( 'string' );

		foreach ( [ 'string', 'foobar', 'nyan', 'string' ] as $typeId ) {
			$property->setDataTypeId( $typeId );
			$this->assertSame( $typeId, $property->getDataTypeId() );
		}
	}

	protected function assertHasCorrectIdType( Property $property ) {
		$this->assertInstanceOf( NumericPropertyId::class, $property->getId() );
	}

	public function testWhenIdSetWithPropertyId_GetIdReturnsPropertyId() {
		$property = Property::newFromType( 'string' );
		$property->setId( new NumericPropertyId( 'P42' ) );

		$this->assertHasCorrectIdType( $property );
	}

	public function testPropertyWithTypeIsEmpty() {
		$this->assertTrue( Property::newFromType( 'string' )->isEmpty() );
	}

	public function testPropertyWithIdIsEmpty() {
		$property = Property::newFromType( 'string' );
		$property->setId( new NumericPropertyId( 'P1337' ) );
		$this->assertTrue( $property->isEmpty() );
	}

	public function testPropertyWithFingerprintIsNotEmpty() {
		$property = Property::newFromType( 'string' );
		$property->setAliases( 'en', [ 'foo' ] );
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
		$secondPropertyWithId->setId( new NumericPropertyId( 'P42' ) );

		$differentId = $secondPropertyWithId->copy();
		$differentId->setId( new NumericPropertyId( 'P43' ) );

		return [
			[ Property::newFromType( 'string' ), Property::newFromType( 'string' ) ],
			[ $firstProperty, $secondProperty ],
			[ $secondProperty, $secondPropertyWithId ],
			[ $secondPropertyWithId, $differentId ],
		];
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

		$property->setId( new NumericPropertyId( 'P42' ) );
		$property->setLabel( 'en', 'Same' );
		$property->setDescription( 'en', 'Same' );
		$property->setAliases( 'en', [ 'Same' ] );
		$property->setStatements( $this->newNonEmptyStatementList() );

		return $property;
	}

	public function notEqualsProvider() {
		$differentLabel = $this->getBaseProperty();
		$differentLabel->setLabel( 'en', 'Different' );

		$differentDescription = $this->getBaseProperty();
		$differentDescription->setDescription( 'en', 'Different' );

		$differentAlias = $this->getBaseProperty();
		$differentAlias->setAliases( 'en', [ 'Different' ] );

		$differentStatement = $this->getBaseProperty();
		$differentStatement->setStatements( new StatementList() );

		$property = $this->getBaseProperty();

		return [
			'empty' => [ $property, Property::newFromType( 'string' ) ],
			'label' => [ $property, $differentLabel ],
			'description' => [ $property, $differentDescription ],
			'alias' => [ $property, $differentAlias ],
			'dataType' => [ Property::newFromType( 'string' ), Property::newFromType( 'foo' ) ],
			'statement' => [ $property, $differentStatement ],
		];
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
		$property = new Property( new NumericPropertyId( 'P1' ), null, 'string' );
		$property->setLabel( 'en', 'original' );
		$property->getStatements()->addNewStatement( new PropertyNoValueSnak( 1 ) );

		return [
			'copy' => [ $property, $property->copy() ],
			'native clone' => [ $property, clone $property ],
		];
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
		$clone->setAliases( 'en', [ 'clone' ] );
		$clonedStatement->setGuid( 'clone' );
		$clonedStatement->setMainSnak( new PropertySomeValueSnak( 666 ) );
		$clonedStatement->setRank( Statement::RANK_DEPRECATED );
		$clonedStatement->getQualifiers()->addSnak( new PropertyNoValueSnak( 1 ) );
		$clonedStatement->getReferences()->addNewReference( new PropertyNoValueSnak( 1 ) );

		$this->assertSame( 'original', $original->getLabels()->getByLanguage( 'en' )->getText() );
		$this->assertFalse( $original->getDescriptions()->hasTermForLanguage( 'en' ) );
		$this->assertFalse( $original->getAliasGroups()->hasGroupForLanguage( 'en' ) );
		$this->assertNull( $originalStatement->getGuid() );
		$this->assertSame( 'novalue', $originalStatement->getMainSnak()->getType() );
		$this->assertSame( Statement::RANK_NORMAL, $originalStatement->getRank() );
		$this->assertTrue( $originalStatement->getQualifiers()->isEmpty() );
		$this->assertTrue( $originalStatement->getReferences()->isEmpty() );
	}

	// Below are tests copied from EntityTest

	public function labelProvider() {
		return [
			[ 'en', 'spam' ],
			[ 'en', 'spam', 'spam' ],
			[ 'de', 'foo bar baz' ],
		];
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

		$this->assertSame( $labelText, $entity->getFingerprint()->getLabel( $languageCode )->getText() );

		$entity->setLabel( $languageCode, $moarText );

		$this->assertSame( $moarText, $entity->getFingerprint()->getLabel( $languageCode )->getText() );
	}

	public function descriptionProvider() {
		return [
			[ 'en', 'spam' ],
			[ 'en', 'spam', 'spam' ],
			[ 'de', 'foo bar baz' ],
		];
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

		$this->assertSame( $description, $entity->getFingerprint()->getDescription( $languageCode )->getText() );

		$entity->setDescription( $languageCode, $moarText );

		$this->assertSame( $moarText, $entity->getFingerprint()->getDescription( $languageCode )->getText() );
	}

	public function aliasesProvider() {
		return [
			[ [
				'en' => [ [ 'spam' ] ],
			] ],
			[ [
				'en' => [ [ 'foo', 'bar', 'baz' ] ],
			] ],
			[ [
				'en' => [ [ 'foo', 'bar' ], [ 'baz', 'spam' ] ],
			] ],
			[ [
				'en' => [ [ 'foo', 'bar', 'baz' ] ],
				'de' => [ [ 'foobar' ], [ 'baz' ] ],
			] ],
			// with duplicates
			[ [
				'en' => [ [ 'spam', 'ham', 'ham' ] ],
			] ],
			[ [
				'en' => [ [ 'foo', 'bar' ], [ 'bar', 'spam' ] ],
			] ],
		];
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
			$actual = $entity->getFingerprint()->getAliasGroup( $langCode )->getAliases();
			$this->assertSame( $expected, $actual );
		}
	}

	public function testSetEmptyAlias() {
		$property = Property::newFromType( 'string' );

		$property->setAliases( 'en', [ 'wind', 'air', '', 'fire' ] );
		$this->assertSame(
			[ 'wind', 'air', 'fire' ],
			$property->getAliasGroups()->getByLanguage( 'en' )->getAliases()
		);

		$property->setAliases( 'en', [ '', '' ] );
		$this->assertFalse( $property->getAliasGroups()->hasGroupForLanguage( 'en' ) );
	}

	public function instanceProvider() {
		$entities = [];

		// empty
		$entity = $this->getNewEmpty();
		$entities[] = $entity;

		// ID only
		$entity = clone $entity;
		$entity->setId( new NumericPropertyId( 'P44' ) );

		$entities[] = $entity;

		// with labels and stuff
		$entity = $this->getNewEmpty();
		$entity->setAliases( 'en', [ 'o', 'noez' ] );
		$entity->setLabel( 'de', 'spam' );
		$entity->setDescription( 'en', 'foo bar baz' );

		$entities[] = $entity;

		// with labels etc and ID
		$entity = clone $entity;
		$entity->setId( new NumericPropertyId( 'P42' ) );

		$entities[] = $entity;

		$argLists = [];

		foreach ( $entities as $entity ) {
			$argLists[] = [ $entity ];
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

		$this->assertIsString( $string );

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
				new TermList( [
					new Term( 'en', 'foo' ),
					new Term( 'de', 'bar' ),
				] )
			),
			$entity->getFingerprint()
		);
	}

	public function testWhenTermsAreSet_getFingerprintReturnsFingerprintWithTerms() {
		$entity = $this->getNewEmpty();

		$entity->setLabel( 'en', 'foo' );
		$entity->setDescription( 'en', 'foo bar' );
		$entity->setAliases( 'en', [ 'foo', 'bar' ] );

		$this->assertEquals(
			new Fingerprint(
				new TermList( [
					new Term( 'en', 'foo' ),
				] ),
				new TermList( [
					new Term( 'en', 'foo bar' ),
				] ),
				new AliasGroupList( [
					new AliasGroup( 'en', [ 'foo', 'bar' ] ),
				] )
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
		$entity->setAliases( 'en', [ 'foo', 'bar' ] );

		$entity->setFingerprint( new Fingerprint() );

		$this->assertTrue( $entity->getFingerprint()->isEmpty() );
	}

	public function testWhenSettingFingerprint_getFingerprintReturnsIt() {
		$fingerprint = new Fingerprint(
			new TermList( [
				new Term( 'en', 'english label' ),
			] ),
			new TermList( [
				new Term( 'en', 'english description' ),
			] ),
			new AliasGroupList( [
				new AliasGroup( 'en', [ 'first en alias', 'second en alias' ] ),
			] )
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
			new TermList( [
				new Term( 'en', 'foo' ),
			] ),
			$property->getLabels()
		);
	}

	public function testGetDescriptions() {
		$property = Property::newFromType( 'string' );
		$property->setDescription( 'en', 'foo bar' );

		$this->assertEquals(
			new TermList( [
				new Term( 'en', 'foo bar' ),
			] ),
			$property->getDescriptions()
		);
	}

	public function testGetAliasGroups() {
		$property = Property::newFromType( 'string' );
		$property->setAliases( 'en', [ 'foo', 'bar' ] );

		$this->assertEquals(
			new AliasGroupList( [
				new AliasGroup( 'en', [ 'foo', 'bar' ] ),
			] ),
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

	/**
	 * @dataProvider clearableProvider
	 */
	public function testClear( Property $property ) {
		$clone = $property->copy();

		$property->clear();

		$this->assertEquals(
			$clone->getId(),
			$property->getId(),
			'cleared Property should keep its id'
		);
		$this->assertSame(
			$clone->getDataTypeId(),
			$property->getDataTypeId(),
			'cleared Property should keep its data type'
		);
		$this->assertTrue( $property->isEmpty(), 'cleared Property should be empty' );
	}

	public function clearableProvider() {
		return [
			'empty' => [
				new Property( new NumericPropertyId( 'P123' ), null, 'string' ),
			],
			'with fingerprint' => [
				new Property(
					new NumericPropertyId( 'P321' ),
					new Fingerprint( new TermList( [ new Term( 'en', 'foo' ) ] ) ),
					'time'
				),
			],
			'with statement' => [
				new Property(
					new NumericPropertyId( 'P234' ),
					null,
					'wikibase-entityid',
					$this->newNonEmptyStatementList()
				),
			],
		];
	}

}
