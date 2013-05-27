<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Diff\Diff;
use Diff\DiffOpAdd;
use Diff\DiffOpChange;
use Diff\DiffOpRemove;
use Wikibase\Claim;
use Wikibase\Entity;
use Wikibase\EntityDiff;
use Wikibase\EntityId;
use Wikibase\Item;
use Wikibase\Lib\ClaimGuidGenerator;
use Wikibase\ObjectComparer;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertySomeValueSnak;
use Wikibase\PropertyValueSnak;

/**
 * Tests for the Wikibase\Entity deriving classes.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseDataModel
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class EntityTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @since 0.1
	 *
	 * @return Entity
	 */
	protected abstract function getNewEmpty();

	/**
	 * @since 0.1
	 *
	 * @param array $data
	 *
	 * @return Entity
	 */
	protected abstract function getNewFromArray( array $data );

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

		$this->assertEquals( $labelText, $entity->getLabel( $languageCode ) );

		$entity->setLabel( $languageCode, $moarText );

		$this->assertEquals( $moarText, $entity->getLabel( $languageCode ) );
	}

	/**
	 * @dataProvider labelProvider
	 * @param string $languageCode
	 * @param string $labelText
	 * @param string $moarText
	 */
	public function testGetLabel( $languageCode, $labelText, $moarText = 'ohi there' ) {
		$entity = $this->getNewEmpty();

		$this->assertFalse( $entity->getLabel( $languageCode ) );

		$entity->setLabel( $languageCode, $labelText );

		$this->assertEquals( $labelText, $entity->getLabel( $languageCode ) );
	}

	/**
	 * @dataProvider labelProvider
	 * @param string $languageCode
	 * @param string $labelText
	 * @param string $moarText
	 */
	public function testRemoveLabel( $languageCode, $labelText, $moarText = 'ohi there' ) {
		$entity = $this->getNewEmpty();
		$entity->setLabel( $languageCode, $labelText );
		$entity->removeLabel( $languageCode );
		$this->assertFalse( $entity->getLabel( $languageCode ) );

		$entity->setLabel( 'nl', 'sadefradtgsrduy' );
		$entity->setLabel( $languageCode, $labelText );
		$entity->removeLabel( array( $languageCode, 'nl' ) );
		$this->assertFalse( $entity->getLabel( $languageCode ) );
		$this->assertFalse( $entity->getLabel( 'nl' ) );
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
	 * @param string $labelText
	 * @param string $moarText
	 */
	public function testSetDescription( $languageCode, $labelText, $moarText = 'ohi there' ) {
		$entity = $this->getNewEmpty();

		$entity->setDescription( $languageCode, $labelText );

		$this->assertEquals( $labelText, $entity->getDescription( $languageCode ) );

		$entity->setDescription( $languageCode, $moarText );

		$this->assertEquals( $moarText, $entity->getDescription( $languageCode ) );
	}

	/**
	 * @dataProvider descriptionProvider
	 * @param string $languageCode
	 * @param string $labelText
	 * @param string $moarText
	 */
	public function testGetDescription( $languageCode, $labelText, $moarText = 'ohi there' ) {
		$entity = $this->getNewEmpty();

		$this->assertFalse( $entity->getDescription( $languageCode ) );

		$entity->setDescription( $languageCode, $labelText );

		$this->assertEquals( $labelText, $entity->getDescription( $languageCode ) );
	}

	/**
	 * @dataProvider descriptionProvider
	 * @param string $languageCode
	 * @param string $labelText
	 * @param string $moarText
	 */
	public function testRemoveDescription( $languageCode, $labelText, $moarText = 'ohi there' ) {
		$entity = $this->getNewEmpty();
		$entity->setDescription( $languageCode, $labelText );
		$entity->removeDescription( $languageCode );
		$this->assertFalse( $entity->getDescription( $languageCode ) );

		$entity->setDescription( 'nl', 'sadefradtgsrduy' );
		$entity->setDescription( $languageCode, $labelText );
		$entity->removeDescription( array( $languageCode, 'nl' ) );
		$this->assertFalse( $entity->getDescription( $languageCode ) );
		$this->assertFalse( $entity->getDescription( 'nl' ) );
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
	public function testAddAliases( array $aliasesLists ) {
		$entity = $this->getNewEmpty();

		foreach ( $aliasesLists as $langCode => $aliasesList ) {
			foreach ( $aliasesList as $aliases ) {
				$entity->addAliases( $langCode, $aliases );
			}
		}

		foreach ( $aliasesLists as $langCode => $aliasesList ) {
			$expected = array_unique( call_user_func_array( 'array_merge', $aliasesList ) );
			asort( $expected );

			$actual = $entity->getAliases( $langCode );
			asort( $actual );

			$this->assertEquals( $expected, $actual );
		}
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
			$expected = array_unique( array_pop( $aliasesList ) );
			asort( $aliasesList );

			$actual = $entity->getAliases( $langCode );
			asort( $actual );

			$this->assertEquals( $expected, $actual );
		}
	}

	/**
	 * @dataProvider aliasesProvider
	 */
	public function testGetAliases( array $aliasesLists ) {
		$entity = $this->getNewEmpty();

		foreach ( $aliasesLists as $langCode => $aliasesList ) {
			$expected = array_unique( array_shift( $aliasesList ) );
			$entity->setAliases( $langCode, $expected );
			$actual = $entity->getAliases( $langCode );

			asort( $expected );
			asort( $actual );

			$this->assertEquals( $expected, $actual );
		}
	}

	public function duplicateAliasesProvider() {
		return array(
			array( array(
				'en' => array( array( 'foo', 'bar', 'baz' ), array( 'foo', 'bar', 'baz' ) )
			) ),
			array( array(
				'en' => array( array( 'foo', 'bar', 'baz' ), array( 'foo', 'bar' ) )
			) ),
			array( array(
				'en' => array( array( 'foo', 'bar' ), array( 'foo', 'bar', 'baz' ) )
			) ),
			array( array(
				'en' => array( array( 'foo', 'bar' ), array( 'bar', 'baz' ) ),
				'de' => array( array(), array( 'foo' ) ),
				'nl' => array( array( 'foo' ), array() ),
			) ),
			array( array(
				'en' => array( array( 'foo', 'bar', 'baz' ), array( 'foo', 'bar', 'baz', 'foo', 'bar' ) )
			) ),
		);
	}

	/**
	 * @dataProvider duplicateAliasesProvider
	 */
	public function testRemoveAliases( array $aliasesLists ) {
		$entity = $this->getNewEmpty();

		foreach ( $aliasesLists as $langCode => $aliasesList ) {
			$aliases = array_shift( $aliasesList );
			$removedAliases =  array_shift( $aliasesList );

			$entity->setAliases( $langCode, $aliases );
			$entity->removeAliases( $langCode, $removedAliases );

			$expected = array_diff( $aliases, $removedAliases );
			$actual = $entity->getAliases( $langCode );

			asort( $expected );
			asort( $actual );

			$this->assertEquals( $expected, $actual );
		}
	}

	public function testIsEmpty() {
		$entity = $this->getNewEmpty();

		$this->assertTrue( $entity->isEmpty() );

		$entity->addAliases( 'en', array( 'ohi' ) );

		$this->assertFalse( $entity->isEmpty() );

		$entity = $this->getNewEmpty();
		$entity->setDescription( 'en', 'o_O' );

		$this->assertFalse( $entity->isEmpty() );

		$entity = $this->getNewEmpty();
		$entity->setLabel( 'en', 'o_O' );

		$this->assertFalse( $entity->isEmpty() );
	}

	public function testClear() {
		$entity = $this->getNewEmpty();

		$entity->addAliases( 'en', array( 'ohi' ) );
		$entity->setDescription( 'en', 'o_O' );
		$entity->setLabel( 'en', 'o_O' );

		$entity->clear();

		$this->assertEmpty( $entity->getLabels(), "labels" );
		$this->assertEmpty( $entity->getDescriptions(), "descriptions" );
		$this->assertEmpty( $entity->getAllAliases(), "aliases" );

		$this->assertTrue( $entity->isEmpty() );
	}

	public static function provideEquals() {
		return array(
			array( #0
				array(),
				array(),
				true
			),
			array( #1
				array( 'labels' => array() ),
				array( 'descriptions' => null ),
				true
			),
			array( #2
				array( 'entity' => 'x23' ),
				array(),
				true
			),
			array( #3
				array( 'entity' => 'x23' ),
				array( 'entity' => 'x24' ),
				true
			),
			array( #4
				array( 'labels' => array(
					'en' => 'foo',
					'de' => 'bar',
				) ),
				array( 'labels' => array(
					'en' => 'foo',
				) ),
				false
			),
			array( #5
				array( 'labels' => array(
					'en' => 'foo',
					'de' => 'bar',
				) ),
				array( 'labels' => array(
					'de' => 'bar',
					'en' => 'foo',
				) ),
				true
			),
			array( #6
				array( 'aliases' => array(
					'en' => array( 'foo', 'FOO' ),
				) ),
				array( 'aliases' => array(
					'en' => array( 'foo', 'FOO', 'xyz' ),
				) ),
				false
			),
		);
	}

	/**
	 * @dataProvider provideEquals
	 */
	public function testEquals( array $a, array $b, $equals ) {
		$itemA = $this->getNewFromArray( $a );
		$itemB = $this->getNewFromArray( $b );

		$this->assertEquals( $equals, $itemA->equals( $itemB ) );
		$this->assertEquals( $equals, $itemB->equals( $itemA ) );
	}

	public function instanceProvider() {
		$entities = array();

		$entities[] = $this->getNewEmpty();

		$entity = $this->getNewEmpty();
		$entity->setAliases( 'en', array( 'o', 'noez' ) );
		$entity->setLabel( 'de', 'spam' );
		$entity->setDescription( 'en', 'foo bar baz' );

		$entities[] = $entity;

		$entity = clone $entity;

		$entity->setId( 42 );

		$entities[] = $entity;

		$entity = $this->getNewEmpty();

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
	 *
	 * @param Entity $entity
	 */
	public function testStub( Entity $entity ) {
		$copy = $entity->copy();
		$entity->stub();

		$this->assertTrue( $entity->equals( $copy ) );
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param Entity $entity
	 */
	public function testCopy( Entity $entity ) {
		$copy = $entity->copy();

		// The equality method alone is not enough since it does not check the IDs.
		$this->assertTrue( $entity->equals( $copy ) );
		$this->assertEquals( $entity->getPrefixedId(), $copy->getPrefixedId() );

		// More checks that should also pass
		$this->assertEquals( $entity, $copy );
		$this->assertFalse( $entity === $copy );
	}


	/**
	 * @dataProvider instanceProvider
	 *
	 * @param Entity $entity
	 */
	public function testSerialize( Entity $entity ) {
		$string = serialize( $entity );

		$this->assertInternalType( 'string', $string );

		$instance = unserialize( $string );

		$this->assertTrue( $entity->equals( $instance ) );
		$this->assertEquals( $entity->getPrefixedId(), $instance->getPrefixedId() );
	}

	public function baseIdProvider() {
		$ids = array();

		$ids[] = 0;
		$ids[] = 42;
		$ids[] = 9001;

		$type = $this->getNewEmpty()->getType();

		foreach ( array_values( $ids ) as $id ) {
			$ids[] = new EntityId( $type, $id );
		}

		$argLists = array();

		foreach ( $ids as $id ) {
			$argLists[] = array( $id );
		}

		return $argLists;
	}

	/**
	 * @dataProvider baseIdProvider
	 */
	public function testSetIdBase( $id ) {
		$entity = $this->getNewEmpty();

		$this->assertEquals( null, $entity->getId(), 'Getting an ID from an empty entity should return null' );

		$entity->setId( $id );

		$this->assertEquals(
			$entity->getType(),
			$entity->getId()->getEntityType(),
			'Entity type of returned ID is correct'
		);

		if ( $id instanceof EntityId ) {
			$id = $id->getNumericId();
		}

		$this->assertEquals( $id, $entity->getId()->getNumericId(), 'Numeric part of returned entity id is correct' );

		$entity->setId( 42 );

		$this->assertEquals( 42, $entity->getId()->getNumericId(), 'Numeric part of returned id is still correct' );
	}

	public function oldSerializationProvider() {
		$serializations = array();

		// Empty item
		$serializations[] = array(
			'O:19:"Wikibase\ItemObject":3:{s:13:" * statements";N;s:7:" * data";a:5:{s:5:"label";a:0:{}s:11:"description";a:0:{}s:7:"aliases";a:0:{}s:5:"links";a:0:{}s:10:"statements";a:0:{}}s:5:" * id";b:0;}',
			null
		);

		// Id 42, set both in the internal array and the id field
		$serializations[] = array(
			'O:19:"Wikibase\ItemObject":3:{s:13:" * statements";N;s:7:" * data";a:6:{s:6:"entity";s:3:"q42";s:5:"label";a:0:{}s:11:"description";a:0:{}s:7:"aliases";a:0:{}s:5:"links";a:0:{}s:10:"statements";a:0:{}}s:5:" * id";i:42;}',
			42
		);

		// Id 42, only set as id field
		$serializations[] = array(
			'O:19:"Wikibase\ItemObject":3:{s:13:" * statements";N;s:7:" * data";a:5:{s:5:"label";a:0:{}s:11:"description";a:0:{}s:7:"aliases";a:0:{}s:5:"links";a:0:{}s:10:"statements";a:0:{}}s:5:" * id";i:42;}',
			42
		);

		return $serializations;
	}

	/**
	 * @dataProvider oldSerializationProvider
	 */
	public function testUnserializeCompat( $oldSerialization, $expectedId ) {
		/**
		 * @var Entity $instance
		 */
		$instance = unserialize( $oldSerialization );

		if ( $expectedId === null ) {
			$thisData = $instance->toArray();
			$thatData = Item::newEmpty()->toArray();

			$comparer = new ObjectComparer();
			$equals = $comparer->dataEquals( $thisData, $thatData, array( 'entity' ) );

			$this->assertTrue( $equals );
		}
		else {
			$this->assertEquals( $expectedId, $instance->getId()->getNumericId() );
		}
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param Entity $entity
	 */
	public function testHasClaims( Entity $entity ) {
		$has = $entity->hasClaims();
		$this->assertInternalType( 'boolean', $has );

		$this->assertEquals( count( $entity->getClaims() ) !== 0, $has );
	}

	/**
	 * Tests Entity::newClaim and Entity::getIdFromClaimGuid
	 *
	 * @dataProvider instanceProvider
	 *
	 * @param Entity $entity
	 */
	public function testNewClaim( Entity $entity ) {
		if ( $entity->getId() === null ) {
			$entity->setId( new EntityId( $entity->getType(), 50 ) );
		}

		$snak = new PropertyNoValueSnak( 42 );
		$claim = $entity->newClaim( $snak );

		$this->assertInstanceOf( '\Wikibase\Claim', $claim );

		$this->assertTrue( $snak->equals( $claim->getMainSnak() ) );

		$guid = $claim->getGuid();

		$this->assertInternalType( 'string', $guid );

		$prefixedEntityId = Entity::getIdFromClaimGuid( $guid );

		$this->assertEquals( $entity->getPrefixedId(), $prefixedEntityId );
	}

	public function testNewClaimMore() {
		$snak = new PropertyNoValueSnak( 42 );
		$item = Item::newEmpty();

		$mockId = new EntityId( Item::ENTITY_TYPE, 9001 );
		$generator = new ClaimGuidGenerator( $mockId );

		$claim = $item->newClaim( $snak, $generator );
		$guid = $claim->getGuid();

		$this->assertInternalType( 'string', $guid );

		$prefixedEntityId = Entity::getIdFromClaimGuid( $guid );

		$this->assertEquals( $mockId->getPrefixedId(), $prefixedEntityId );
	}

	public function diffProvider() {
		$argLists = array();

		$emptyDiff = EntityDiff::newForType( $this->getNewEmpty()->getType() );

		$entity0 = $this->getNewEmpty();
		$entity1 = $this->getNewEmpty();
		$expected = clone $emptyDiff;

		$argLists[] = array( $entity0, $entity1, $expected );

		$entity0 = $this->getNewEmpty();
		$entity0->addAliases( 'nl', array( 'bah' ) );
		$entity0->addAliases( 'de', array( 'bah' ) );

		$entity1 = $this->getNewEmpty();
		$entity1->addAliases( 'en', array( 'foo', 'bar' ) );
		$entity1->addAliases( 'nl', array( 'bah', 'baz' ) );

		$entity1->setDescription( 'en', 'onoez' );

		$expected = new EntityDiff( array(
			'aliases' => new Diff( array(
				'en' => new Diff( array(
					new DiffOpAdd( 'foo' ),
					new DiffOpAdd( 'bar' ),
				), false ),
				'de' => new Diff( array(
					new DiffOpRemove( 'bah' ),
				), false ),
				'nl' => new Diff( array(
					new DiffOpAdd( 'baz' ),
				), false )
			) ),
			'description' => new Diff( array(
				'en' => new DiffOpAdd( 'onoez' ),
			) ),
		) );

		$argLists[] = array( $entity0, $entity1, $expected );

		$entity0 = clone $entity1;
		$entity1 = clone $entity1;
		$expected = clone $emptyDiff;

		$argLists[] = array( $entity0, $entity1, $expected );

		$entity0 = $this->getNewEmpty();

		$entity1 = $this->getNewEmpty();
		$entity1->setLabel( 'en', 'onoez' );

		$expected = new EntityDiff( array(
			'label' => new Diff( array(
				'en' => new DiffOpAdd( 'onoez' ),
			) ),
		) );

		$argLists[] = array( $entity0, $entity1, $expected );

		return $argLists;
	}

	/**
	 * @dataProvider diffProvider
	 *
	 * @param Entity $entity0
	 * @param Entity $entity1
	 * @param EntityDiff $expected
	 */
	public function testDiffEntities( Entity $entity0, Entity $entity1, EntityDiff $expected ) {
		$actual = $entity0->getDiff( $entity1 );

		$this->assertInstanceOf( '\Wikibase\EntityDiff', $actual );
		$this->assertEquals( count( $expected ), count( $actual ) );

		// TODO: equality check
		// (simple serialize does not work, since the order is not relevant, and not only on the top level)
	}

	public function patchProvider() {
		$claim0 = new Claim( new PropertyNoValueSnak( 42 ) );
		$claim1 = new Claim( new PropertySomeValueSnak( 42 ) );
		$claim2 = new Claim( new PropertyValueSnak( 42, new StringValue( 'ohi' ) ) );
		$claim3 = new Claim( new PropertyNoValueSnak( 1 ) );

		$claim0->setGuid( 'claim0' );
		$claim1->setGuid( 'claim1' );
		$claim2->setGuid( 'claim2' );
		$claim3->setGuid( 'claim3' );

		$argLists = array();


		$source = $this->getNewEmpty();
		$patch = new EntityDiff();
		$expected = clone $source;

		$argLists[] = array( $source, $patch, $expected );


		$source = $this->getNewEmpty();
		$source->setLabel( 'en', 'foo' );
		$source->setLabel( 'nl', 'bar' );
		$source->setDescription( 'de', 'foobar' );
		$source->setAliases( 'en', array( 'baz', 'bah' ) );
		$source->addClaim( $claim1 );

		$patch = new EntityDiff();
		$expected = clone $source;

		$argLists[] = array( $source, $patch, $expected );


		$source = clone $source;

		$patch = new EntityDiff( array(
			'description' => new Diff( array(
				'de' => new DiffOpChange( 'foobar', 'onoez' ),
				'en' => new DiffOpAdd( 'foobar' ),
			), true ),
		) );
		$expected = clone $source;
		$expected->setDescription( 'de', 'onoez' );
		$expected->setDescription( 'en', 'foobar' );

		$argLists[] = array( $source, $patch, $expected );


		$source = $this->getNewEmpty();
		$source->addClaim( $claim0 );
		$source->addClaim( $claim1 );
		$patch = new EntityDiff( array( 'claim' => new Diff( array(
			new DiffOpRemove( $claim0 ),
			new DiffOpAdd( $claim2 ),
			new DiffOpAdd( $claim3 )
		), false ) ) );
		$expected = $this->getNewEmpty();
		$expected->addClaim( $claim1 );
		$expected->addClaim( $claim2 );
		$expected->addClaim( $claim3 );

		$argLists[] = array( $source, $patch, $expected );

		return $argLists;
	}

	/**
	 * @dataProvider patchProvider
	 *
	 * @param Entity $source
	 * @param EntityDiff $patch
	 * @param Entity $expected
	 */
	public function testPatch( Entity $source, EntityDiff $patch, Entity $expected ) {
		$source->patch( $patch );
		$this->assertTrue( $expected->equals( $source ) );
	}

}
