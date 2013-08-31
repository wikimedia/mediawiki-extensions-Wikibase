<?php

namespace Wikibase\Lib\Serializers;
use ApiResult, MWException;
use Wikibase\Repo\WikibaseRepo;

/**
 * Serializer for Traversable objects that need to be grouped
 * per property id. Each element needs to have a getPropertyId method.
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
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ByPropertyListUnserializer implements Unserializer {

	/**
	 * @since 0.2
	 *
	 * @var Serializer
	 */
	protected $elementUnserializer;

	/**
	 * Constructor.
	 *
	 * @since 0.2
	 *
	 * @param Unserializer $elementUnserializer
	 */
	public function __construct( Unserializer $elementUnserializer ) {
		$this->elementUnserializer = $elementUnserializer;
	}

	/**
	 * @see Unserializer::newFromSerialization
	 *
	 * @since 0.4
	 *
	 * @param array $serialization
	 *
	 * @return array
	 * @throws MWException
	 */
	public function newFromSerialization( array $serialization ) {
		$elements = array();

		foreach ( $serialization as $propertyId => $byPropId ) {
			if ( !is_array( $byPropId ) ) {
				throw new MWException( "Element with key '$propertyId' should be an array, found " . gettype( $byPropId ) );
			}
			$parsedProperty = WikibaseRepo::getDefaultInstance()->getEntityIdParser()->parse( $propertyId );
			$parsedPropertyId = $parsedProperty->getPrefixedId();

			foreach ( $byPropId as $serializedElement ) {
				$element = $this->elementUnserializer->newFromSerialization( $serializedElement );
				$elementPropertyId = $element->getPropertyId()->getPrefixedId();

				if ( !$element->getPropertyId()->equals( $parsedProperty ) ) {
					throw new MWException( "Element with id '$elementPropertyId' found in list with id '$parsedPropertyId'" );
				}

				$elements[] = $element;
			}
		}

		return $elements;
	}

}
