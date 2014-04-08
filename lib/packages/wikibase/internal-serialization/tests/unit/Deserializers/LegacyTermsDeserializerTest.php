<?php

namespace Tests\Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Description;
use Wikibase\DataModel\Term\DescriptionList;
use Wikibase\DataModel\Term\Label;
use Wikibase\DataModel\Term\LabelList;
use Wikibase\InternalSerialization\Deserializers\LegacyFingerprintDeserializer;

/**
 * @covers Wikibase\InternalSerialization\Deserializers\LegacyFingerprintDeserializer
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LegacyFingerprintDeserializerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var Deserializer
	 */
	private $deserializer;

	public function setUp() {
		$this->deserializer = new LegacyFingerprintDeserializer();
	}

	/**
	 * @dataProvider labelListProvider
	 */
	public function testGivenLabels_getLabelsReturnsThem( array $labelSerialization, $expected ) {
		$fingerprint = $this->deserializer->deserialize( array( 'label' => $labelSerialization ) );

		$this->assertEquals( $expected, $fingerprint->getLabels() );
	}

	public function labelListProvider() {
		return array(
			array(
				array(),
				new LabelList( array() )
			),

			array(
				array(
					'en' => 'foo',
					'de' => 'bar',
				),
				new LabelList( array(
					new Label( 'en', 'foo' ),
					new Label( 'de', 'bar' ),
				) )
			),
		);
	}

	public function testGivenNonArrayLabels_exceptionIsThrown() {
		$this->expectDeserializationException();
		$this->deserializer->deserialize( array( 'label' => null ) );
	}

	private function expectDeserializationException() {
		$this->setExpectedException( 'Deserializers\Exceptions\DeserializationException' );
	}

	public function testGivenNonArrayDescriptions_exceptionIsThrown() {
		$this->expectDeserializationException();
		$this->deserializer->deserialize( array( 'description' => null ) );
	}

	/**
	 * @dataProvider descriptionListProvider
	 */
	public function testGivenDescriptions_getDescriptionsReturnsThem( array $descriptionSerialization, $expected ) {
		$fingerprint = $this->deserializer->deserialize( array( 'description' => $descriptionSerialization ) );

		$this->assertEquals( $expected, $fingerprint->getDescriptions() );
	}

	public function descriptionListProvider() {
		return array(
			array(
				array(),
				new DescriptionList( array() )
			),

			array(
				array(
					'en' => 'foo',
					'de' => 'bar',
				),
				new DescriptionList( array(
					new Description( 'en', 'foo' ),
					new Description( 'de', 'bar' ),
				) )
			),
		);
	}

	public function testGivenNonArrayAliases_exceptionIsThrown() {
		$this->expectDeserializationException();
		$this->deserializer->deserialize( array( 'aliases' => null ) );
	}

	/**
	 * @dataProvider aliasesListProvider
	 */
	public function testGivenAliases_getAliasesReturnsThem( array $aliasesSerialization, $expected ) {
		$fingerprint = $this->deserializer->deserialize( array( 'aliases' => $aliasesSerialization ) );

		$this->assertEquals( $expected, $fingerprint->getAliases() );
	}

	public function aliasesListProvider() {
		return array(
			array(
				array(),
				new AliasGroupList( array() )
			),

			array(
				array(
					'en' => array( 'foo', 'bar' ),
					'de' => array( 'foo', 'bar', 'baz' ),
					'nl' => array( 'bah' ),
					'fr' => array(),
				),
				new AliasGroupList( array(
					new AliasGroup( 'en', array( 'foo', 'bar' ) ),
					new AliasGroup( 'de', array( 'foo', 'bar', 'baz' ) ),
					new AliasGroup( 'nl', array( 'bah' ) ),
				) )
			),
		);
	}

}