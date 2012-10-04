<?php

namespace Wikibase;

/**
 * Represents the creation of an entity.
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
class EntityRefresh extends DiffChange {

	/**
	 * @since 0.1
	 *
	 * @param Entity $entity
	 */
	public function setEntity( Entity $entity ) {
		$info = $this->hasField( 'info' ) ? $this->getField( 'info' ) : array();
		$info['entity'] = $entity;
		$this->setField( 'info', $info );
	}

	/**
	 * @since 0.1
	 *
	 * @param Entity $entity
	 * @param array|null $fields
	 *
	 * @return EntityRefresh
	 */
	public static function newFromEntity( Entity $entity, array $fields = null ) {
		$instance = new static(
			ChangesTable::singleton(),
			$fields,
			true
		);

		$instance->setEntity( $entity );
		$instance->setField( 'type', $instance->getType() );

		return $instance;
	}

	/**
	 * @see Change::getChangeType
	 *
	 * @since 0.2
	 *
	 * @return string
	 */
	public function getChangeType() {
		return 'refresh';
	}

	/**
	 * @see ChangeRow::postConstruct
	 *
	 * @since 0.1
	 */
	protected function postConstruct() {
	}

}
