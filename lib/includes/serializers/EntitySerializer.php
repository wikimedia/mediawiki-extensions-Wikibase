<?php

namespace Wikibase\Lib\Serializers;

use ApiResult;
use MWException;
use Wikibase\Entity;

/**
 * Serializer for entities.
 *
 * @since 0.2
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class EntitySerializer extends SerializerObject {

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
	 * @see ApiSerializerObject::$options
	 *
	 * @since 0.2
	 *
	 * @var SerializationOptions
	 */
	protected $options;

	/**
	 * Constructor.
	 *
	 * @since 0.2
	 *
	 * @param SerializationOptions $options
	 */
	public function __construct( SerializationOptions $options ) {
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

		$parts = $this->options->getOption( EntitySerializer::OPT_PARTS, array() );

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
