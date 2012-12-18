<?php

namespace Wikibase;
use MWException;

/**
 * Class for patching an Item with an ItemDiff.
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
 * @since 0.4
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ItemPatcher extends EntityPatcher {

	/**
	 * @see EntityPatcher::patchSpecificFields
	 *
	 * @since 0.4
	 *
	 * @param Entity $entity
	 * @param EntityDiff $patch
	 *
	 * @throws MWException
	 */
	protected function patchSpecificFields( Entity &$entity, EntityDiff $patch ) {
		if ( !( $entity instanceof Item ) || !( $patch instanceof ItemDiff ) ) {
			throw new MWException( 'ItemPatcher only deals with Item objects' );
		}

		/**
		 * @var Item $entity
		 * @var ItemDIff $patch
		 */
		$links = SiteLink::siteLinksToArray( $entity->getSiteLinks() );
		$links = $this->mapPatcher->patch( $links, $patch->getSiteLinkDiff() );
		$entity->setSiteLinks( SiteLink::siteLinksFromArray( $links ) );
	}

}