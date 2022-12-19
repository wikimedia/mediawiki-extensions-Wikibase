<?php

namespace Wikibase\Client\Tests\Unit\Serializer;

use DataValues\Serializers\DataValueSerializer;
use Wikibase\Client\Serializer\ClientStatementListSerializer;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\StatementList;

/**
 * @covers \Wikibase\Client\Serializer\ClientStatementListSerializer
 *
 * @group Wikibase
 * @group WikibaseClient
 *
 * @license GPL-2.0-or-later
 * @author eranroz
 */
class ClientStatementListSerializerTest extends \PHPUnit\Framework\TestCase {

	private function newInstance() {
		$serializerFactory = new SerializerFactory(
			new DataValueSerializer(),
			SerializerFactory::OPTION_SERIALIZE_MAIN_SNAKS_WITHOUT_HASH +
			SerializerFactory::OPTION_SERIALIZE_REFERENCE_SNAKS_WITHOUT_HASH
		);

		$dataTypeLookup = $this->createMock( PropertyDataTypeLookup::class );
		$dataTypeLookup->method( 'getDataTypeIdForProperty' )
			->willReturn( '<DATATYPE>' );

		return new ClientStatementListSerializer(
			$serializerFactory->newStatementListSerializer(),
			$dataTypeLookup,
			WikibaseClient::getEntityIdParser()
		);
	}

	public function testSerialize() {
		$statementList = new StatementList();
		$statementList->addNewStatement( new PropertyNoValueSnak( 1 ) );
		$statements = $statementList->getByPropertyId( new NumericPropertyId( 'P1' ) );

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
				'rank' => 'normal',
			] ],
		];
		$this->assertSame( $expected, $serialization );
	}

}
