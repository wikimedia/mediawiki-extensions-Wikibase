<?php

namespace Wikibase\Lib\Serializers;

use ApiResult;
use MWException;
use Wikibase\Entity;
use Wikibase\Repo\WikibaseRepo;

/**
 * Serializer for entities.
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
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class EntitySerializer extends SerializerObject {

	/**
	 * @see ApiSerializerObject::$options
	 *
	 * @since 0.2
	 *
	 * @var EntitySerializationOptions
	 */
	protected $options;

	/**
	 * Constructor.
	 *
	 * @since 0.2
	 *
	 * @param EntitySerializationOptions $options
	 */
	public function __construct( EntitySerializationOptions $options ) {
		parent::__construct( $options );
	}

	/**
	 * @see ApiSerializer::getSerialized
	 *
	 * @since 0.2
	 *
	 * @param mixed $entity
	 *
	 * @return array
	 * @throws MWException
	 */
	public final function getSerialized( $entity ) {
		if ( !( $entity instanceof Entity ) ) {
			throw new MWException( 'EntitySerializer can only serialize Entity objects' );
		}

		$serialization['id'] = $this->options->getIdFormatter()->format( $entity->getId() );
		$serialization['type'] = $entity->getType();

		foreach ( $this->options->getProps() as $key ) {
			switch ( $key ) {
				case 'aliases':
					$aliasSerializer = new AliasSerializer( $this->options );
					$aliases = $entity->getAllAliases( $this->options->getLanguages() );
					$serialization['aliases'] = $aliasSerializer->getSerialized( $aliases );
					break;
				case 'descriptions':
					$descriptionSerializer = new DescriptionSerializer( $this->options );
					$descriptions = $entity->getDescriptions( $this->options->getLanguages() );
					$serialization['descriptions'] = $descriptionSerializer->getSerialized( $descriptions );
					break;
				case 'labels':
					$labelSerializer = new LabelSerializer( $this->options );
					$labels = $entity->getLabels( $this->options->getLanguages() );
					$serialization['labels'] = $labelSerializer->getSerialized( $labels );
					break;
				case 'claims':
					$claimsSerializer = new ClaimsSerializer( $this->options );
					$serialization['claims'] = $claimsSerializer->getSerialized( new \Wikibase\Claims( $entity->getClaims() ) );
					break;
			}
		}

		$serialization = array_merge( $serialization, $this->getEntityTypeSpecificSerialization( $entity ) );

		// Omit empty arrays from the result
		$serialization = array_filter(
			$serialization,
			function( $value ) {
				return $value !== array();
			}
		);

		return $serialization;
	}

	/**
	 * Extension point for subclasses.
	 *
	 * @since 0.2
	 *
	 * @param Entity $entity
	 *
	 * @return array
	 */
	protected function getEntityTypeSpecificSerialization( Entity $entity ) {
		// Stub, override expected
		return array();
	}
}
