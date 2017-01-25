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
class ClaimsChangeOpSerializationTest extends \PHPUnit_Framework_TestCase {

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

	public function testGivenNewStatementChangeRequest_setsStatement() {
		$property = new PropertyId( 'P7' );
		$statement = new Statement( new PropertyNoValueSnak( $property ) );
		$item = new Item( new ItemId( 'Q23' ) );

		$changeOp = $this->newClaimsChangeOpDeserializer()->createEntityChangeOp(
			[ 'claims' => [ $this->getStatementSerializer()->serialize( $statement ) ] ]
		);

		$changeOp->apply( $item, new Summary() );
		$this->assertFalse(
			$item->getStatements()->getByPropertyId( $property )->isEmpty()
		);
	}

	public function testGivenRemoveChangeRequest_removesStatement() {
		$property = new PropertyId( 'P7' );
		$statement = new Statement( new PropertyNoValueSnak( $property ) );
		$statement->setGuid( 'some-guid-value' );
		$item = new Item( new ItemId( 'Q23' ) );
		$item->setStatements( new StatementList( [ $statement ] ) );

		$changeOp = $this->newClaimsChangeOpDeserializer()->createEntityChangeOp(
			[ 'claims' => [
				[ 'remove' => '', 'id' => $statement->getGuid() ]
			] ]
		);
		$changeOp->apply( $item, new Summary() );
		$this->assertTrue( $item->getStatements()->getByPropertyId( $property )->isEmpty() );
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
