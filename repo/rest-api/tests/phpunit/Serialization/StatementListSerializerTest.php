<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Serialization;

use ArrayObject;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\Serialization\StatementListSerializer;
use Wikibase\Repo\RestApi\Serialization\StatementSerializer;

/**
 * @covers \Wikibase\Repo\RestApi\Serialization\StatementListSerializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class StatementListSerializerTest extends TestCase {

	public function testSerialize(): void {
		$statementList = new StatementList(
			NewStatement::forProperty( 'P123' )
				->withValue( 'potato' )
				->build(),
			NewStatement::someValueFor( 'P321' )
				->build(),
			NewStatement::noValueFor( 'P321' )
				->build()
		);

		$this->assertEquals(
			new ArrayObject(
				[
					'P123' => [
						[ 'P123 statement serialization' ],
					],
					'P321' => [
						[ 'P321 statement serialization' ],
						[ 'P321 statement serialization' ],
					],
				]
			),
			$this->newSerializer()->serialize( $statementList )
		);
	}

	public function testSerializeEmptyList(): void {
		$this->assertEquals(
			new ArrayObject(),
			$this->newSerializer()->serialize( new StatementList() )
		);
	}

	private function newSerializer(): StatementListSerializer {
		$statementSerializer = $this->createStub( StatementSerializer::class );
		$statementSerializer->method( 'serialize' )
			->willReturnCallback(
				fn( Statement $statement ) => [
					$statement->getPropertyId()->serialize() . ' statement serialization',
				]
			);
		return new StatementListSerializer( $statementSerializer );
	}

}
