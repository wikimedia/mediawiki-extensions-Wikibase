<?php

namespace Wikibase;
use MWException;

/**
 * API serializer for Snaks objects.
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
 * @since 0.2
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SnaksSerializer extends ApiSerializerObject {

	/**
	 * @see ApiSerializer::getSerialized
	 *
	 * @since 0.2
	 *
	 * @param mixed $snaks
	 *
	 * @return array
	 * @throws MWException
	 */
	public function getSerialized( $snaks ) {
		if ( !( $snaks instanceof Snaks ) ) {
			throw new MWException( 'SnaksSerializer can only serialize Snaks objects' );
		}

		$serialization = array();

		$props = array(); // TODO

		$snakSerializer = new SnakSerializer( $this->getResult() );

		foreach ( $props as $prop ) {
			$serializedSnaks = array();

			foreach ( $snaks as $snak ) {
				$serializedSnaks[] = $snakSerializer->getSerialized( $snak );
			}

			$this->getResult()->setIndexedTagName( $serializedSnaks, 'snak' );

			$serialization[42 /* TODO propid */] = $serializedSnaks;
		}

		return $serialization;
	}

}