<?php

namespace Tests\Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\InternalSerialization\Deserializers\LegacySiteLinkListDeserializer;

/**
 * @covers Wikibase\InternalSerialization\Deserializers\SiteLinkListDeserializer
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SiteLinkListDeserializerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var Deserializer
	 */
	private $deserializer;

	protected function setUp() {
		$this->deserializer = new LegacySiteLinkListDeserializer();
	}

	public function invalidSerializationProvider() {
		return array(
			array( null ),
			array( 42 ),
			array( 'foo' ),

			array( array( 'foo' ) ),
			array( array( 'foo' => 42 ) ),
			array( array( 'foo' => array() ) ),

			array( array( 'foo' => array( 'bar' => 'baz' ) ) ),
			array( array( 'foo' => array( 'name' => 'baz' ) ) ),
			array( array( 'foo' => array( 'badges' => array() ) ) ),

			array( array( 'foo' => array( 'name' => 'baz', 'badges' => array( 42 ) ) ) ),
			array( array( 'foo' => array( 'name' => 'baz', 'badges' => array( 'Q42', 'Q42' ) ) ) ),
		);
	}

	/**
	 * @dataProvider invalidSerializationProvider
	 */
	public function testGivenInvalidSerialization_deserializeThrowsException( $serialization ) {
		$this->setExpectedException( 'Deserializers\Exceptions\DeserializationException' );
		$this->deserializer->deserialize( $serialization );
	}

	public function testEmptyListDeserialization() {
		$list = $this->deserializer->deserialize( array() );
		$this->assertInstanceOf( 'Wikibase\DataModel\SiteLinkList', $list );
	}

	public function serializationProvider() {
		return array(
			array( array(
			) ),

			array( array(
				'foo' => array(
					'name' => 'bar',
					'badges' => array(),
				),
				'baz' => array(
					'name' => 'bah',
					'badges' => array(),
				)
			) ),

			array( array(
				'foo' => array(
					'name' => 'bar',
					'badges' => array( 'Q42', 'Q1337' ),
				)
			) ),

			array( array(
				'foo' => 'bar',
			) ),

			array( array(
				'foo' => 'bar',
				'baz' => 'bah',
			) ),
		);
	}

	/**
	 * @dataProvider serializationProvider
	 */
	public function testGivenValidSerialization_deserializeReturnsSiteLinkList( $serialization ) {
		$siteLinkList = $this->deserializer->deserialize( $serialization );
		$this->assertInstanceOf( 'Wikibase\DataModel\SiteLinkList', $siteLinkList );
	}

	public function testDeserialization() {
		$this->assertEquals(
			new SiteLinkList(
				array(
					new SiteLink( 'foo', 'bar', array( new ItemId( 'Q42' ), new ItemId( 'Q1337' ) ) ),
					new SiteLink( 'bar', 'baz' )
				)
			),
			$this->deserializer->deserialize(
				array(
					'foo' => array(
						'name' => 'bar',
						'badges' => array( 'Q42', 'Q1337' ),
					),
					'bar' => 'baz'
				)
			)
		);
	}

}