<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\ChangeOp\ChangedLanguagesCounter;
use Wikibase\Repo\ChangeOp\ChangeOpResult;
use Wikibase\Repo\ChangeOp\ChangeOpsResult;

/**
 * @covers \Wikibase\Repo\ChangeOp\ChangedLanguagesCounter
 *
 * @group Wikibase
 * @group ChangeOp
 * @license GPL-2.0-or-later
 */
class ChangedLanguagesCounterTest extends \PHPUnit\Framework\TestCase {

	public function changeOpResultsProvider() {
		$entityId = new ItemId( 'Q123' );
		return [
			'Entity changed' => [
				new LanguageBoundChangeOpResultStub( $entityId, true, 'en' ),
				1,
			],
			'Entity did not change' => [
				new LanguageBoundChangeOpResultStub( $entityId, false, 'en' ),
				0,
			],
			'Multiple changes in same language' => [
				new ChangeOpsResult( $entityId, [
					new LanguageBoundChangeOpResultStub( $entityId, true, 'en' ),
					new LanguageBoundChangeOpResultStub( $entityId, true, 'en' ),
				] ),
				1,
			],
		];
	}

	/**
	 * @dataProvider changeOpResultsProvider
	 */
	public function testCountChangedLanguages( ChangeOpResult $result, $expectedLanguages ) {
		$changedLanguagesCollector = new ChangedLanguagesCounter();

		$actualLanguages = $changedLanguagesCollector->countChangedLanguages( $result );

		$this->assertEquals( $expectedLanguages, $actualLanguages );
	}

}
