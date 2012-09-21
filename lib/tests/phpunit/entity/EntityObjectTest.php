<?php

namespace Wikibase\Test;
use \Wikibase\EntityObject as EntityObject;
use \Wikibase\Entity as Entity;

/**
 * Tests for the Wikibase\EntityObject deriving classes.
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
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class EntityObjectTest extends \MediaWikiTestCase {

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
			$expected = call_user_func_array( 'array_merge', $aliasesList );
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
			$expected = array_pop( $aliasesList );
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
			$expected = array_shift( $aliasesList );
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
				false
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
}