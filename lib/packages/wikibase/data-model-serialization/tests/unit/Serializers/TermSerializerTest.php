<?php

namespace Tests\Wikibase\DataModel\Serializers;

use Serializers\Exceptions\UnsupportedObjectException;
use Wikibase\DataModel\Serializers\TermSerializer;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermFallback;

/**
 * @covers Wikibase\DataModel\Serializers\TermSerializer
 *
 * @license GPL-2.0+
 * @author Addshore
 */
class TermSerializerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider serializationProvider
	 */
	public function testSerialization( Term $input, array $expected ) {
		$serializer = new TermSerializer();

		$output = $serializer->serialize( $input );

		$this->assertEquals( $expected, $output );
	}

	public function serializationProvider() {
		return [
			[
				new Term( 'en', 'SomeValue' ),
				[
					'language' => 'en',
					'value' => 'SomeValue',
				]
			],
			[
				new TermFallback( 'en', 'SomeValue', 'en-gb', 'en' ),
				[
					'language' => 'en-gb',
					'value' => 'SomeValue',
					'source' => 'en',
				]
			],
		];
	}

	public function testWithUnsupportedObject() {
		$serializer = new TermSerializer();

		$this->setExpectedException( UnsupportedObjectException::class );
		$serializer->serialize( new \stdClass() );
	}

}
