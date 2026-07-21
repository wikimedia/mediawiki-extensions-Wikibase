<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Statements\Application\Serialization;

use ArrayObject;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\Domains\Statements\Application\Serialization\StatementListSerializer;
use Wikibase\Repo\Domains\Statements\Application\Serialization\StatementSerializer;
use Wikibase\Repo\Domains\Statements\Domain\ReadModel\PredicateProperty;
use Wikibase\Repo\Domains\Statements\Domain\ReadModel\Qualifiers;
use Wikibase\Repo\Domains\Statements\Domain\ReadModel\Rank;
use Wikibase\Repo\Domains\Statements\Domain\ReadModel\References;
use Wikibase\Repo\Domains\Statements\Domain\ReadModel\Statement;
use Wikibase\Repo\Domains\Statements\Domain\ReadModel\StatementList;
use Wikibase\Repo\Domains\Statements\Domain\ReadModel\Value;

/**
 * @covers \Wikibase\Repo\Domains\Statements\Application\Serialization\StatementListSerializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class StatementListSerializerTest extends TestCase {

	public function testSerialize(): void {
		$statementList = new StatementList(
			$this->newStatement( 'P123', 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' ),
			$this->newStatement( 'P321', 'BBBBBBBB-BBBB-CCCC-DDDD-EEEEEEEEEEEE' ),
			$this->newStatement( 'P321', 'CCCCCCCC-BBBB-CCCC-DDDD-EEEEEEEEEEEE' ),
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

	private function newStatement( string $propertyId, string $guidPart ): Statement {
		return new Statement(
			new StatementGuid( new ItemId( 'Q42' ), $guidPart ),
			new PredicateProperty( new NumericPropertyId( $propertyId ), 'string' ),
			new Value( Value::TYPE_SOME_VALUE ),
			Rank::normal(),
			new Qualifiers(),
			new References()
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
