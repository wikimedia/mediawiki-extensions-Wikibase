<?php

namespace Wikibase;
use MWException;

/**
 * API serializer for Claim objects.
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
class ClaimSerializer extends ApiSerializerObject {

	/**
	 * Constructor.
	 *
	 * @since 0.2
	 *
	 * @param \ApiResult $apiResult
	 * @param EntitySerializationOptions|null $options
	 */
	public function __construct( \ApiResult $apiResult, EntitySerializationOptions $options = null ) {
		if ( $options === null ) {
			$options = new EntitySerializationOptions();
		}

		parent::__construct( $apiResult, $options );
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

		$serialization['type'] = ( $claim instanceof Statement ) ? 'statement' : 'claim';

		$snakSerializer = new SnakSerializer( $this->getResult(), $this->options );
		$serialization['mainsnak'] = $snakSerializer->getSerialized( $claim->getMainSnak() );

		if ( isset( $this->options ) && in_array( 'qualifiers', $this->options->getProps() ) ) {
			$snaksSerializer = new ByPropertyListSerializer( 'qualifier', $snakSerializer, $this->getResult(), $this->options );
			$qualifiers = $snaksSerializer->getSerialized( $claim->getQualifiers() );
			if ($qualifiers !== false ) {
				$serialization['qualifiers'] = $qualifiers;
			}
		}

		if ( $claim instanceof Statement ) {
			$serialization['rank'] = $claim->getRank();

			if ( isset( $this->options ) && in_array( 'references', $this->options->getProps() ) ) {
				$snaksSerializer = new ByPropertyListSerializer( 'reference', $snakSerializer, $this->getResult(), $this->options );
				$references = $snaksSerializer->getSerialized( $claim->getReferences() );
				if ( $references !== false ) {
					$serialization['references'] = $references;
				}
			}
		}

		return $serialization;
	}

}