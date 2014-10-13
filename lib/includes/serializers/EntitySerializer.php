<?php

namespace Wikibase\Lib\Serializers;

use InvalidArgumentException;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\EntityFactory;

/**
 * Serializer for entities.
 *
 * See docs/json.wiki for details of the format.
 *
 * @since 0.2
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
abstract class EntitySerializer extends SerializerObject implements Unserializer {

	const SORT_ASC = 'ascending';
	const SORT_DESC = 'descending';
	const SORT_NONE = 'none';

	/**
	 * @const option key for setting the sort order; use the SORT_XXX constants as values.
	 */
	const OPT_SORT_ORDER = 'entityPartOrder';

	/**
	 * @const option key for the list of entity parts to include in the serialization, e.g.
	 *        array( 'aliases', 'descriptions', 'labels', 'claims', 'datatype',  'sitelinks' )
	 */
	const OPT_PARTS = 'entityParts';

	/**
	 * @const option key for specifying which fields to use for sorting.
	 * @todo: find out whether this is still needed.
	 */
	const OPT_SORT_FIELDS = 'entitySortFields';

	/**
	 * @var EntityFactory
	 */
	private $entityFactory;

	/**
	 * @var ClaimSerializer
	 */
	private $claimSerializer;

	/**
	 * Constructor.
	 *
	 * @since 0.2
	 *
	 * @param ClaimSerializer $claimSerializer
	 * @param SerializationOptions|null $options
	 * @param EntityFactory|null $entityFactory
	 *
	 * @todo: make $entityFactory required
	 */
	public function __construct( ClaimSerializer $claimSerializer, SerializationOptions $options = null, EntityFactory $entityFactory = null ) {
		if ( $options === null ) {
			$options = new SerializationOptions();
		}

		$options->initOption( self::OPT_SORT_ORDER, self::SORT_NONE );

		$options->initOption( self::OPT_PARTS,  array(
			'aliases',
			'descriptions',
			'labels',
			'claims',
			// TODO: the following properties are not part of all entities, listing them here is not nice
			'datatype', // property specific
			'sitelinks', // item specific
		) );

		$options->initOption( self::OPT_SORT_FIELDS,  array() );

		parent::__construct( $options );

		if ( $entityFactory === null ) {
			// FIXME: This is bad. We need to require the EntityFactory to be provided (bug 66020).
			// That requires refactoring of all calls to the constructor of SerializerFactory,
			// which currently allows all parameters to be null.
			$this->entityFactory = new EntityFactory(
				array(
					Item::ENTITY_TYPE => 'Wikibase\DataModel\Entity\Item',
					Property::ENTITY_TYPE => 'Wikibase\DataModel\Entity\Property',
				)
			);
		} else {
			$this->entityFactory = $entityFactory;
		}

		$this->claimSerializer = $claimSerializer;
	}

	/**
	 * @since 0.2
	 *
	 * @param mixed $entity
	 *
	 * @return array
	 * @throws InvalidArgumentException
	 */
	final public function getSerialized( $entity ) {
		if ( !( $entity instanceof Entity ) ) {
			throw new InvalidArgumentException( 'EntitySerializer can only serialize Entity objects' );
		}

		//NOTE: when changing the serialization structure, update docs/json.wiki too!
		$serialization['id'] = $entity->getId() ? $entity->getId()->getSerialization() : '';

		$serialization['type'] = $entity->getType();

		$parts = $this->options->getOption( self::OPT_PARTS, array() );

		foreach ( $parts as $key ) {
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
					$claimsSerializer = new ClaimsSerializer( $this->claimSerializer, $this->options );
					$serialization['claims'] = $claimsSerializer->getSerialized( new Claims( $entity->getClaims() ) );
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
		$validTypes = $this->entityFactory->getEntityTypes();

		if ( !array_key_exists( 'type', $data ) || !in_array( $data['type'], $validTypes ) ) {
			throw new InvalidArgumentException( 'Invalid entity serialization' );
		}

		$entityType = $data['type'];
		$entity = $this->entityFactory->newEmpty( $entityType );

		if ( array_key_exists( 'id', $data ) ) {
			$idParser = new BasicEntityIdParser(); //FIXME: inject!
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

		if ( array_key_exists( 'claims', $data ) && method_exists( $entity, 'setClaims' ) ) {
			$claimsSerializer = new ClaimsSerializer( $this->claimSerializer, $this->options );
			$claims = $claimsSerializer->newFromSerialization( $data['claims'] );
			$entity->setClaims( $claims );
		}

		return $entity;
	}

}
