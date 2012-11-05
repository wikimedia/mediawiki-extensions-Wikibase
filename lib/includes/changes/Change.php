<?php

namespace Wikibase;

/**
 * Interface for objects representing changes.
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
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

interface Change {

	/**
	 * Returns the user that made the change.
	 *
	 * @since 0.1
	 *
	 * @return \User
	 */
	public function getUser();

	/**
	 * Returns the age of the change in seconds.
	 *
	 * @since 0.1
	 *
	 * @return integer
	 */
	public function getAge();

	/**
	 * Returns whether the change is empty.
	 * If it's empty, it can be ignored.
	 *
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public function isEmpty();

	/**
	 * Returns the type of change.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getType();

	/**
	 * Returns the entity type for a change.
	 *
	 * @since 0.2
	 *
	 * @return string
	 */
	public function getEntityType();

	/**
	 * Returns the change type
	 *
	 * @since 0.2
	 *
	 * @return string
	 */
	public function getChangeType();

	/**
	 * Returns the time on which the change was made as a timestamp in TS_MW format.
	 *
	 * @since 0.2
	 *
	 * @return string TS_MW
	 */
	public function getTime();

	/**
	 * Returns the id of the change.
	 *
	 * @since 0.2
	 *
	 * @return integer
	 */
	public function getId();

	/**
	 * Returns the id of the affected object (ie item or property).
	 *
	 * @since 0.2
	 *
	 * @return integer
	 */
	public function getObjectId();

}
