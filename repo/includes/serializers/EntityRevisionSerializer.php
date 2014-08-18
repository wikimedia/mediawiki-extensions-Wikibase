<?php

namespace Wikibase\Serializers;

use InvalidArgumentException;
use Wikibase\EntityRevision;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\Serializers\EntitySerializer;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Serializers\SerializerFactory;
use Wikibase\Lib\Serializers\SerializerObject;
use Wikibase\Lib\Store\EntityTitleLookup;

/**
 * Serializer for some information related to Content. This is not a full Content serialization,
 * instead the serialized object will contain information required by the UI to create a
 * FetchedEntityRevision instance in JavaScript.
 *
 * @since 0.5
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 */
class EntityRevisionSerializer extends SerializerObject {

	/**
	 * @var EntityTitleLookup
	 */
	protected $titleLookup;

	/**
	 * @since 0.5
	 *
	 * @param EntityTitleLookup $titleLookup
	 * @param EntityRevisionSerializationOptions $options
	 */
	public function __construct(
		EntityTitleLookup $titleLookup,
		EntityRevisionSerializationOptions $options = null
	) {
		if( $options === null ) {
			$options = new EntityRevisionSerializationOptions();
		}
		parent::__construct( $options );

		$this->titleLookup = $titleLookup;
	}

	/**
	 * @see Serializer::getSerialized
	 *
	 * @since 0.5
	 *
	 * @param EntityRevision $entityRevision
	 *
	 * @throws InvalidArgumentException If $entityContent is no instance of Content.
	 * @return array
	 */
	public function getSerialized( $entityRevision ) {
		if( !( $entityRevision instanceof EntityRevision ) ) {
			throw new InvalidArgumentException(
				'EntityRevisionSerializer can only serialize EntityRevision objects' );
		}

		$entity = $entityRevision->getEntity();
		$entityTitle = $this->titleLookup->getTitleForId( $entity->getId() );
		$serializationOptions = $this->options->getSerializationOptions();

		$serializerFactory = new SerializerFactory(); //TODO: inject
		$entitySerializer = $serializerFactory->newSerializerForObject(
			$entity,
			$serializationOptions
		);
		$serialization['content'] = $entitySerializer->getSerialized( $entity );
		$serialization['title'] = $entityTitle->getPrefixedText();
		$serialization['revision'] = $entityTitle->getLatestRevID() ?: '';

		return $serialization;
	}

	/**
	 * Creates a new instance suitable for EntityRevision serializations in a form as required in the
	 * frontend's "wikibase.fetchedEntities" global.
	 *
	 * @since 0.5
	 *
	 * @param EntityTitleLookup $titleLookup
	 * @param string $primaryLanguage
	 * @param LanguageFallbackChain $languageFallbackChain
	 *
	 * @return EntityRevisionSerializer
	 */
	public static function newForFrontendStore(
		EntityTitleLookup $titleLookup,
		$primaryLanguage,
		LanguageFallbackChain $languageFallbackChain
	) {
		$serializationOptions = new SerializationOptions();

		$serializationOptions->setOption( EntitySerializer::OPT_PARTS, array( 'labels', 'descriptions', 'datatype' ) );
		$serializationOptions->setLanguages( array( $primaryLanguage => $languageFallbackChain ) );

		$entityRevisionSerializationOptions =
			new EntityRevisionSerializationOptions( $serializationOptions );

		$serializer = new EntityRevisionSerializer( $titleLookup, $entityRevisionSerializationOptions );
		return $serializer;
	}

}
