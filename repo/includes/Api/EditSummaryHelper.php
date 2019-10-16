<?php

namespace Wikibase\Repo\Api;

use Wikibase\Summary;
use Wikibase\Repo\ChangeOp\ChangedLanguagesCounter;
use Wikibase\Repo\ChangeOp\ChangeOpResult;
use Wikibase\Repo\ChangeOp\NonLanguageBoundChangesCounter;

/**
 * Helper methods for preparing summary instance for editing entity activity
 */
class EditSummaryHelper {

	/** @var ChangedLanguagesCounter */
	private $changedLanguagesCounter;

	/** @var NonLanguageBoundChangesCounter */
	private $nonLanguageBoundChangesCounter;

	public function __construct(
		ChangedLanguagesCounter $changedLanguagesCounter,
		NonLanguageBoundChangesCounter $nonLanguageBoundChangesCounter
	) {
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

		if ( $changedLanguagesCount === 0 ) {
			$summary->setAction( 'update' );
		} else {
			$nonLanguageBoundChangesCount = $this->nonLanguageBoundChangesCounter->countChanges( $changeOpResult );

			$action = $nonLanguageBoundChangesCount > 0 ? 'update-languages-and-other' : 'update-languages';

			$summary->setAutoCommentArgs( [ $changedLanguagesCount ] );
			$summary->setAction( $action );
		}
	}
}
