<?php

namespace Wikibase\Serializers;

use ApiResult;
use InvalidArgumentException;
use Content;
use MWException;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\EntityContent;
use Wikibase\Lib\Serializers\SerializerObject;
use Wikibase\Lib\Serializers\EntitySerializationOptions;
use Wikibase\Lib\Serializers\SerializerFactory;

/**
 * Serializer for some information related to Content. This is not a full Content serialization,
 * instead the serialized object will contain information required by the UI to create a
 * FetchedEntityContent instance in JavaScript.
 *
 * @since 0.5
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 */
class FetchedEntityContentSerializer extends SerializerObject {
	/**
	 * @see SerializerObject::$options
	 * @var FetchedEntityContentSerializationOptions
	 */
	protected $options;

	/**
	 * Constructor.
	 *
	 * @since 0.5
	 *
	 * @param FetchedEntityContentSerializationOptions $options
	 */
	public function __construct( FetchedEntityContentSerializationOptions $options = null ) {
		if( $options === null ) {
			$options = new FetchedEntityContentSerializationOptions();
		}
		parent::__construct( $options );
	}

	/**
	 * @see Serializer::getSerialized
	 *
	 * @since 0.5
	 *
	 * @param EntityContent $entityContent
	 * @return array
	 *
	 * @throws InvalidArgumentException If $entityContent is no instance of Content.
	 */
	public function getSerialized( $entityContent ) {
		if( !( $entityContent instanceof Content ) ) {
			throw new InvalidArgumentException(
				'FetchedEntityContentSerializer can only serialize Content objects' );
		}

		/** @var $entity Entity */
		$entity = $entityContent->getEntity();
		$entitySerializationOptions = $this->options->getEntitySerializationOptions();

		$serializerFactory = new SerializerFactory();
		$entitySerializer = $serializerFactory->newSerializerForObject(
			$entity,
			$entitySerializationOptions
		);
		$serialization['content'] = $entitySerializer->getSerialized( $entity );
		$serialization['title'] = $entityContent->getTitle()->getPrefixedText();

		$entityPageRevision = $entityContent->getWikiPage()->getRevision();

		if( !$entityPageRevision ) {
			$serialization['revision'] = '';
		} else {
			$serialization['revision'] = $entityPageRevision->getId();
		}

		return $serialization;
	}

	/**
	 * Creates a new instance suitable for EntityContent serializations in a form as required in the
	 * frontend's "wikibase.fetchedEntities" global.
	 *
	 * @since 0.5
	 *
	 * @param string $primaryLanguage
	 * @return FetchedEntityContentSerializer
	 */
	public static function newForFrontendStore( $primaryLanguage ) {
		$entitySerializationOptions =
			new EntitySerializationOptions( WikibaseRepo::getDefaultInstance()->getIdFormatter() );
		$entitySerializationOptions->setProps( array( 'labels', 'descriptions', 'datatype' ) );
		$entitySerializationOptions->setLanguages( array( $primaryLanguage ) );

		$fetchedEntityContentSerializationOptions =
			new FetchedEntityContentSerializationOptions( $entitySerializationOptions );

		$serializer = new FetchedEntityContentSerializer( $fetchedEntityContentSerializationOptions );
		return $serializer;
	}
}
