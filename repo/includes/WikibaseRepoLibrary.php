<?php

/**
 * Registers and defines functions to access Wikibase through the Scribunto extension
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
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */

use Wikibase\EntityContentFactory;

class Scribunto_LuaWikibaseRepoLibrary extends Scribunto_LuaWikibaseLibrary {

	/**
	 * @see Scribunto_LuaWikibaseLibrary::getAndRegisterEntity
	 */
	protected function getAndRegisterEntity( $entityId ) {
		$entityContent = EntityContentFactory::singleton()->getFromId( $entityId );
		if ( $entityContent === null ) {
			return array( null );
		}

		$entityTitle = $entityContent->getTitle();

		// Record in templatelinks, so edits cause the page to be refreshed
		if ( $entityTitle !== false ) {
			$this->getParser()->getOutput()->addTemplate(
				$entityTitle, $entityTitle->getArticleID(), $entityTitle->getLatestRevID()
			);
		}

		return $entityContent->getEntity();
	}
}