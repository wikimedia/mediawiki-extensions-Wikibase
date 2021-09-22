<?php

namespace Wikibase\InternalSerialization;

/**
 * Public interface of the library for constructing serializers.
 * Direct access to serializers is prohibited, users are only allowed to
 * know about this interface. Also note that the return type of the methods
 * is "Serializer". You are also not allowed to know which concrete
 * implementation is returned.
 *
 *
 * @since 1.0
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SerializerFactory extends \Wikibase\DataModel\Serializers\SerializerFactory {
}
