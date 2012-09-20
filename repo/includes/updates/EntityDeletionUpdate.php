<?php

namespace Wikibase;

/**
 * Deletion update to handle deletion of Wikibase entities.
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
class EntityDeletionUpdate extends \DataUpdate {

	/**
	 * @since 0.1
	 *
	 * @var EntityContent
	 */
	protected $content;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param EntityContent $content
	 */
	public function __construct( EntityContent $content ) {
		$this->content = $content;
	}

	/**
	 * Returns the EntityContent that's being deleted.
	 *
	 * @since 0.1
	 *
	 * @return EntityContent
	 */
	public function getEntityContent() {
		return $this->content;
	}

	/**
	 * @see DeferrableUpdate::doUpdate
	 *
	 * @since 0.1
	 */
	public final function doUpdate() {
		wfProfileIn( __METHOD__ );

		$store = StoreFactory::getStore();
		$entity = $this->content->getEntity();

		$store->newTermCache()->deleteTermsOfEntity( $entity );
		$this->doTypeSpecificStuff( $store, $entity );

		/**
		 * Gets called after the deletion of an item has been committed,
		 * allowing for extensions to do additional cleanup.
		 *
		 * @since 0.1
		 *
		 * @param ItemStructuredSave $this
		 */
		wfRunHooks( 'WikibaseEntityDeletionUpdate', array( $this ) );

		wfProfileOut( __METHOD__ );
	}

	/**
	 * Do anything specific to the entity type.
	 *
	 * @since 0.1
	 *
	 * @param Store $store
	 * @param Entity $entity
	 */
	protected function doTypeSpecificStuff( Store $store, Entity $entity ) {
		// Override to add behaviour.
	}

}
