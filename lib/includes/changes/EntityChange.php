<?php

namespace Wikibase;

/**
 * Represents a change for an entity; to be extended by various change subtypes
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
 * @since 0.3
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class EntityChange extends DiffChange {

	/**
	 * @since 0.3
	 *
	 * @return Entity
	 * @throws \MWException
	 */
	public function getEntity() {
		$info = $this->getField( 'info' );

		if ( !array_key_exists( 'entity', $info ) ) {
			throw new \MWException( 'Cannot get the entity when it has not been set yet.' );
		}

		return $info['entity'];
	}

	/**
	 * @since 0.3
	 *
	 * @param Entity $entity
	 */
	public function setEntity( Entity $entity ) {
		$info = $this->hasField( 'info' ) ? $this->getField( 'info' ) : array();
		$info['entity'] = $entity;
		$this->setField( 'info', $info );
	}

	/**
	 * Returns whether the entity in the change is empty.
	 * If it's empty, it can be ignored.
	 *
	 * @since 0.3
	 *
	 * @return bool
	 */
	public function isEmpty() {
		if ( $this->hasField( 'info' ) ) {
			$info = $this->getField( 'info' );

			if ( array_key_exists( 'entity', $info ) && !$info['entity']->isEmpty() ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @since 0.3
	 *
	 * @param Entity $entity
	 * @param array|null $fields
	 *
	 * @return EntityChange
	 */
	public static function newFromEntity( Entity $entity, array $fields = null ) {
		$instance = new static(
			ChangesTable::singleton(),
			$fields,
			true
		);

		if ( !$instance->hasField( 'info' ) ) {
			$info = array();
			$instance->setField( 'info', $info );
		}

		$info = $instance->getField( 'info' );
		if ( !array_key_exists( 'entity', $info ) ) {
			$instance->setEntity( $entity );
		}

		$type = 'wikibase-' . $entity->getType() . '~' . $instance->getChangeType();
		$instance->setField( 'type', $type );

		return $instance;
	}

	/**
	 * @since 0.3
	 *
	 * @return string
	 */
	public function getType() {
		return $this->getEntityType() . '~' . $this->getChangeType();
	}

	/**
	 * @since 0.3
	 *
	 * @return string
	 */
	public function getEntityType() {
		$entity = $this->getEntity();
		if ( $entity !== null ) {
			return $entity->getType();
		}
		return null;
	}

	/**
	 * @see Change::getChangeType
	 *
	 * @since 0.3
	 *
	 * @return string
	 */
	public function getChangeType() {
		return 'change';
	}

	/**
	 * @since 0.3
	 *
	 * @return array|bool
	 */
	public function getMetadata() {
		$info = $this->hasField( 'info' ) ? $this->getField( 'info' ) : array();
		if ( ( is_array( $info ) ) && ( array_key_exists( 'metadata', $info ) ) ) {
			return $info['metadata'];
		}

		return false;
	}

	/**
	 * @since 0.3
	 *
	 * @param array $rc
	 */
	public function setMetadata( array $metadata ) {
		$info = $this->hasField( 'info' ) ? $this->getField( 'info' ) : array();
		$validKeys = array(
			'rc_comment',
			'rc_curid',
			'rc_bot',
			'rc_this_oldid',
			'rc_last_oldid',
			'rc_user',
			'rc_user_text'
		);

		if ( is_array( $metadata ) ) {
			foreach ( array_keys( $metadata ) as $key ) {
				if ( !in_array( $key, $validKeys ) ) {
					unset( $metadata[$key] );
				}
			}
			$info['metadata'] = $metadata;
			$this->setField( 'info', $info );
		}

		return false;
	}

	/**
	 * @since 0.1
	 */
	protected function postConstruct() {

	}
}
