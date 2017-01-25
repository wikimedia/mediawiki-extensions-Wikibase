<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Repo\ChangeOp\Deserialization\ClaimsChangeOpDeserializer;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Summary;

/**
 * @covers Wikibase\Repo\ChangeOp\Deserialization\ClaimsChangeOpDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class ClaimsChangeOpDeserializerTest extends \PHPUnit_Framework_TestCase {

	public function testGivenClaimsFieldNotAnArray_createEntityChangeOpThrowsError() {
		ChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function() {
				$this->newClaimsChangeOpDeserializer()->createEntityChangeOp( [ 'claims' => null ] );
			},
			'not-recognized-array'
		);
	}

	public function testGivenRemoveClaimChangeRequestWithoutId_throwsException() {
		ChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function() {
				$this->newClaimsChangeOpDeserializer()->createEntityChangeOp( [
					'claims' => [ [ 'remove' => '', ] ]
				] );
			},
			'invalid-claim'
		);
	}

	public function testGivenClaimsDoesNotContainStatementSerialization_throwsException() {
		ChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function() {
				$this->newClaimsChangeOpDeserializer()->createEntityChangeOp( [
					'claims' => [ [ 'foo' ] ]
				] );
			},
			'invalid-claim'
		);
	}

	/**
	 * @dataProvider setStatementProvider
	 */
	public function testGivenNewStatementChangeRequest_setsStatement( $changeRequest, Item $item, $property ) {
		$changeOp = $this->newClaimsChangeOpDeserializer()
			->createEntityChangeOp( $changeRequest );
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
		$changeOp = $this->newClaimsChangeOpDeserializer()
			->createEntityChangeOp( $changeRequest );
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

	private function getStatementSerializer() {
		return WikibaseRepo::getDefaultInstance()->getStatementSerializer();
	}

	private function newClaimsChangeOpDeserializer() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		return new ClaimsChangeOpDeserializer(
			$wikibaseRepo->getExternalFormatStatementDeserializer(),
			$wikibaseRepo->getChangeOpFactoryProvider()->getStatementChangeOpFactory()
		);
	}

}
