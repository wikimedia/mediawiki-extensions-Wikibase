<?php

namespace Wikibase\Test;
use Wikibase\ChangesTable;

/**
 * Tests for the Wikibase\ClientHooks class.
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
 * @since 0.2
 *
 * @ingroup WikibaseClient
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ClientHooksTest extends \MediaWikiTestCase {

	public function updateProvider() {

		$changeData = array(
			'type' => 'wikibase-item~update',
			'time' => '20121101134948',
  			'object_id' => 44,
			'revision_id' => 107,
    		'user_id' => 0,
			'info' => 'a:3:{s:6:"entity";O:19:"Wikibase\ItemObject":3:{s:13:" * statements";N;s:7:" * data";a:6:{s:5:"label";a:1:{s:2:"en";s:6:"Cookie";}s:11:"description";a:0:{}s:7:"aliases";a:0:{}s:5:"links";a:2:{s:6:"enwiki";s:6:"Cookie";s:6:"dewiki";s:6:"Cookie";}s:10:"statements";a:0:{}s:6:"entity";s:3:"q44";}s:5:" * id";i:44;}s:4:"diff";C:17:"Wikibase\ItemDiff":1262:{a:4:{s:4:"data";a:4:{s:7:"aliases";C:12:"Diff\MapDiff":187:{a:4:{s:4:"data";a:0:{}s:5:"index";i:0;s:12:"typePointers";a:6:{s:3:"add";a:0:{}s:6:"remove";a:0:{}s:6:"change";a:0:{}s:4:"list";a:0:{}s:3:"map";a:0:{}s:4:"diff";a:0:{}}s:9:"parentKey";N;}}s:5:"label";C:12:"Diff\MapDiff":280:{a:4:{s:4:"data";a:1:{s:2:"en";C:17:"Diff\DiffOpChange":41:{a:2:{i:0;s:6:"Cookie";i:1;s:7:"Cookies";}}}s:5:"index";i:0;s:12:"typePointers";a:6:{s:3:"add";a:0:{}s:6:"remove";a:0:{}s:6:"change";a:1:{i:0;s:2:"en";}s:4:"list";a:0:{}s:3:"map";a:0:{}s:4:"diff";a:0:{}}s:9:"parentKey";N;}}s:11:"description";C:12:"Diff\MapDiff":187:{a:4:{s:4:"data";a:0:{}s:5:"index";i:0;s:12:"typePointers";a:6:{s:3:"add";a:0:{}s:6:"remove";a:0:{}s:6:"change";a:0:{}s:4:"list";a:0:{}s:3:"map";a:0:{}s:4:"diff";a:0:{}}s:9:"parentKey";N;}}s:5:"links";C:12:"Diff\MapDiff":187:{a:4:{s:4:"data";a:0:{}s:5:"index";i:0;s:12:"typePointers";a:6:{s:3:"add";a:0:{}s:6:"remove";a:0:{}s:6:"change";a:0:{}s:4:"list";a:0:{}s:3:"map";a:0:{}s:4:"diff";a:0:{}}s:9:"parentKey";N;}}}s:5:"index";i:0;s:12:"typePointers";a:6:{s:3:"add";a:0:{}s:6:"remove";a:0:{}s:6:"change";a:0:{}s:4:"list";a:0:{}s:3:"map";a:4:{i:0;s:7:"aliases";i:1;s:5:"label";i:2;s:11:"description";i:3;s:5:"links";}s:4:"diff";a:0:{}}s:9:"parentKey";N;}}s:2:"rc";a:5:{s:12:"rc_user_text";s:12:"93.220.76.69";s:8:"rc_curid";i:221;s:13:"rc_this_oldid";i:107;s:13:"rc_last_oldid";i:106;s:10:"rc_comment";s:32:"/* wbsetlabel-set:1|en */ Cookie";}}'
		);

		$changesTable = ChangesTable::singleton();
		$change = $changesTable->newRow( $changeData, false );

		$data = array();
		$data[] = array( $change );

		return $data;
	}

	/**
	 * @dataProvider updateProvider
	 */
	public function testGetEntity( $change ) {
		$entity = $change->getEntity();
		var_export( $entity );
		$this->assertInstanceOf( '\Wikibase\ItemObject', $entity );
	}

	/**
	 * @dataProvider updateProvider
	 */
	public function testCachedEntity( $change ) {
		$entityCacheTable = \Wikibase\EntityCacheTable::singleton();
		$entity = $change->getEntity();
		$cachedEntity = $entityCacheTable->newRow( array(
			'entity_id' => $entity->getId(),
			'entity_type' => $entity->getType(),
			'entity_data' => $entity,
		) );
		$this->assertTrue( $entityCacheTable->updateEntity( $entity ) );
	}

	/**
	 * @dataProvider updateProvider
	 */
	public function getEntityId( $change ) {
		$entity = $change->getEntity();
		$id = $entity->getId();
		$this->assertEquals( 112, $id );
	}

	/**
	 * @dataProvider updateProvider
	 */
	public function testEntityUpdate( $change ) {
		$this->assertInstanceOf( '\Wikibase\EntityUpdate', $change );
	}

	/**
	 * @dataProvider updateProvider
	 */
	public function testDisableInjectChanges( $change ) {
		\Wikibase\ClientHooks::onWikibasePollHandle( $change );
/*		$rc = $this->db->select(
			'recentchanges',
			'*'
		);
		var_export( count( $rc ) );
*/
	}
}
