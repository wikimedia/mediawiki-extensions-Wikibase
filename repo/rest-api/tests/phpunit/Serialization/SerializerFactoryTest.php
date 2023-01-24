<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Serialization;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Repo\RestApi\Serialization\ItemDataSerializer;
use Wikibase\Repo\RestApi\Serialization\SerializerFactory;
use Wikibase\Repo\RestApi\Serialization\StatementListSerializer;

/**
 * @covers \Wikibase\Repo\RestApi\Serialization\SerializerFactory
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SerializerFactoryTest extends TestCase {

	public function testNewStatementListSerializer(): void {
		$this->assertInstanceOf(
			StatementListSerializer::class,
			$this->newSerializerFactory()->newStatementListSerializer()
		);
	}

	public function testNewItemDataSerializer(): void {
		$this->assertInstanceOf(
			ItemDataSerializer::class,
			$this->newSerializerFactory()->newItemDataSerializer()
		);
	}

	private function newSerializerFactory(): SerializerFactory {
		return new SerializerFactory(
			$this->createStub( PropertyDataTypeLookup::class )
		);
	}

}
