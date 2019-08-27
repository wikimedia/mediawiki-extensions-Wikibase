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
	public function countChangedLanguages( ChangeOpResult $changeOpResult ) {
		$uniqueChangedLanguages = $this->collectUniqueChangedLanguages( $changeOpResult );

		return count( $uniqueChangedLanguages );
	}

	private function collectUniqueChangedLanguages( ChangeOpResult $changeOpResult ) {
		$languagesAsKeys = [];
		$changeOpResultsTraversal = new ChangeOpResultTraversal();
		$traversable = $changeOpResultsTraversal->makeRecursiveTraversable( $changeOpResult );

		foreach ( $traversable as $result ) {
			if ( $result instanceof LanguageBoundChangeOpResult && $result->isEntityChanged() ) {
				$languagesAsKeys[$result->getLanguageCode()] = 1;
			}
		}

		return $languagesAsKeys;
	}

}
