<?php

namespace Wikibase\Test;
use \Wikibase\ItemObject;

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

	protected function getItem() {
		$item = ItemObject::newEmpty();
		$item->setLabel( 'en', 'Venezuela' );
		$item->setDescription( 'en', 'a country' );
		$item->addAliases( 'en', array( 'Bolivarian Republic of Venezuela' ) );

		$group = \Wikibase\Settings::get( 'siteLinkGroup' );
		$sites = \Sites::singleton()->getSiteGroup( $group );

		if ( count( $sites ) > 1 ) {
			$item->addSiteLink( \Wikibase\SiteLink::newFromText( 'enwiki', 'Venezuela' ) );
			$item->addSiteLink( \Wikibase\SiteLink::newFromText( 'jawiki', 'ベネズエラ' ) );
			$item->addSiteLink( \Wikibase\SiteLink::newFromText( 'cawiki', 'Veneçuela' ) );
		}

		return $item;
	}

	public static function getChange() {
		return array(
			'type' => 'wikibase-item~add',
			'time' => '20120515104713',
			'object_id' => 182,
			'revision_id' => 452,
			'user_id' => 0,
			'info' => array(
				'entity' => self::getItem(),
				'rc' => array(
					'rc_user' => 0,
					'rc_user_text' => '208.80.152.201'
				)
			 )
		);
	}
}
