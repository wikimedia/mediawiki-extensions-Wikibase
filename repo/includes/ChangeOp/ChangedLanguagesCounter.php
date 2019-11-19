<?php

namespace Wikibase\Repo\ChangeOp;

/**
 * Counts distinct languages of changed parts in {@link ChangeOpResult} tree
 */
class ChangedLanguagesCounter {

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
