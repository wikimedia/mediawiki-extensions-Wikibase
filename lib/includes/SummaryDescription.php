<?php

namespace Wikibase;

interface SummaryDescription {

	/**
	 * Format the message key using the object-specific values
	 *
	 * @note When displayed, message key will be prefixed with either with
	 *       "wikibase-<entity_type>-summary" then with "wikibase-entity-summary".
	 *       Displayed message will be the first one that exists.
	 *
	 * @note When displaying to the user, arguments to the target message will be:
	 *         - $1: number of AutoSummaryArgs
	 *         - $2: language code (empty if no language code is provided in summary)
	 *         - $3: CommentArg with index 0 (if present)
	 *         - ...
	 *		   - $n: CommentArg with index n-3 (if present)
	 *
	 * @return string with a non-prefixed message key
	 */
	public function getMessageKey();

	/**
	 * Comment args will be used as parameters for the message.
	 *
	 * @note Element with index 0 in comment args will be $3 in the message
	 *
	 * @return array
	 */
	public function getCommentArgs();

	/**
	 * Get the user-provided edit summary
	 *
	 * @return string|null
	 */
	public function getUserSummary();

	/**
	 * Get the language part of the autocomment
	 *
	 * @note Will be passed as $2 argument to the message.
	 *
	 * @return string|null
	 */
	public function getLanguageCode();

	/**
	 * @return array Array or associative array of values that were changed in new revision
	 *
	 * @note Simple array will be displayed as comma separated list of values: e.g. "a, b".
	 *       Associative array will be displayed as comma separated list of values
	 *       prefixed with keys, e.g. "en: en-label, fr: fr-label"
	 */
	public function getAutoSummaryArgs();

}
