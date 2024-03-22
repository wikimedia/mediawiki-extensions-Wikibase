<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Serialization;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\DescriptionsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\PropertyDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\PropertyValuePairDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\ReferenceDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\StatementDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\StatementsDeserializer;

/**
 * @covers \Wikibase\Repo\RestApi\Application\Serialization\PropertyDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PropertyDeserializerTest extends TestCase {

	public function testDeserialize(): void {
		$propertySerialization = [
			'id' => 'P123',
			'type' => 'property',
			'data-type' => 'wikibase-item',
			'labels' => [ 'en' => 'english-label' ],
			'descriptions' => [ 'en' => 'english-description' ],
			'aliases' => [ 'en' => [ 'en-alias-1', 'en-alias-2' ] ],
			'statements' => [
				'P567' => [
					[ 'property' => [ 'id' => 'P567' ], 'value' => [ 'type' => 'somevalue' ] ],
				],
				'P789' => [
					[ 'property' => [ 'id' => 'P789' ], 'value' => [ 'type' => 'somevalue' ] ],
				],
			],
		];

		$this->assertEquals(
			new Property(
				new NumericPropertyId( 'P123' ),
				new Fingerprint(
					new TermList( [ new Term( 'en', 'english-label' ) ] ),
					new TermList( [ new Term( 'en', 'english-description' ) ] ),
					new AliasGroupList( [ new AliasGroup( 'en', [ 'en-alias-1', 'en-alias-2' ] ) ] )
				),
				'wikibase-item',
				new StatementList(
					NewStatement::someValueFor( 'P567' )->build(),
					NewStatement::someValueFor( 'P789' )->build()
				)
			),
			$this->newDeserializer()->deserialize( $propertySerialization )
		);
	}

	public function testDeserialize_withEmptySerialization(): void {
		$this->assertEquals(
			new Property( null, null, 'string', null ),
			$this->newDeserializer()->deserialize( [ 'data-type' => 'string' ] )
		);
	}

	private function newDeserializer(): PropertyDeserializer {
		$propValPairDeserializer = $this->createStub( PropertyValuePairDeserializer::class );
		$propValPairDeserializer->method( 'deserialize' )->willReturnCallback(
			fn( array $p ) => new PropertySomeValueSnak( new NumericPropertyId( $p[ 'property' ][ 'id' ] ) )
		);

		return new PropertyDeserializer(
			new LabelsDeserializer(),
			new DescriptionsDeserializer(),
			new AliasesDeserializer(),
			new StatementsDeserializer(
				new StatementDeserializer( $propValPairDeserializer, $this->createStub( ReferenceDeserializer::class ) )
			)
		);
	}

}
