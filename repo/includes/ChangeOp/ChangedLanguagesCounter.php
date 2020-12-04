<?php

namespace Wikibase\Repo\ChangeOp;

/**
 * Counts distinct languages of changed parts in {@link ChangeOpResult} tree
 * @license GPL-2.0-or-later
 */
class ChangedLanguagesCounter {

	public const ZERO_EDIT = 0;
	public const SHORTENED_SUMMARY_MAX_EDIT = 50;

	/**
	 * @param ChangeOpResult $changeOpResult
	 *
	 * @return int count of distinct languages of language-bound results that changed the entity
	 */
	public function countChangedLanguages( ChangeOpResult $changeOpResult ): int {
		$changedLanguagesCollector = new ChangedLanguagesCollector();
		$uniqueChangedLanguages = $changedLanguagesCollector->collectChangedLanguages( $changeOpResult );

		return count( $uniqueChangedLanguages );
	}

}
