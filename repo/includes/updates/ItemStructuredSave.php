<?php

namespace Wikibase;
use Title;

/**
 * Represents an update to the structured storage for a single WikibaseItem.
 * TODO: we could keep track of actual changes in a lot of cases, and so be able to do less (expensive) queries to update.
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
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ItemStructuredSave extends \DataUpdate {

	/**
	 * The item to update.
	 *
	 * @since 0.1
	 * @var ItemContent
	 */
	protected $itemContent;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param ItemContent $itemContent
	 */
	public function __construct( ItemContent $itemContent ) {
		$this->itemContent = $itemContent;
	}

	/**
	 * Returns the ItemContent that's being saved.
	 *
	 * @since 0.1
	 *
	 * @return ItemContent
	 */
	public function getItemContent() {
		return $this->itemContent;
	}

	/**
	 * Perform the actual update.
	 *
	 * @since 0.1
	 */
	public function doUpdate() {
		wfProfileIn( __METHOD__ );

		$store = StoreFactory::getStore();
		$item = $this->itemContent->getItem();

		$store->newTermCache()->saveTermsOfEntity( $item );
		$store->newSiteLinkCache()->saveLinksOfItem( $item );

		/**
		 * Gets called after the structured save of an item has been comitted,
		 * allowing for extensions to do additional storage/indexing.
		 *
		 * @since 0.1
		 *
		 * @param ItemStructuredSave $this
		 */
		wfRunHooks( 'WikibaseItemStructuredSave', array( $this ) );

		wfProfileOut( __METHOD__ );
	}

}
