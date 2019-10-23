<?php

namespace Wikibase\Repo\Tests\Api;

use Wikibase\Summary;
use Wikibase\Repo\Api\EditSummaryHelper;
use Wikibase\Repo\ChangeOp\ChangedLanguagesCounter;
use Wikibase\Repo\ChangeOp\DummyChangeOpResult;
use Wikibase\Repo\ChangeOp\NonLanguageBoundChangesCounter;

/**
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 *
 * @covers \Wikibase\Repo\Api\EditSummaryHelper
 */
class EditSummaryHelperTest extends \PHPUnit\Framework\TestCase {
	public function provideChangeOpResultsForPrepareEditSummary() {
		return [
			'no terms changed' => [
				'changedLanguagesCount' => 0,
				'nonLanguageBoundChangsCount' => 1,
				'expectedAction' => 'update',
				'expectedAutoCommentArgs' => []
			],
			'only terms changed' => [
				'changedLanguagesCount' => 1,
				'nonLanguageBoundChangsCount' => 0,
				'expectedAction' => 'update-languages',
				'expectedAutoCommentArgs' => [ 1 ]
			],
			'terms and other parts changed' => [
				'changedLanguagesCount' => 1,
				'nonLanguageBoundChangsCount' => 1,
				'expectedAction' => 'update-languages-and-other',
				'expectedAutoCommentArgs' => [ 1 ]
			]
		];
	}

	/**
	 * @dataProvider provideChangeOpResultsForPrepareEditSummary
	 */
	public function testPrepareEditSummary(
		$changedLanguagesCount,
		$nonLanguageBoundChangsCount,
		$expectedAction,
		$expectedAutoCommentArgs
	) {
		$summary = new Summary();

		$editSummaryHelper = $this->newEditSummaryHelper( $changedLanguagesCount, $nonLanguageBoundChangsCount );
		$editSummaryHelper->prepareEditSummary( $summary, new DummyChangeOpResult() );

		$this->assertEquals( $expectedAction, $summary->getMessageKey() );
		$this->assertEquals( $expectedAutoCommentArgs, $summary->getCommentArgs() );
	}

	private function newEditSummaryHelper( $changedLanguagesCount, $nonLanguageBoundChangsCount ) {
		$mockedChangedLanguagesCounter = $this->createMock( ChangedLanguagesCounter::class );
		$mockedChangedLanguagesCounter->method( 'countChangedLanguages' )->willReturn( $changedLanguagesCount );

		$mockedNonLanguageBoundChangesCounter = $this->createMock( NonLanguageBoundChangesCounter::class );
		$mockedNonLanguageBoundChangesCounter->method( 'countChanges' )->willReturn( $nonLanguageBoundChangsCount );

		return new EditSummaryHelper(
			$mockedChangedLanguagesCounter,
			$mockedNonLanguageBoundChangesCounter
		);
	}
}
