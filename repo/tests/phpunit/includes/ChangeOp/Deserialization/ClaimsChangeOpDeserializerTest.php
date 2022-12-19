<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\ChangeOp\Deserialization\ClaimsChangeOpDeserializer;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\ChangeOp\Deserialization\ClaimsChangeOpDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ClaimsChangeOpDeserializerTest extends \PHPUnit\Framework\TestCase {

	use ClaimsChangeOpDeserializationTester;

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
					'claims' => [ [ 'remove' => '' ] ],
				] );
			},
			'invalid-claim'
		);
	}

	public function testGivenClaimsDoesNotContainStatementSerialization_throwsException() {
		ChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function() {
				$this->newClaimsChangeOpDeserializer()->createEntityChangeOp( [
					'claims' => [ [ 'foo' ] ],
				] );
			},
			'invalid-claim'
		);
	}

	private function newClaimsChangeOpDeserializer() {
		return new ClaimsChangeOpDeserializer(
			WikibaseRepo::getExternalFormatStatementDeserializer(),
			WikibaseRepo::getChangeOpFactoryProvider()
				->getStatementChangeOpFactory()
		);
	}

	public function getChangeOpDeserializer() {
		return $this->newClaimsChangeOpDeserializer();
	}

	public function getEntity() {
		return new Item( new ItemId( 'Q123' ) );
	}

}
