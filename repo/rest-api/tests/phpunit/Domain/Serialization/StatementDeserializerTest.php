<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Domain\Serialization;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Deserializers\StatementDeserializer as LegacyStatementDeserializer;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\RestApi\Domain\Serialization\StatementDeserializer;

/**
 * @covers \Wikibase\Repo\RestApi\Domain\Serialization\StatementDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class StatementDeserializerTest extends TestCase {

	public function testDeserialize_addsTypeField(): void {
		$statementSerialization = [];
		$expectedStatementSerialization = [ 'type' => 'statement' ];

		$legacyStatementDeserializer = $this->createMock( LegacyStatementDeserializer::class );
		$legacyStatementDeserializer
			->expects( $this->once() )
			->method( 'deserialize' )
			->with( $expectedStatementSerialization )
			->willReturn( $this->createStub( Statement::class ) );

		$statementDeserializer = new StatementDeserializer( $legacyStatementDeserializer );
		$statementDeserializer->deserialize( $statementSerialization );
	}

}
