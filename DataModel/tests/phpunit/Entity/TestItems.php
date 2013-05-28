<?php

namespace Wikibase\Test;

use Wikibase\Item;
use Wikibase\SiteLink;

/**
 * Holds Item objects for testing proposes.
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
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
final class TestItems {

	/**
	 * @since 0.1
	 * @return Item[]
	 */
	public static function getItems() {
		$items = array();

		//$items[] = Item::newEmpty();

		$item = Item::newEmpty();

		$item->setDescription( 'en', 'foo' );
		$item->setLabel( 'en', 'bar' );

		//$items[] = $item;

		$item = Item::newEmpty();

		$item->addAliases( 'en', array( 'foobar', 'baz' ) );

		//$items[] = $item;

		$item = Item::newEmpty();

		$groups = \Wikibase\Settings::get( 'siteLinkGroups' );

		foreach ( $groups as $group ) {
			$sites = \SiteSQLStore::newInstance()->getSites()->getGroup( $group );

			if ( count( $sites ) > 1 ) {
				$item->addSiteLink( new SiteLink( $sites->getIterator()->current(), 'spam' ) );
			}
		}

		//$items[] = $item;

		$item = Item::newEmpty();

		if ( count( $sites ) > 1 ) {
			$linksIterator = $sites->getIterator();

			$item->addSiteLink( new SiteLink( $linksIterator->current(), 'spamz' ) );
			$linksIterator->next();
			$item->addSiteLink( new SiteLink( $linksIterator->current(), 'foobar' ) );
		}

		$item->setDescription( 'en', 'foo' );
		$item->setLabel( 'en', 'bar' );

		$item->addAliases( 'en', array( 'foobar', 'baz' ) );
		$item->addAliases( 'de', array( 'foobar', 'spam' ) );

		$items[] = $item;

		return $items;
	}

}