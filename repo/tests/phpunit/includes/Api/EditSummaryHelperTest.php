<?php

namespace Wikibase\Repo\Tests\Api;

use Wikibase\Lib\Summary;
use Wikibase\Repo\Api\EditSummaryHelper;
use Wikibase\Repo\ChangeOp\ChangedLanguagesCollector;
use Wikibase\Repo\ChangeOp\ChangedLanguagesCounter;
use Wikibase\Repo\ChangeOp\DummyChangeOpResult;
use Wikibase\Repo\ChangeOp\NonLanguageBoundChangesCounter;

/**
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 *
 * @covers \Wikibase\Repo\Api\EditSummaryHelper
 * @license GPL-2.0-or-later
 */
class EditSummaryHelperTest extends \PHPUnit\Framework\TestCase {
	public function provideChangeOpResultsForPrepareEditSummary() {
		return [
			'no terms changed' => [
				'collectChangedLanguages' => [],
				'changedLanguagesCount' => 0,
				'nonLanguageBoundChangesCount' => 1,
				'expectedAction' => 'update',
				'expectedAutoCommentArgs' => [],
			],
			'only terms changed in less than 50 languages' => [
				'collectChangedLanguages' => [ 'en' ],
				'changedLanguagesCount' => 1,
				'nonLanguageBoundChangesCount' => 0,
				'expectedAction' => 'update-languages-short',
				'expectedAutoCommentArgs' => [ [ 'en' ] ],
			],
			' terms in less than 50 languages and other parts changed' => [
				'collectChangedLanguages' => [ 'fr' ],
				'changedLanguagesCount' => 1,
				'nonLanguageBoundChangesCount' => 1,
				'expectedAction' => 'update-languages-and-other-short',
				'expectedAutoCommentArgs' => [ [ 'fr' ] ],
			],
			'terms in more than 50 languages changed' => [
				'collectChangedLanguages' => [ 'en', '...' ],
				'changedLanguagesCount' => 51,
				'nonLanguageBoundChangesCount' => 0,
				'expectedAction' => 'update-languages',
				'expectedAutoCommentArgs' => [ 51 ],
			],
			'terms  in more than 50 languages and other parts changed' => [
				'collectChangedLanguages' => [ 'en', '...' ],
				'changedLanguagesCount' => 51,
				'nonLanguageBoundChangesCount' => 3,
				'expectedAction' => 'update-languages-and-other',
				'expectedAutoCommentArgs' => [ 51 ],
			],
		];
	}

	/**
	 * @dataProvider provideChangeOpResultsForPrepareEditSummary
	 */
	public function testPrepareEditSummary(
		$collectChangedLanguages,
		$changedLanguagesCount,
		$nonLanguageBoundChangsCount,
		$expectedAction,
		$expectedAutoCommentArgs
	) {
		$summary = new Summary();

		$editSummaryHelper = $this->newEditSummaryHelper( $collectChangedLanguages, $changedLanguagesCount, $nonLanguageBoundChangsCount );
		$editSummaryHelper->prepareEditSummary( $summary, new DummyChangeOpResult() );

		$this->assertEquals( $expectedAction, $summary->getMessageKey() );
		$this->assertEquals( $expectedAutoCommentArgs, $summary->getCommentArgs() );
	}

	private function newEditSummaryHelper( $collectChangedLanguages, $changedLanguagesCount, $nonLanguageBoundChangesCount ) {
		$mockedChangedLanguagesCounter = $this->createMock( ChangedLanguagesCounter::class );
		$mockedChangedLanguagesCounter->method( 'countChangedLanguages' )->willReturn( $changedLanguagesCount );

		$mockedChangedLanguagesCollector = $this->createMock( ChangedLanguagesCollector::class );
		$mockedChangedLanguagesCollector->method( 'collectChangedLanguages' )->willReturn( $collectChangedLanguages );

		$mockedNonLanguageBoundChangesCounter = $this->createMock( NonLanguageBoundChangesCounter::class );
		$mockedNonLanguageBoundChangesCounter->method( 'countChanges' )->willReturn( $nonLanguageBoundChangesCount );

		return new EditSummaryHelper(
			$mockedChangedLanguagesCollector,
			$mockedChangedLanguagesCounter,
			$mockedNonLanguageBoundChangesCounter
		);
	}
}
