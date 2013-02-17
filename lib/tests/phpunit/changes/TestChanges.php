<?php

namespace Wikibase\Test;
use \Wikibase\EntityChange;
use \Wikibase\EntityId;
use \Wikibase\Item;
use \Wikibase\SiteLink;

/**
 * Test change data for ChangeRowTest
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
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseChange
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
final class TestChanges {

	public function getItem() {
		$item = Item::newEmpty();
		$item->setId( new EntityId( Item::ENTITY_TYPE, 102 ) );
		$item->setLabel( 'en', 'Venezuela' );
		$item->setDescription( 'en', 'a country' );
		$item->addAliases( 'en', array( 'Bolivarian Republic of Venezuela' ) );

		$siteLinks = array(
			SiteLink::newFromText( 'enwiki', 'Venezuela' ),
			SiteLink::newFromText( 'jawiki', 'ベネズエラ' ),
			SiteLink::newFromText( 'cawiki', 'Veneçuela' )
		);

		foreach( $siteLinks as $siteLink ) {
			$item->addSiteLink( $siteLink );
		}

		return $item;
	}

	public function getSiteLinkChanges() {
		$changes = array();

		$oldItem = $this->getItem();
		$newItem = $oldItem->copy();
		$newItem->addSiteLink( SiteLink::newFromText( 'afwiki', 'Venezuela' ) );

		$changes[] = EntityChange::newFromUpdate( EntityChange::UPDATE, $oldItem, $newItem );

		$oldItem = $this->getItem();
		$newItem = $oldItem->copy();
		$newItem->removeSiteLink( 'cawiki' );

		$changes[] = EntityChange::newFromUpdate( EntityChange::UPDATE, $oldItem, $newItem );

		return $changes;
	}

	public static function getChange() {
		$id = new \Wikibase\EntityId( \Wikibase\Item::ENTITY_TYPE, 182 );

		return array(
			'type' => 'wikibase-item~add',
			'time' => '20120515104713',
			'object_id' => $id->getPrefixedId(),
			'revision_id' => 452,
			'user_id' => 0,
			'info' => array(
				'entity' => self::getItem(),
				'metadata' => array(
					'rc_user' => 0,
					'rc_user_text' => '208.80.152.201'
				)
			 )
		);
	}

    public static function getEntities() {
		$entityList = array();

		$entities = array(
			Item::newEmpty(),
			\Wikibase\Property::newEmpty(),
		);

		/**
		 * @var \Wikibase\Entity $entity
		 */
		foreach( $entities as $entity ) {
			$entityList[] = $entity;

			$entity->setId( 112 );
			$entity->stub();
			$entity->setLabel( 'ja', '\u30d3\u30fc\u30eb' );

			$entityList[] = $entity;
		}


		return $entityList;
	}
}
