<?php

namespace Wikibase\Lib\Serializers;

use Traversable;
use InvalidArgumentException;
use Wikibase\ByPropertyIdArray;

/**
 * Serializer for Traversable objects that need to be grouped
 * per property id. Each element needs to have a getPropertyId method.
 *
 * @since 0.2
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class ByPropertyListSerializer extends SerializerObject {

	/**
	 * @since 0.2
	 *
	 * @var string
	 */
	protected $elementName;

	/**
	 * @since 0.2
	 *
	 * @var Serializer
	 */
	protected $elementSerializer;

	/**
	 * @var ByRankSorter|null
	 */
	protected $sorter = null;

	/**
	 * Constructor.
	 *
	 * @since 0.2
	 *
	 * @param string $elementName
	 * @param Serializer $elementSerializer
	 * @param SerializationOptions|null $options
	 */
	public function __construct( $elementName, Serializer $elementSerializer, SerializationOptions $options = null ) {
		parent::__construct( $options );

		$this->elementName = $elementName;
		$this->elementSerializer = $elementSerializer;

		if ( $this->options->hasOption( SerializationOptions::OPT_SORT_BY_RANK )
			&& $this->options->getOption( SerializationOptions::OPT_SORT_BY_RANK ) ) {
			$this->sorter = new ByRankSorter();
		}
	}

	/**
	 * @see ApiSerializer::getSerialized
	 *
	 * @since 0.2
	 *
	 * @param mixed $objects
	 *
	 * @return array
	 * @throws InvalidArgumentException
	 */
	public function getSerialized( $objects ) {
		if ( !( $objects instanceof Traversable ) ) {
			throw new InvalidArgumentException( 'ByPropertyListSerializer can only serialize Traversable objects' );
		}

		//NOTE: when changing the serialization structure, update docs/json.wiki too!

		$serialization = array();

		// FIXME: "iterator => array => iterator" is stupid
		$objects = new ByPropertyIdArray( iterator_to_array( $objects ) );
		$objects->buildIndex();

		foreach ( $objects->getPropertyIds() as $propertyId ) {
			$objectsForId = $objects->getByPropertyId( $propertyId );

			if ( $this->sorter ) {
				$objectsForId = $this->sorter->sort( $objectsForId );
			}

			$serializedObjects = array();
			foreach ( $objectsForId as $object ) {
				$serializedObjects[] = $this->elementSerializer->getSerialized( $object );
			}

			$this->setIndexedTagName( $serializedObjects, $this->elementName );

			if ( $this->options->shouldIndexTags() ) {
				$serializedObjects['id'] = $propertyId->getPrefixedId();
				$serialization[] = $serializedObjects;
			}
			else {
				$key = $propertyId->getPrefixedId();

				if ( $this->getOptions()->shouldUseUpperCaseIdsAsKeys() ) {
					$key = strtoupper( $key );
					$serialization[$key] = $serializedObjects;
				}

				if ( $this->getOptions()->shouldUseLowerCaseIdsAsKeys() ) {
					$key = strtolower( $key );
					$serialization[$key] = $serializedObjects;
				}
			}
		}

		$this->setIndexedTagName( $serialization, 'property' );

		return $serialization;
	}

}
