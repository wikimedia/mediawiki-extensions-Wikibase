<?php

namespace Wikibase;

/**
 * Class representing an update to an entity.
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
class EntityUpdate extends DiffChange {

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
	 * @param Entity $oldEntity
	 * @param Entity $newEntity
	 *
	 * @return EntityUpdate
	 * @throws \MWException
	 */
	public static function newFromEntities( Entity $oldEntity, Entity $newEntity ) {
		$type = $oldEntity->getType();

		if ( $type !== $newEntity->getType() ) {
			throw new \MWException( 'Entity type mismatch' );
		}

		$typeMap = array(
			Item::ENTITY_TYPE => '\Wikibase\EntityUpdate',
			Property::ENTITY_TYPE => '\Wikibase\EntityUpdate',
			Query::ENTITY_TYPE => '\Wikibase\EntityUpdate',
		);

		/**
		 * @var EntityUpdate $instance
		 */
		$instance = new $typeMap[$type](
			ChangesTable::singleton(),
			array(),
			true
		);

		$instance->setEntity( $newEntity );
		$instance->setField( 'type', $instance->getType() );
		$instance->setDiff( $oldEntity->getDiff( $newEntity ) );

		return $instance;
	}

	/**
	 * @see ChangeRow::postConstruct
	 *
	 * @since 0.1
	 */
	protected function postConstruct() {
	}

	/**
	 * @see Change::getChangeType
	 *
	 * @since 0.2
	 *
	 * @return string
	 */
	public final function getChangeType() {
		return 'update';
	}

}
