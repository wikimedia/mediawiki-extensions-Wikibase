<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Summary;

/**
 * Set of test methods that can be reused in ClaimsChangeOpDeserializerTest and tests for
 * ChangeOpDeserializers of entities that have claims
 */
trait ClaimsChangeOpDeserializationTest {
	/**
	 * @dataProvider setStatementProvider
	 */
	public function testGivenNewStatementChangeRequest_setsStatement( $changeRequest, Item $item, $property ) {
		$changeOp = $this->getChangeOpDeserializer()->createEntityChangeOp( $changeRequest );
		$changeOp->apply( $item, new Summary() );

		$this->assertFalse(
			$item->getStatements()->getByPropertyId( $property )->isEmpty()
		);
	}

	public function setStatementProvider() {
		$property = new PropertyId( 'P7' );
		$statement = new Statement( new PropertyNoValueSnak( $property ) );
		$statementSerialization = $this->getStatementSerializer()->serialize( $statement );
		$item = new Item( new ItemId( 'Q23' ) );

		return [
			'numeric index format' => [ [ 'claims' => [ $statementSerialization ] ], $item, $property ],
			'associative format' => [ [ 'claims' => [ 'P7' => [ $statementSerialization ] ] ], $item, $property ],
		];
	}

	/**
	 * @dataProvider deleteStatementProvider
	 */
	public function testGivenRemoveChangeRequest_removesStatement( $changeRequest, Item $item, $property ) {
		$changeOp = $this->getChangeOpDeserializer()->createEntityChangeOp( $changeRequest );
		$changeOp->apply( $item, new Summary() );

		$this->assertTrue( $item->getStatements()->getByPropertyId( $property )->isEmpty() );
	}

	public function deleteStatementProvider() {
		$property = new PropertyId( 'P7' );
		$statement = new Statement( new PropertyNoValueSnak( $property ) );
		$statement->setGuid( 'test-guid' );
		$item = new Item( new ItemId( 'Q23' ) );
		$item->setStatements( new StatementList( [ $statement ] ) );

		return [
			'numeric index format' => [
				[ 'claims' => [
					[ 'remove' => '', 'id' => $statement->getGuid() ]
				] ],
				$item,
				$property
			],
			'associative format' => [
				[ 'claims' => [
					'P7' => [ [ 'remove' => '', 'id' => $statement->getGuid() ] ]
				] ],
				$item->copy(),
				$property
			],
		];
	}

	/**
	 * @dataProvider editStatementProvider
	 */
	public function testGivenEditChangeRequest_statementGetsChanged( $changeRequest, Item $item ) {
		$changeOp = $this->getChangeOpDeserializer()->createEntityChangeOp( $changeRequest );
		$changeOp->apply( $item, new Summary() );

		$this->assertCount( 1, $item->getStatements()->toArray() );
		$this->assertSame(
			'bar',
			$item->getStatements()->toArray()[0]
				->getMainSnak()
				->getDataValue()
				->getValue()
		);
	}

	public function editStatementProvider() {
		$property = new PropertyId( 'P7' );
		$statement = new Statement( new PropertyValueSnak( $property, new StringValue( 'foo' ) ) );
		$statement->setGuid( 'Q23$D8404CDA-25E4-4334-AF13-A3290BC66666' );
		$item = new Item( new ItemId( 'Q23' ) );
		$item->setStatements( new StatementList( [ $statement ] ) );
		$statementSerialization = $this->getStatementSerializer()->serialize( $statement );
		$statementSerialization['mainsnak']['datavalue']['value'] = 'bar';

		return [
			'numeric index format' => [
				[ 'claims' => [ $statementSerialization ] ],
				$item
			],
			'associative format' => [
				[ 'claims' => [ 'P7' => [ $statementSerialization ] ] ],
				$item
			],
		];
	}

	private function getStatementSerializer() {
		return WikibaseRepo::getDefaultInstance()->getStatementSerializer();
	}
}
