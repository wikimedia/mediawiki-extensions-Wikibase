<?php

namespace Wikibase\Serializers;

use Wikibase\Lib\Serializers\SerializationOptions;

/**
 * Serialization options for FetchedEntityContentSerializer.
 *
 * @since 0.5
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 */
class FetchedEntityContentSerializationOptions extends SerializationOptions {
	/**
	 * @since 0.5
	 * @var SerializationOptions|null
	 */
	protected $contentSerializer;

	/**
	 * @since 0.5
	 *
	 * @param SerializationOptions|null $entitySerializationOptions The serializer options which
	 *        will be used when serializing the Entity contained by the EntityContent instance.
	 *
	 * NOTE: Originally a "contentSerializer" option has been intended without some refactoring
	 *       since for example EntitySerializer can not be used for serializing all kinds of
	 *       entities, instead a Property- or ItemSerializer have to be provided.
	 *       The whole serializer system is quite ugly, this just adds up to this, the whole thing
	 *       should be refactored in one go.
	 */
	public function __construct( SerializationOptions $entitySerializationOptions = null ) {
		$this->setEntitySerializationOptions( $entitySerializationOptions );
	}

	/**
	 * For getting the "entitySerializationOptions" option.
	 *
	 * @since 0.5
	 *
	 * @return SerializationOptions|null
	 */
	public function getEntitySerializationOptions() {
		return $this->contentSerializer;
	}

	/**
	 * For setting the "entitySerializationOptions" option.
	 *
	 * @since 0.5
	 *
	 * @param SerializationOptions $options|null
	 */
	public function setEntitySerializationOptions( SerializationOptions $options = null ) {
		$this->contentSerializer = $options;
	}
}
