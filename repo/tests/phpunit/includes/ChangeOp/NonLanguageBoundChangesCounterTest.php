<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\ChangeOp\ChangeOpResult;
use Wikibase\Repo\ChangeOp\ChangeOpsResult;
use Wikibase\Repo\ChangeOp\NonLanguageBoundChangesCounter;

/**
 * @covers \Wikibase\Repo\ChangeOp\NonLanguageBoundChangesCounter
 *
 * @group Wikibase
 * @group ChangeOp
 * @license GPL-2.0-or-later
 */
class NonLanguageBoundChangesCounterTest extends \PHPUnit\Framework\TestCase {

	public function changeOpResultsAndCountsProvider() {
		$entityId = new ItemId( 'Q123' );
		return [
			'Entity changed' => [
				new ChangeOpResultStub( $entityId, true ),
				1,
			],
			'Entity did not change' => [
				new ChangeOpResultStub( $entityId, false ),
				0,
			],
			'Changes on different tree levels' => [
				new ChangeOpsResult( $entityId, [
					new ChangeOpsResult( $entityId, [
						// should count this one
						new ChangeOpResultStub( $entityId, true ),
						new ChangeOpResultStub( $entityId, false ),
						new LanguageBoundChangeOpResultStub( $entityId, true, 'en' ),
						new LanguageBoundChangeOpResultStub( $entityId, false, 'en' ),
					] ),
					// and count this one too
					new ChangeOpResultStub( $entityId, true ),
					new ChangeOpResultStub( $entityId, false ),
					new LanguageBoundChangeOpResultStub( $entityId, true, 'en' ),
					new LanguageBoundChangeOpResultStub( $entityId, false, 'en' ),
				] ),
				2,
			],
		];
	}

	/**
	 * @dataProvider changeOpResultsAndCountsProvider
	 */
	public function testCountChangedLanguages( ChangeOpResult $result, $expectedCount ) {
		$languageUnboundChangesCounter = new NonLanguageBoundChangesCounter();

		$actualCount = $languageUnboundChangesCounter->countChanges( $result );

		$this->assertEquals( $expectedCount, $actualCount );
	}

}
