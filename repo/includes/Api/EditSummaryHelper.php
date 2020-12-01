<?php

namespace Wikibase\Repo\Api;

use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\ChangedLanguagesCollector;
use Wikibase\Repo\ChangeOp\ChangedLanguagesCounter;
use Wikibase\Repo\ChangeOp\ChangeOpResult;
use Wikibase\Repo\ChangeOp\NonLanguageBoundChangesCounter;

/**
 * Helper methods for preparing summary instance for editing entity activity
 * @license GPL-2.0-or-later
 */
class EditSummaryHelper {

	/** @var ChangedLanguagesCounter */
	private $changedLanguagesCounter;

	/** @var ChangedLanguagesCollector */
	private $changedLanguagesCollector;

	/** @var NonLanguageBoundChangesCounter */
	private $nonLanguageBoundChangesCounter;

	public function __construct(
		ChangedLanguagesCollector $changedLanguagesCollector,
		ChangedLanguagesCounter $changedLanguagesCounter,
		NonLanguageBoundChangesCounter $nonLanguageBoundChangesCounter
	) {
		$this->changedLanguagesCollector = $changedLanguagesCollector;
		$this->changedLanguagesCounter = $changedLanguagesCounter;
		$this->nonLanguageBoundChangesCounter = $nonLanguageBoundChangesCounter;
	}

	/**
	 * Prepares edit summaries with appropriate action and comment args
	 * based on what has changed on the entity.
	 *
	 * @param  Summary $summary
	 * @param  ChangeOpResult $changeOpResult
	 * @return void
	 */
	public function prepareEditSummary( Summary $summary, ChangeOpResult $changeOpResult ) {
		$changedLanguagesCount = $this->changedLanguagesCounter->countChangedLanguages( $changeOpResult );
		$nonLanguageBoundChangesCount = $this->nonLanguageBoundChangesCounter->countChanges( $changeOpResult );

		if ( $changedLanguagesCount === $this->changedLanguagesCounter::ZERO_EDIT ) {
			$action = 'update';
		} elseif ( $changedLanguagesCount <= $this->changedLanguagesCounter::SHORTENED_SUMMARY_MAX_EDIT ) {
			$action = $nonLanguageBoundChangesCount > 0 ? 'update-languages-and-other-short' : 'update-languages-short';

			$collectChangedLanguages = $this->changedLanguagesCollector->collectChangedLanguages( $changeOpResult );

			$summary->setAutoCommentArgs( [ $collectChangedLanguages ] );
		} else {
			$action = $nonLanguageBoundChangesCount > 0 ? 'update-languages-and-other' : 'update-languages';

			$summary->setAutoCommentArgs( [ $changedLanguagesCount ] );
		}
		$summary->setAction( $action );
	}
}
