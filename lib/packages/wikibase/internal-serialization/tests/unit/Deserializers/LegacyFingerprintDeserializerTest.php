<?php

namespace Tests\Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\InternalSerialization\Deserializers\LegacyFingerprintDeserializer;

/**
 * @covers Wikibase\InternalSerialization\Deserializers\LegacyFingerprintDeserializer
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LegacyFingerprintDeserializerTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @var Deserializer
	 */
	private $deserializer;

	protected function setUp(): void {
		$this->deserializer = new LegacyFingerprintDeserializer();
	}

	/**
	 * @dataProvider TermListProvider
	 */
	public function testGivenLabels_getLabelsReturnsThem( array $labelSerialization, $expected ) {
		$fingerprint = $this->deserializer->deserialize( [ 'label' => $labelSerialization ] );

		$this->assertEquals( $expected, $fingerprint->getLabels() );
	}

	public function TermListProvider() {
		return [
			[
				[],
				new TermList( [] ),
			],

			[
				[
					'en' => 'foo',
					'de' => 'bar',
				],
				new TermList( [
					new Term( 'en', 'foo' ),
					new Term( 'de', 'bar' ),
				] ),
			],
		];
	}

	public function testGivenNonArray_exceptionIsThrown() {
		$this->expectDeserializationException();
		$this->deserializer->deserialize( null );
	}

	public function testGivenNonArrayLabels_exceptionIsThrown() {
		$this->expectDeserializationException();
		$this->deserializer->deserialize( [ 'label' => null ] );
	}

	public function testGivenInvalidTermSerialization_exceptionIsThrown() {
		$this->expectDeserializationException();
		$this->deserializer->deserialize( [ 'label' => [ null ] ] );
	}

	private function expectDeserializationException() {
		$this->expectException( DeserializationException::class );
	}

	public function testGivenNonArrayDescriptions_exceptionIsThrown() {
		$this->expectDeserializationException();
		$this->deserializer->deserialize( [ 'description' => null ] );
	}

	/**
	 * @dataProvider descriptionListProvider
	 */
	public function testGivenDescriptions_getDescriptionsReturnsThem( array $descriptionSerialization, $expected ) {
		$fingerprint = $this->deserializer->deserialize( [ 'description' => $descriptionSerialization ] );

		$this->assertEquals( $expected, $fingerprint->getDescriptions() );
	}

	public function descriptionListProvider() {
		return [
			[
				[],
				new TermList( [] ),
			],

			[
				[
					'en' => 'foo',
					'de' => 'bar',
				],
				new TermList( [
					new Term( 'en', 'foo' ),
					new Term( 'de', 'bar' ),
				] ),
			],
		];
	}

	public function testGivenNonArrayAliases_exceptionIsThrown() {
		$this->expectDeserializationException();
		$this->deserializer->deserialize( [ 'aliases' => null ] );
	}

	/**
	 * @dataProvider aliasesListProvider
	 */
	public function testGivenAliases_getAliasesReturnsThem( array $aliasesSerialization, $expected ) {
		/**
		 * @var Fingerprint $fingerprint
		 */
		$fingerprint = $this->deserializer->deserialize( [ 'aliases' => $aliasesSerialization ] );

		$this->assertEquals( $expected, $fingerprint->getAliasGroups() );
	}

	public function aliasesListProvider() {
		return [
			[
				[],
				new AliasGroupList( [] ),
			],

			[
				[
					'en' => [ 'foo', 'bar' ],
					'de' => [ 'foo', 'bar', 'baz' ],
					'nl' => [ 'bah' ],
					'fr' => [],
				],
				new AliasGroupList( [
					new AliasGroup( 'en', [ 'foo', 'bar' ] ),
					new AliasGroup( 'de', [ 'foo', 'bar', 'baz' ] ),
					new AliasGroup( 'nl', [ 'bah' ] ),
				] ),
			],
		];
	}

}
