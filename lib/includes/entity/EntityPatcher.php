<?php

namespace Wikibase;
use MWException;
use Diff\MapPatcher;

/**
 * Class for patching an Entity with an EntityDiff.
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
class EntityPatcher {

	/**
	 * @since 0.4
	 *
	 * @param string $entityType
	 *
	 * @return EntityDiffer
	 */
	public static function newForType( $entityType ) {
		if ( $entityType === Item::ENTITY_TYPE ) {
			$class = '\Wikibase\ItemPatcher';
		}
		else {
			$class = __CLASS__;
		}

		return new $class( new MapPatcher() );
	}

	/**
	 * @since 0.4
	 *
	 * @var MapPatcher
	 */
	protected $mapPatcher;

	/**
	 * @since 0.4
	 *
	 * @param boolean $throwErrors
	 */
	public function __construct( MapPatcher $mapPatcher ) {
		$this->mapPatcher = $mapPatcher;
	}

	/**
	 * @since 0.4
	 *
	 * @param Entity $oldEntity
	 * @param EntityDiff $patch
	 *
	 * @return Entity
	 * @throws MWException
	 */
	public final function getPatchedEntity( Entity $entity, EntityDiff $patch ) {
		$entity = $entity->copy();

		$entity->setLabels( $this->mapPatcher->patch( $entity->getLabels(), $patch->getLabelsDiff() ) );
		$entity->setDescriptions( $this->mapPatcher->patch( $entity->getDescriptions(), $patch->getDescriptionsDiff() ) );
		$entity->setAllAliases( $this->mapPatcher->patch( $entity->getAllAliases(), $patch->getAliasesDiff() ) );

		$this->patchSpecificFields( $entity, $patch );

		return $entity;
	}

	/**
	 * @since 0.4
	 *
	 * @param Entity $entity
	 * @param EntityDiff $patch
	 */
	protected function patchSpecificFields( Entity &$entity, EntityDiff $patch ) {
		// No-op, meant to be overridden in deriving classes to add specific behaviour
	}

}