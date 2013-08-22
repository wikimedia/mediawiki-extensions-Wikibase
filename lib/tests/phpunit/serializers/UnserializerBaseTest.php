<?php

namespace Wikibase\Lib\Test\Serializers;

use Wikibase\Lib\Serializers\Unserializer;

/**
 * Base class for tests that test classes deriving from Wikibase\SerializerObject.
 *
 * @file
 * @since 0.4
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseSerialization
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class UnserializerBaseTest extends \MediaWikiTestCase {

	/**
	 * @since 0.4
	 *
	 * @return string
	 */
	protected abstract function getClass();

	/**
	 * @since 0.4
	 *
	 * @return array
	 */
	public abstract function validProvider();

	/**
	 * @since 0.4
	 *
	 * @return Unserializer
	 */
	protected function getInstance() {
		$class = $this->getClass();
		return new $class();
	}

	/**
	 * @since 0.4
	 *
	 * @return array
	 */
	public function invalidProvider() {
		$invalid = array(
			false,
			true,
			null,
			42,
			4.2,
			'',
			'foo bar baz',
		);

		return $this->arrayWrap( $this->arrayWrap( $invalid ) );
	}

	/**
	 * @dataProvider invalidProvider
	 *
	 * @since 0.4
	 */
	public function testNewFromSerializationInvalid( $input ) {
		$serializer = $this->getInstance();
		$this->assertException( function() use ( $serializer, $input ) { $serializer->newFromSerialization( $input ); } );
	}

}
