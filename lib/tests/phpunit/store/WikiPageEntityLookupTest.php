<?php

namespace Wikibase\Test;
use \Wikibase\EntityLookup;
use \Wikibase\WikiPageEntityLookup;

/**
 * Tests for the Wikibase\WikiPageEntityLookup class.
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
 * @since 0.3
 *
 * @ingroup WikibaseClient
 * @ingroup Test
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseEntityLookup
 *
 * @todo: test behavior for old revisions
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class WikipageEntityLookupTest extends EntityLookupTest {

	protected static $testEntities = array();

	public function setUp( ) {
		if ( !defined( 'WB_VERSION' ) ) {
			$this->markTestSkipped( "Only works on the repository (can't do foreign db access in unit tests)." );
		}

		parent::setUp();
	}

	/**
	 * @see EntityLookupTest::newEntityLoader()
	 *
	 * @return EntityLookup
	 */
	protected function newEntityLoader( array $entities ) {
		// make sure all test entities are in the database, but only do that once.
		/* @var \Wikibase\Entity $entity */
		foreach ( $entities as $entity ) {
			if ( !isset( self::$testEntities[$entity->getPrefixedId()] ) ) {
				self::storeTestEntity( $entity );
				$testEntities[$entity->getPrefixedId()] = $entity->getPrefixedId();
			}
		}

		return new WikiPageEntityLookup( false, CACHE_DB );
	}

	/*
	protected static function getTestEntityId( $handle ) {
		$entities = self::getTestEntities();

		if ( !isset( $entities[$handle] ) ) {
			return null;
		}

		return $entities[$handle]->getId();
	}

	protected static function getTestEntity( $handle ) {
		$entities = self::initTestEntities();

		return $entities[$handle];
	}

	protected static function initTestEntities() {
		static $initialized = false;

		if ( !$initialized ) {
			$entities = self::getTestEntities();

			foreach ( $entities as $handle => $entity ) {
				self::storeTestEntity( $entity );
				$testEntities[$handle] = $entity->getPrefixedId();
			}

			$initialized = true;
		}

		return self::$testEntities;
	}
	*/

	protected static function storeTestEntity( \Wikibase\Entity $entity ) {
		//NOTE: We are using EntityContent here, which is not available on the client.
		//      For now, this test case will only work on the repository.

		if ( !defined( 'WB_VERSION' ) ) {
			throw new \MWException( "Can't generate test entities in a client database." );
		}

		// FIXME: this is using repo functionality
		$content = \Wikibase\EntityContentFactory::singleton()->newFromEntity( $entity );
		$status = $content->save( "storeTestEntity" );

		if ( !$status->isOK() ) {
			throw new \MWException( "couldn't create " . $content->getTitle()->getFullText()
				. ":\n" . $status->getWikiText() );
		}
	}
}
