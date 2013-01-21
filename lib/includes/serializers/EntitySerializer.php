<?php

namespace Wikibase;
use ApiResult, MWException;

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
	 * @param EntitySerializationOptions|null $options
	 */
	public function __construct( EntitySerializationOptions $options = null ) {
		if ( $options === null ) {
			$options = new EntitySerializationOptions();
		}

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

		$serialization['id'] = $entity->getPrefixedId();
		$serialization['type'] = $entity->getType();

		foreach ( $this->options->getProps() as $key ) {
			switch ( $key ) {
				case 'aliases':
					$serialization['aliases'] = $this->getAliasesSerialization( $entity );
					break;
				case 'descriptions':
					$serialization['descriptions'] = $this->getDescriptionsSerialization( $entity );
					break;
				case 'labels':
					$serialization['labels'] = $this->getLabelsSerialization( $entity );
					break;
				case 'claims':
					$claimsSerializer = new ClaimsSerializer( $this->options );
					$serialization['claims'] = $claimsSerializer->getSerialized( $entity->getClaims() );
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

	/**
	 * Returns the aliases in an array ready for serialization.
	 *
	 * @since 0.2
	 *
	 * @param Entity $entity
	 *
	 * @return array
	 */
	protected final function getAliasesSerialization( Entity $entity ) {
		$value = array();

		$aliases = $entity->getAllAliases( $this->options->getLanguages() );

		if ( $this->options->shouldUseKeys() ) {
			foreach ( $aliases as $languageCode => $alarr ) {
				$arr = array();
				foreach ( $alarr as $alias ) {
					$arr[] = array(
						'language' => $languageCode,
						'value' => $alias,
					);
				}
				$value[$languageCode] = $arr;
			}
		}
		else {
			foreach ( $aliases as $languageCode => $alarr ) {
				foreach ( $alarr as $alias ) {
					$value[] = array(
						'language' => $languageCode,
						'value' => $alias,
					);
				}
			}
		}

		if ( !$this->options->shouldUseKeys() ) {
			$this->setIndexedTagName( $value, 'alias' );
		}

		return $value;
	}

	/**
	 * Returns the descriptions in an array ready for serialization.
	 *
	 * @since 0.2
	 *
	 * @param Entity $entity
	 *
	 * @return array
	 */
	protected final function getDescriptionsSerialization( Entity $entity ) {
		$value = array();
		$idx = 0;

		$descriptions = $entity->getDescriptions( $this->options->getLanguages() );

		foreach ( $descriptions as $languageCode => $description ) {
			if ( $description === '' ) {
				$value[$this->options->shouldUseKeys() ? $languageCode : $idx++] = array(
					'language' => $languageCode,
					'removed' => '',
				);
			}
			else {
				$value[$this->options->shouldUseKeys() ? $languageCode : $idx++] = array(
					'language' => $languageCode,
					'value' => $description,
				);
			}
		}

		if ( !$this->options->shouldUseKeys() ) {
			$this->setIndexedTagName( $value, 'description' );
		}

		return $value;
	}

	/**
	 * Returns the labels in an array ready for serialization.
	 *
	 * @since 0.2
	 *
	 * @param Entity $entity
	 *
	 * @return array
	 */
	protected final function getLabelsSerialization( Entity $entity ) {
		$value = array();
		$idx = 0;

		$labels = $entity->getLabels( $this->options->getLanguages() );

		foreach ( $labels as $languageCode => $label ) {
			if ( $label === '' ) {
				$value[$this->options->shouldUseKeys() ? $languageCode : $idx++] = array(
					'language' => $languageCode,
					'removed' => '',
				);
			}
			else {
				$value[$this->options->shouldUseKeys() ? $languageCode : $idx++] = array(
					'language' => $languageCode,
					'value' => $label,
				);
			}
		}

		if ( !$this->options->shouldUseKeys() ) {
			$this->setIndexedTagName( $value, 'label' );
		}

		return $value;
	}

	/**
	 * Returns an EntitySerializer for the provided entity.
	 * If there is no specific serializer registered for the type of entity,
	 * a plain EntitySerializer is returned, else the more specific one is.
	 *
	 * @since 0.3
	 *
	 * @param Entity $entity
	 * @param EntitySerializationOptions $options
	 *
	 * @return EntitySerializer
	 */
	public static function newForEntity( Entity $entity, EntitySerializationOptions $options = null ) {
		$serializers = array(
			'item' => '\Wikibase\ItemSerializer',
			'property' => '\Wikibase\PropertySerializer',
		);

		$serializer = array_key_exists( $entity->getType(), $serializers ) ? $serializers[$entity->getType()] : '\Wikibase\EntitySerializer';

		return new $serializer( $options );
	}

}
