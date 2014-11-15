<?php

namespace Tests\Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
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

	protected function setUp() {
		$this->deserializer = new LegacyFingerprintDeserializer();
	}

	/**
	 * @dataProvider TermListProvider
	 */
	public function testGivenLabels_getLabelsReturnsThem( array $labelSerialization, $expected ) {
		$fingerprint = $this->deserializer->deserialize( array( 'label' => $labelSerialization ) );

		$this->assertEquals( $expected, $fingerprint->getLabels() );
	}

	public function TermListProvider() {
		return array(
			array(
				array(),
				new TermList( array() )
			),

			array(
				array(
					'en' => 'foo',
					'de' => 'bar',
				),
				new TermList( array(
					new Term( 'en', 'foo' ),
					new Term( 'de', 'bar' ),
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
				new TermList( array() )
			),

			array(
				array(
					'en' => 'foo',
					'de' => 'bar',
				),
				new TermList( array(
					new Term( 'en', 'foo' ),
					new Term( 'de', 'bar' ),
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
		/**
		 * @var Fingerprint $fingerprint
		 */
		$fingerprint = $this->deserializer->deserialize( array( 'aliases' => $aliasesSerialization ) );

		$this->assertEquals( $expected, $fingerprint->getAliasGroups() );
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
