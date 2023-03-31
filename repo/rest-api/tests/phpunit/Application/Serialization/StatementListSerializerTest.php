<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Serialization;

use ArrayObject;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\Serialization\StatementListSerializer;
use Wikibase\Repo\RestApi\Application\Serialization\StatementSerializer;
use Wikibase\Repo\RestApi\Domain\ReadModel\Statement;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;
use Wikibase\Repo\Tests\RestApi\Domain\ReadModel\NewStatementReadModel;

/**
 * @covers \Wikibase\Repo\RestApi\Application\Serialization\StatementListSerializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class StatementListSerializerTest extends TestCase {

	public function testSerialize(): void {
		$statementList = new StatementList(
			NewStatementReadModel::forProperty( 'P123' )
				->withValue( 'potato' )
				->withGuid( 'Q42$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' )
				->build(),
			NewStatementReadModel::someValueFor( 'P321' )
				->withGuid( 'Q42$BBBBBBBB-BBBB-CCCC-DDDD-EEEEEEEEEEEE' )
				->build(),
			NewStatementReadModel::noValueFor( 'P321' )
				->withGuid( 'Q42$CCCCCCCC-BBBB-CCCC-DDDD-EEEEEEEEEEEE' )
				->build(),
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
					$statement->getProperty()->getId()->getSerialization() . ' statement serialization',
				]
			);
		return new StatementListSerializer( $statementSerializer );
	}

}
