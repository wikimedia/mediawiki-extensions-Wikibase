<?php

namespace Wikibase\Repo\Api;

use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\ChangeOpResult;
use Wikibase\Repo\ChangeOp\ChangeOpResultTraversal;
use Wikibase\Repo\ChangeOp\ChangeOpsResult;
use Wikibase\Repo\ChangeOp\LanguageBoundChangeOpResult;

/**
 * Helper methods for preparing summary instance for editing entity activity
 * @license GPL-2.0-or-later
 */
class EditSummaryHelper {

	use ChangeOpResultTraversal;

	public const SHORTENED_SUMMARY_MAX_CHANGED_LANGUAGES = 50;

	/**
	 * Prepares edit summaries with appropriate action and comment args
	 * based on what has changed on the entity.
	 *
	 * @param Summary $summary
	 * @param ChangeOpResult $changeOpResult
	 * @return void
	 */
	public function prepareEditSummary( Summary $summary, ChangeOpResult $changeOpResult ) {
		$changedLanguagesAsKeys = [];
		$nonLanguageBoundChangesCount = 0;

		foreach ( $this->makeRecursiveTraversable( $changeOpResult ) as $result ) {
			if ( !$result->isEntityChanged() ) {
				continue;
			}
			if ( $result instanceof LanguageBoundChangeOpResult ) {
				$changedLanguagesAsKeys[$result->getLanguageCode()] = 1;
			} elseif ( !$result instanceof ChangeOpsResult ) {
				$nonLanguageBoundChangesCount += 1;
			}
		}

		$changedLanguagesCount = count( $changedLanguagesAsKeys );

		if ( $changedLanguagesCount === 0 ) {
			$action = 'update';
		} elseif ( $changedLanguagesCount <= self::SHORTENED_SUMMARY_MAX_CHANGED_LANGUAGES ) {
			$action = $nonLanguageBoundChangesCount > 0 ? 'update-languages-and-other-short' : 'update-languages-short';

			$summary->setAutoCommentArgs( [ array_keys( $changedLanguagesAsKeys ) ] );
		} else {
			$action = $nonLanguageBoundChangesCount > 0 ? 'update-languages-and-other' : 'update-languages';

			$summary->setAutoCommentArgs( [ $changedLanguagesCount ] );
		}
		$summary->setAction( $action );
	}
}
