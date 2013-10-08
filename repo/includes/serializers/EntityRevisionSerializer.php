<?php

namespace Wikibase\Serializers;

use InvalidArgumentException;
use Wikibase\Entity;
use Wikibase\EntityRevision;
use Wikibase\EntityTitleLookup;
use Wikibase\Lib\Serializers\EntitySerializer;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\Serializers\SerializerObject;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Serializers\SerializerFactory;
use Wikibase\Repo\WikibaseRepo;

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
	 * @var SerializerFactory
	 */
	protected $serializerFactory;

	/**
	 * Constructor.
	 *
	 * @since 0.5
	 *
	 * @param EntityTitleLookup $titleLookup
	 * @param \Wikibase\Lib\Serializers\SerializerFactory $serializerFactory
	 * @param SerializationOptions $options
	 */
	public function __construct(
		EntityTitleLookup $titleLookup,
		SerializerFactory $serializerFactory,
		SerializationOptions $options = null
	) {
		parent::__construct( $options );

		$this->titleLookup = $titleLookup;
		$this->serializerFactory = $serializerFactory;
	}

	/**
	 * @see Serializer::getSerialized
	 *
	 * @since 0.5
	 *
	 * @param EntityRevision $entityContent
	 * @return array
	 *
	 * @throws InvalidArgumentException If $entityContent is no instance of Content.
	 */
	public function getSerialized( $entityRevision ) {
		if( !( $entityRevision instanceof EntityRevision ) ) {
			throw new InvalidArgumentException(
				'EntityRevisionSerializer can only serialize EntityRevision objects' );
		}

		/** @var $entity Entity */
		$entity = $entityRevision->getEntity();
		$entityTitle = $this->titleLookup->getTitleForId( $entity->getId() );

		$entitySerializer = $this->serializerFactory->newSerializerForObject(
			$entity,
			$this->options
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
	public static function newForFrontendStore( EntityTitleLookup $titleLookup, $primaryLanguage, LanguageFallbackChain $languageFallbackChain ) {
		$serializationOptions = new SerializationOptions();
		$serializationOptions->setOption( EntitySerializer::OPT_PARTS, array( 'labels', 'descriptions', 'datatype' ) );
		$serializationOptions->setLanguages( array( $primaryLanguage => $languageFallbackChain ) );

		$serializerFactory = WikibaseRepo::getDefaultInstance()->getSerializerFactory();
		$serializer = new EntityRevisionSerializer( $titleLookup, $serializerFactory, $serializationOptions );

		return $serializer;
	}
}
