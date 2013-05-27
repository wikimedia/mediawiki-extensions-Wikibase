<?php
namespace Wikibase\Test;
use Wikibase\Entity;
use Wikibase\EntityDiff;

/**
 * Tests for the Wikibase\EntityDiff deriving classes.
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
 * @group WikibaseDiff
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Jens Ohlig <jens.ohlig@wikimedia.de>
 */

abstract class EntityDiffOldTest extends \PHPUnit_Framework_TestCase {

	private static function newEntity ( $entityType ) {
		switch ( $entityType ) {
			case \Wikibase\Item::ENTITY_TYPE:
				$entity = \Wikibase\Item::newEmpty();
				break;
			case \Wikibase\Property::ENTITY_TYPE:
				$entity = \Wikibase\Property::newEmpty();
				break;
			case \Wikibase\Query::ENTITY_TYPE:
				$entity =\Wikibase\Query::newEmpty();
				break;
			default:
				throw new \MWException( "unknown entity type: $entityType" );
		}

		return $entity;
	}

	public static function generateApplyData( $entityType ) {
		$tests = array();

		// #0: add label
		$a = self::newEntity( $entityType );
		$a->setLabel( 'en', 'Test' );

		$b = $a->copy();
		$b->setLabel( 'de', 'Test' );

		$tests[] = array( $a, $b );

		// #1: remove label
		$a = self::newEntity( $entityType );
		$a->setLabel( 'en', 'Test' );
		$a->setLabel( 'de', 'Test' );

		$b = $a->copy();
		$b->removeLabel( array( 'en' ) );

		$tests[] = array( $a, $b );

		// #2: change label
		$a = self::newEntity( $entityType );
		$a->setLabel( 'en', 'Test' );

		$b = $a->copy();
		$b->setLabel( 'en', 'Test!!!' );

		// #3: add description ------------------------------
		$a = self::newEntity( $entityType );
		$a->setDescription( 'en', 'Test' );

		$b = $a->copy();
		$b->setDescription( 'de', 'Test' );

		$tests[] = array( $a, $b );

		// #4: remove description
		$a = self::newEntity( $entityType );
		$a->setDescription( 'en', 'Test' );
		$a->setDescription( 'de', 'Test' );

		$b = $a->copy();
		$b->removeDescription( array( 'en' ) );

		$tests[] = array( $a, $b );

		// #5: change description
		$a = self::newEntity( $entityType );
		$a->setDescription( 'en', 'Test' );

		$b = $a->copy();
		$b->setDescription( 'en', 'Test!!!' );

		$tests[] = array( $a, $b );

		// #6: add alias ------------------------------
		$a = self::newEntity( $entityType );
		$a->addAliases( 'en', array( 'Foo', 'Bar' ) );

		$b = $a->copy();
		$b->addAliases( 'en', array( 'Quux' ) );

		$tests[] = array( $a, $b );

		// #7: add alias language
		$a = self::newEntity( $entityType );
		$a->addAliases( 'en', array( 'Foo', 'Bar' ) );

		$b = $a->copy();
		$b->addAliases( 'de', array( 'Quux' ) );

		$tests[] = array( $a, $b );

		// #8: remove alias
		$a = self::newEntity( $entityType );
		$a->addAliases( 'en', array( 'Foo', 'Bar' ) );

		$b = $a->copy();
		$b->removeAliases( 'en', array( 'Foo' ) );

		$tests[] = array( $a, $b );

		// #9: remove alias language
		$a = self::newEntity( $entityType );

		$b = $a->copy();
		$b->addAliases( 'en', array( 'Foo', 'Bar' ) );
		$b->removeAliases( 'en', array( 'Foo', 'Bar' ) );

		$tests[] = array( $a, $b );
		return $tests;
	}

	/**
	 *
	 * @dataProvider provideApplyData
	 */
	public function testApply( Entity $a, Entity $b ) {
		$a->patch( $a->getDiff( $b ) );

		$this->assertEquals( $a->getLabels(), $b->getLabels() );
		$this->assertEquals( $a->getDescriptions(), $b->getDescriptions() );
		$this->assertEquals( $a->getAllAliases(), $b->getAllAliases() );
	}

	public static function provideConflictDetection() {
		$cases = array();

		// #0: adding a label where there was none before
		$base = self::newEntity( \Wikibase\Item::ENTITY_TYPE );
		$current = $base;

		$new = $base->copy();
		$new->setLabel( 'en', 'TEST' );

		$cases[] = array(
			$base,
			$current,
			$new,
			0 // there should eb no conflicts.
		);

		// #1: adding an alias where there was none before
		$base = self::newEntity( \Wikibase\Item::ENTITY_TYPE );
		$current = $base;

		$new = $base->copy();
		$new->addAliases( 'en', array( 'TEST' ) );

		$cases[] = array(
			$base,
			$current,
			$new,
			0 // there should eb no conflicts.
		);

		// #2: adding an alias where there already was one before
		$base = self::newEntity( \Wikibase\Item::ENTITY_TYPE );
		$base->addAliases( 'en', array( 'Foo' ) );
		$current = $base;

		$new = $base->copy();
		$new->addAliases( 'en', array( 'Bar' ) );

		$cases[] = array(
			$base,
			$current,
			$new,
			0 // there should be no conflicts.
		);

		// #3: adding an alias where there already was one in another language
		$base = self::newEntity( \Wikibase\Item::ENTITY_TYPE );
		$base->addAliases( 'en', array( 'Foo' ) );
		$current = $base;

		$new = $base->copy();
		$new->addAliases( 'de', array( 'Bar' ) );

		$cases[] = array(
			$base,
			$current,
			$new,
			0 // there should be no conflicts.
		);

		return $cases;
	}

	/**
	 * @dataProvider provideConflictDetection
	 */
	public function testConflictDetection( Entity $base, Entity $current, Entity $new, $expectedConflicts ) {
		$patch = $base->getDiff( $new ); // diff from base to new

		$patchedCurrent = clone $current;
		$patchedCurrent->patch( $patch );
		$cleanPatch = $base->getDiff( $patchedCurrent );

		$conflicts = $patch->count() - $cleanPatch->count();

		$this->assertEquals( $expectedConflicts, $conflicts, "check number of conflicts detected" );
	}



}