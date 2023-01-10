<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Serialization;

use ArrayObject;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Statement\Statement as DataModelStatement;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\Domain\ReadModel\Statement;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;
use Wikibase\Repo\RestApi\Serialization\ReadModelStatementListSerializer;
use Wikibase\Repo\RestApi\Serialization\ReadModelStatementSerializer;

/**
 * @covers \Wikibase\Repo\RestApi\Serialization\ReadModelStatementListSerializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ReadModelStatementListSerializerTest extends TestCase {

	public function testSerialize(): void {
		$statementList = new StatementList(
			$this->convertDataModelToReadModel(
				NewStatement::forProperty( 'P123' )
					->withValue( 'potato' )
					->withGuid( 'Q42$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' )
					->build()
			),
			$this->convertDataModelToReadModel(
				NewStatement::someValueFor( 'P321' )
					->withGuid( 'Q42$BBBBBBBB-BBBB-CCCC-DDDD-EEEEEEEEEEEE' )
					->build()
			),
			$this->convertDataModelToReadModel(
				NewStatement::noValueFor( 'P321' )
					->withGuid( 'Q42$CCCCCCCC-BBBB-CCCC-DDDD-EEEEEEEEEEEE' )
					->build()
			)
		);

		$this->assertEquals(
			new ArrayObject(
				[
					'P123' => [
						[ 'P123 statement serialization' ]
					],
					'P321' => [
						[ 'P321 statement serialization' ],
						[ 'P321 statement serialization' ],
					]
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

	private function newSerializer(): ReadModelStatementListSerializer {
		$statementSerializer = $this->createStub( ReadModelStatementSerializer::class );
		$statementSerializer->method( 'serialize' )
			->willReturnCallback(
				fn( Statement $statement ) => [
					$statement->getMainSnak()->getPropertyId()->serialize() . ' statement serialization'
				]
			);
		return new ReadModelStatementListSerializer( $statementSerializer );
	}

	private function convertDataModelToReadModel( DataModelStatement $statement ): Statement {
		[ $itemId, $guidPart ] = explode( '$', $statement->getGuid() );
		return new Statement(
			new StatementGuid( new ItemId( $itemId ), $guidPart ),
			$statement->getRank(),
			$statement->getMainSnak(),
			$statement->getQualifiers(),
			$statement->getReferences()
		);
	}

}
