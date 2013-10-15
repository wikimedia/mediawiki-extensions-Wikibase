<?php

namespace Wikibase\Serializers;

use Wikibase\Lib\Serializers\SerializationOptions;

/**
 * Serialization options for EntityRevisionSerializer.
 *
 * @since 0.5
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 */
class EntityRevisionSerializationOptions extends SerializationOptions {
	/**
	 * @since 0.5
	 * @var SerializationOptions|null
	 */
	protected $contentSerializer;

	/**
	 * @since 0.5
	 *
	 * @param SerializationOptions|null $SerializationOptions The serializer options which
	 *        will be used when serializing the Entity contained by the EntityContent instance.
	 *
	 * NOTE: Originally a "contentSerializer" option has been intended without some refactoring
	 *       since for example EntitySerializer can not be used for serializing all kinds of
	 *       entities, instead a Property- or ItemSerializer have to be provided.
	 *       The whole serializer system is quite ugly, this just adds up to this, the whole thing
	 *       should be refactored in one go.
	 */
	public function __construct( SerializationOptions $SerializationOptions = null ) {
		$this->setSerializationOptions( $SerializationOptions );
	}

	/**
	 * For getting the "SerializationOptions" option.
	 *
	 * @since 0.5
	 *
	 * @return SerializationOptions|null
	 */
	public function getSerializationOptions() {
		return $this->contentSerializer;
	}

	/**
	 * For setting the "SerializationOptions" option.
	 *
	 * @since 0.5
	 *
	 * @param SerializationOptions $options|null
	 */
	public function setSerializationOptions( SerializationOptions $options = null ) {
		$this->contentSerializer = $options;
	}
}
