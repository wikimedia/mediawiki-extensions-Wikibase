<?php

namespace Wikibase\Client\Tests\Serializer;

use DataValues\Serializers\DataValueSerializer;
use PHPUnit4And6Compat;
use Wikibase\Client\Serializer\ClientStatementListSerializer;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * @covers Wikibase\Client\Serializer\ClientStatementListSerializer
 *
 * @group Wikibase
 * @group WikibaseClient
 *
 * @license GPL-2.0-or-later
 * @author eranroz
 */
class ClientStatementListSerializerTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	private function newInstance() {
		$serializerFactory = new SerializerFactory(
			new DataValueSerializer(),
			SerializerFactory::OPTION_SERIALIZE_MAIN_SNAKS_WITHOUT_HASH +
			SerializerFactory::OPTION_SERIALIZE_REFERENCE_SNAKS_WITHOUT_HASH
		);

		$dataTypeLookup = $this->getMock( PropertyDataTypeLookup::class );
		$dataTypeLookup->expects( $this->any() )
			->method( 'getDataTypeIdForProperty' )
			->will( $this->returnValue( '<DATATYPE>' ) );

		return new ClientStatementListSerializer(
			$serializerFactory->newStatementListSerializer(),
			$dataTypeLookup
		);
	}

	public function testSerialize() {
		$statementList = new StatementList();
		$statementList->addNewStatement( new PropertyNoValueSnak( 1 ) );
		$statements = $statementList->getByPropertyId( new PropertyId( 'P1' ) );

		$instance = $this->newInstance();
		$serialization = $instance->serialize( $statements );

		$expected = [
			'P1' => [ [
				'mainsnak' => [
					'snaktype' => 'novalue',
					'property' => 'P1',
					'datatype' => '<DATATYPE>',
				],
				'type' => 'statement',
				'rank' => 'normal'
			] ],
		];
		$this->assertSame( $expected, $serialization );
	}

}
