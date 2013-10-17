<?php

namespace Wikibase\Lib\Serializers;

use ApiResult;
use InvalidArgumentException;
use MWException;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\Entity;
use Wikibase\EntityFactory;

/**
 * Serializer for entities.
 *
 * @since 0.2
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class EntitySerializer extends SerializerObject implements Unserializer {

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
	final public function getSerialized( $entity ) {
		if ( !( $entity instanceof Entity ) ) {
			throw new MWException( 'EntitySerializer can only serialize Entity objects' );
		}

		$serialization['id'] = $entity->getId() ? $entity->getId()->getPrefixedId() : '';
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
					$serialization['descriptions'] = $descriptionSerializer->
						getSerializedMultilingualValues( $entity->getDescriptions() );
					break;
				case 'labels':
					$labelSerializer = new LabelSerializer( $this->options );
					$serialization['labels'] = $labelSerializer->
						getSerializedMultilingualValues( $entity->getLabels() );
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

	/**
	 * @see Unserializer::newFromSerialization
	 *
	 * @since 0.4
	 *
	 * @param array $data
	 *
	 * @return Entity
	 * @throws InvalidArgumentException
	 */
	public function newFromSerialization( array $data ) {
		// @todo inject EntityFactory
		$entityFactory = new EntityFactory();
		$validTypes = $entityFactory->getEntityTypes();

		if ( !array_key_exists( 'type', $data ) || !in_array( $data['type'], $validTypes ) ) {
			throw new InvalidArgumentException( 'Invalid entity serialization' );
		}

		$entityType = $data['type'];
		$entity = $entityFactory->newEmpty( $entityType );

		if ( array_key_exists( 'id', $data ) ) {
			$idParser = new BasicEntityIdParser();
			$entityId = $idParser->parse( $data['id'] );

			if ( $entityId->getEntityType() !== $entityType ) {
				throw new InvalidArgumentException( 'Mismatched entity type and entity id in serialization.' );
			}

			$entity->setId( $entityId );
		}

		if ( array_key_exists( 'aliases', $data ) ) {
			$aliasSerializer = new AliasSerializer( $this->options );
			$aliases = $aliasSerializer->newFromSerialization( $data['aliases'] );
			$entity->setAllAliases( $aliases );
		}

		if ( array_key_exists( 'descriptions', $data ) ) {
			$descriptionSerializer = new DescriptionSerializer( $this->options );
			$descriptions = $descriptionSerializer->newFromSerialization( $data['descriptions'] );
			$entity->setDescriptions( $descriptions );
		}

		if ( array_key_exists( 'labels', $data ) ) {
			$labelSerializer = new LabelSerializer( $this->options );
			$labels = $labelSerializer->newFromSerialization( $data['labels'] );
			$entity->setLabels( $labels );
		}

		if ( array_key_exists( 'claims', $data ) ) {
			$claimsSerializer = new ClaimsSerializer( $this->options );
			$claims = $claimsSerializer->newFromSerialization( $data['claims'] );
			$entity->setClaims( $claims );
		}

		return $entity;
	}
}
