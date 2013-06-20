<?php

namespace Wikibase\Lib\Serializers;
use MWException;
use Wikibase\Entity;
use Wikibase\Item;

/**
 * Serializer for items.
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
 * @since 0.2
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class ItemSerializer extends EntitySerializer {

	/**
	 * @see EntitySerializer::getEntityTypeSpecificSerialization
	 *
	 * @since 0.2
	 *
	 * @param Entity $item
	 *
	 * @return array
	 * @throws MWException
	 */
	protected function getEntityTypeSpecificSerialization( Entity $item ) {
		if ( !( $item instanceof Item ) ) {
			throw new MWException( 'ItemSerializer can only serialize Item implementing objects' );
		}

		$serialization = array();

		if ( in_array( 'sitelinks', $this->options->getProps() ) ) {
			$siteStore = \SiteSQLStore::newInstance();
			$siteLinkSerializer = new SiteLinkSerializer( $this->options, $siteStore );
			$siteLinks = $item->getSimpleSiteLinks();
			$serialization['sitelinks'] = $siteLinkSerializer->getSerialized( $siteLinks );
		}

		return $serialization;
	}
}
