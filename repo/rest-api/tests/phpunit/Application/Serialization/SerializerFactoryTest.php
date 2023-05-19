<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Serialization;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Repo\RestApi\Application\Serialization\ItemPartsSerializer;
use Wikibase\Repo\RestApi\Application\Serialization\SerializerFactory;
use Wikibase\Repo\RestApi\Application\Serialization\StatementListSerializer;

/**
 * @covers \Wikibase\Repo\RestApi\Application\Serialization\SerializerFactory
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

	public function testNewItemPartsSerializer(): void {
		$this->assertInstanceOf(
			ItemPartsSerializer::class,
			$this->newSerializerFactory()->newItemPartsSerializer()
		);
	}

	private function newSerializerFactory(): SerializerFactory {
		return new SerializerFactory(
			$this->createStub( PropertyDataTypeLookup::class )
		);
	}

}
