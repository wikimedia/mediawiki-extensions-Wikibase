<?php

namespace Wikibase\Test;
use \Wikibase\EntityCacheTable;
use \Wikibase\Entity;
use \Wikibase\EntityId;
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
 * @since 0.1
 *
 * @ingroup WikibaseClient
 * @ingroup Test
 *
 * @group Broken
 *        ^--- broken because it needs to wb_entity_per_page from the repo database.
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseEntityLookup
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class WikipageEntityLookupTest extends EntityTestCase {

	static $testEntityDefinitions = array(
		'foo' => array( // handle "foo"
			'type' => 'item',
			'label' => array(
				'en' => 'Foo',
				'de' => 'Bar',
			)
		)
	);

	static $testEntities = null;

	protected static function getTestEntityId( $handle ) {
		$entity = self::getTestEntityData( $handle );

		return new EntityId( $entity['type'], $entity['id'] );
	}

	protected static function getTestEntityData( $handle ) {
		$entities = self::getTestEntities();

		return $entities[$handle];
	}

	protected static function getTestEntities() {
		if ( self::$testEntities === null ) {
			self::$testEntities = array();

			foreach ( self::$testEntityDefinitions as $handle => $def ) {
				$entity = self::storeTestEntity( $def );

				self::$testEntities[$handle] = $entity;
			}
		}

		return self::$testEntities;
	}

	protected static function storeTestEntity( $def ) {
		static $idCounter = 0;

		//NOTE: we are doing a little dance here, using knowledge about how EntityContent
		//      is stored, but not using EntityContent, because that is part of the repository
		//      extension, not the client. We can not assume EntityContent to be present.
		//      So, we disguise serialized entity data as text.

		$type = $def['type'];

		$entity = \Wikibase\EntityFactory::singleton()->newFromArray( $type, $def );
		$data = $entity->toArray();
		$blob = json_encode( $data );

		$idCounter += 1;
		$id = new EntityId( $type, $idCounter );

		// XXX: hoping we can store text there
		$title = \Title::newFromText( __CLASS__ . '_' . $id->getPrefixedId(), NS_HELP );

		$page = \WikiPage::factory( $title );
		$status = $page->doEditContent( new \WikiTextContent( $blob ),
				"storeTestEntity $idCounter", EDIT_NEW );

		//FIXME: we need to write to wb_entity_per_page here, but that table
		//       doesn't even exist on the client. Ugh.

		if ( !$status->isOK() ) {
			throw new \MWException( "couldn't create " . $title->getFullText() );
		}

		$data['id'] = $idCounter;
		$data['revision'] = $status->value['revision']->getId();
		return $data;
	}

	public static function provideGetEntity() {
		return array(
			array( // #0
				CACHE_NONE,
				'foo'
			),
			array( // #1
				CACHE_DB, //XXX: can't we just configure an in-memory BagOfStuff?
				'foo'
			),
			array( // #2  (try again, should come from cache)
				CACHE_DB,
				'foo'
			),

			//TODO: test with revision IDs (several cases, check the source!)
		);
	}

	/**
	 * @dataProvider provideGetEntity
	 */
	public function testGetEntity( $cacheType, $handle, $revision = false ) {
		//TODO: find a way to test this with an actual foreign database
		$lookup = new WikiPageEntityLookup( false, $cacheType );

		$id = self::getTestEntityId( $handle );

		$entity = $lookup->getEntity( $id );

		// TODO: test fetching specific revisions
		$expectedData = self::getTestEntityData( $handle );
		$this->assertEntityStructureEquals( $expectedData, $entity );
	}
}
