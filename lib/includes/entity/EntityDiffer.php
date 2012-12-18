<?php

namespace Wikibase;
use MWException;

/**
 * Class for diffing two Entity objects to an EntityDiff.
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
class EntityDiffer extends \Diff\MapDiffer {

	/**
	 * @since 0.4
	 *
	 * @param string $entityType
	 *
	 * @return EntityDiffer
	 */
	public static function newForType( $entityType ) {
		if ( $entityType === Item::ENTITY_TYPE ) {
			$class = '\Wikibase\ItemDiffer';
		}
		else {
			$class = __CLASS__;
		}

		return new $class( true );
	}

	/**
	 * @since 0.4
	 *
	 * @param Entity $oldEntity
	 * @param Entity $newEntity
	 *
	 * @return EntityDiff
	 * @throws MWException
	 */
	public final function diffEntities( Entity $oldEntity, Entity $newEntity ) {
		if ( $oldEntity->getType() !== $newEntity->getType() ) {
			throw new MWException( 'Can only diff between entities of the same type' );
		}

		$entityType = $oldEntity->getType();

		$oldEntity = $this->entityToArray( $oldEntity );
		$newEntity = $this->entityToArray( $newEntity );

		$diffOps = $this->doDiff( $oldEntity, $newEntity );
		$diff = EntityDiff::newForType( $entityType, $diffOps );

		return $diff;
	}

	/**
	 * @since 0.4
	 *
	 * @param Entity $entity
	 *
	 * @return array
	 */
	protected function entityToArray( Entity $entity ) {
		$array = array();

		$array['aliases'] = $entity->getAllAliases();
		$array['label'] = $entity->getLabels();
		$array['description'] = $entity->getDescriptions();

		// TODO: claims

		return $array;
	}

}