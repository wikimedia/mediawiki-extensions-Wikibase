<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\Entity;

/**
 * @covers Wikibase\Lib\Serializers\EntitySerializer
 *
 * @since 0.2
 *
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseSerialization
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class EntitySerializerBaseTest extends SerializerBaseTest {

	/**
	 * @since 0.2
	 *
	 * @return Entity
	 */
	abstract protected function getEntityInstance();

}
