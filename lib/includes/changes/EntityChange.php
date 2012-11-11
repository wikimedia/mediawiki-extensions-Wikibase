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
	public function hasEntity() {
		if ( $this->hasField( 'info' ) ) {
			$info = $this->getField( 'info' );

			if ( array_key_exists( 'entity', $info ) && !$info['entity']->isEmpty() ) {
				return false;
			}
		}

		return true;
	}

    /**
     * @see Change::getType
     *
     * @since 0.3
     *
     * @param bool $withPrefix Optionally include prefix, such as 'wikibase-'
     *
     * @return string
     */
    public function getType( $withPrefix = true ) {
        $changeType = $this->getChangeType();
		return $this->getEntityType( $withPrefix ) . '~' . $changeType;
	}

	/**
	 * @since 0.3
	 *
	 * @param bool $withPrefix Optionally include prefix, such as 'wikibase-'
	 *
	 * @return string
	 */
	public function getEntityType( $withPrefix = true ) {
		if ( $withPrefix ) {
			return 'wikibase-' . $this->getEntity()->getType();
		}
		return $this->getEntity()->getType();
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
}
