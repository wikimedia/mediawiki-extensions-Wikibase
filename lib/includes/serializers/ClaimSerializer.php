<?php

namespace Wikibase;
use MWException;

/**
 * Serializer for Claim objects.
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
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ClaimSerializer extends SerializerObject implements Unserializer {

	/**
	 * @since 0.3
	 *
	 * @var string[]
	 */
	protected static $rankMap = array(
		Statement::RANK_DEPRECATED => 'deprecated',
		Statement::RANK_NORMAL => 'normal',
		Statement::RANK_PREFERRED => 'preferred',
	);

	/**
	 * Returns the available ranks in serialized form.
	 *
	 * @since 0.3
	 *
	 * @return string[]
	 */
	public static function getRanks() {
		return array_values( self::$rankMap );
	}

	/**
	 * Unserializes the rank and returns an element from the Statement::RANK_ enum.
	 * Roundtrips with @see ClaimSerializer::serializeRank
	 *
	 * @since 0.3
	 *
	 * @param string $serializedRank
	 *
	 * @return integer
	 */
	public static function unserializeRank( $serializedRank ) {
		$ranks = array_flip( self::$rankMap );
		return $ranks[$serializedRank];
	}

	/**
	 * Serializes the rank.
	 * Roundtrips with @see ClaimSerializer::unserializeRank
	 *
	 * @since 0.3
	 *
	 * @param integer $rank
	 *
	 * @return string
	 */
	public static function serializeRank( $rank ) {
		return self::$rankMap[$rank];
	}

	/**
	 * @see ApiSerializer::getSerialized
	 *
	 * @since 0.2
	 *
	 * @param mixed $claim
	 *
	 * @return array
	 * @throws MWException
	 */
	public function getSerialized( $claim ) {
		if ( !( $claim instanceof Claim ) ) {
			throw new MWException( 'ClaimSerializer can only serialize Claim objects' );
		}

		$serialization['id'] = $claim->getGuid();

		$snakSerializer = new SnakSerializer( $this->options );
		$serialization['mainsnak'] = $snakSerializer->getSerialized( $claim->getMainSnak() );

		$snaksSerializer = new ByPropertyListSerializer( 'qualifier', $snakSerializer, $this->options );
		$qualifiers = $snaksSerializer->getSerialized( $claim->getQualifiers() );

		if ( $qualifiers !== array() ) {
			$serialization['qualifiers'] = $qualifiers;
		}

		$serialization['type'] = $claim instanceof Statement ? 'statement' : 'claim';

		if ( $claim instanceof Statement ) {
			$serialization['rank'] = self::$rankMap[ $claim->getRank() ];

			$referenceSerializer = new ReferenceSerializer( $this->options );

			$serialization['references'] = array();

			foreach ( $claim->getReferences() as $reference ) {
				$serialization['references'][] = $referenceSerializer->getSerialized( $reference );
			}

			if ( $serialization['references'] === array() ) {
				unset( $serialization['references'] );
			}
			else {
				$this->setIndexedTagName( $serialization['references'], 'reference' );
			}
		}

		return $serialization;
	}

}