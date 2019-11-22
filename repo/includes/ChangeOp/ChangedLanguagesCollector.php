<?php

namespace Wikibase\Repo\ChangeOp;

/**
 * Collect distinct languages of changed parts in {@link ChangeOpResult} tree
 * @license GPL-2.0-or-later
 */
class ChangedLanguagesCollector {

	use ChangeOpResultTraversal;

	/**
	 * @param ChangeOpResult $changeOpResult
	 * @return string[]
	 */
	public function collectChangedLanguages( ChangeOpResult $changeOpResult ): array {
		$languagesAsKeys = [];
		$traversable = $this->makeRecursiveTraversable( $changeOpResult );

		foreach ( $traversable as $result ) {
			if ( $result instanceof LanguageBoundChangeOpResult && $result->isEntityChanged() ) {
				$languagesAsKeys[$result->getLanguageCode()] = 1;
			}
		}

		return array_keys( $languagesAsKeys );
	}
}
