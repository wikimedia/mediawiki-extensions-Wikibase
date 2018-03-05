<?php

namespace Wikibase\Lib;

/**
 * Interface for consumers (typically a formatter) of auto-generated edit summary lines that
 * describe edits made on Wikibase entities. Wikibase edits are usually atomic, e.g. a single label
 * in a single language was added. The auto-summary describing this edit uses the message
 * "wikibase-entity-summary-wbsetlabel-add", and mentions the new label as well as it's language
 * code.
 *
 * @license GPL-2.0-or-later
 * @author Aleksey Bekh-Ivanov
 * @author Thiemo Kreuz
 */
interface FormatableSummary {

	/**
	 * Returns a fragment of a message key, missing the "wikibase-…-summary-" prefix. When displayed
	 * to the user, the message key will be prefixed with either "wikibase-<entity type>-summary-",
	 * or with "wikibase-entity-summary-" if no message specific to the entity type exists.
	 *
	 * Parameters of the message will be:
	 * - $1: Number of auto-summary arguments (@see getAutoSummaryArgs ) to be used for
	 *   {{PLURAL:$1|…|…}} support in the message. Note that the auto-summary arguments themselves
	 *   are not passed as parameters to the message, but appended.
	 * - $2: Language code of the edited content, or empty if not applicable
	 *   (@see getLanguageCode ).
	 * - $3 to $n: Comment arguments, if present (@see getCommentArgs ).
	 *
	 * @return string
	 */
	public function getMessageKey();

	/**
	 * The language of the content that was edited, e.g. when the summary represents an edit of a
	 * label, description, or set of aliases in a specific language. Not set if not applicable.
	 *
	 * Will be used as argument $2 in the message.
	 *
	 * @return string|null
	 */
	public function getLanguageCode();

	/**
	 * Comment arguments will be used as parameters for the message. The element with index 0 in the
	 * array will become $3 in the message, and so on.
	 *
	 * Elements in the array that are not strings will be forcefully converted to strings, utilizing
	 * proper formatters.
	 *
	 * @note Duplicate values in the comment as well as auto-summary arguments are not a mistake. If
	 * duplication makes sense depends on the edit, and how the message and the appended
	 * auto-summary can represent the edit.
	 *
	 * @return array
	 */
	public function getCommentArgs();

	/**
	 * An array or associative array of values to describe the new revision this summary represents,
	 * for example:
	 * - The actual label, description, or set of aliases that was set in an edit.
	 * - A formatted property ID => value pair.
	 * - The site IDs and titles of two pages that have been merged via the "wblinktitles" module.
	 *
	 * The auto-summary arguments are compiled to a string and form a plain, unlocalized,
	 * automatically generated summary that is appended to the message.
	 *
	 * A numerically indexed array will be displayed as a comma separated list of values, e.g.
	 * "a, b". An associative array will be displayed as a comma separated list of values prefixed
	 * with keys, e.g. "en: en-label, fr: fr-label".
	 *
	 * Elements in the array that are not strings will be forcefully converted to strings, utilizing
	 * proper formatters.
	 *
	 * @note Duplicate values in the comment as well as auto-summary arguments are not a mistake. If
	 * duplication makes sense depends on the edit, and how the message and the appended
	 * auto-summary can represent the edit.
	 *
	 * @return array
	 */
	public function getAutoSummaryArgs();

	/**
	 * The user-provided edit summary, or null if none was given. Typically provided via the API.
	 *
	 * If both a user-provided summary as well as auto-summary arguments are provided
	 * (@see getAutoSummaryArgs ), the user's comment will be appended to the auto-summary,
	 * separated by a comma.
	 *
	 * @return string|null
	 */
	public function getUserSummary();

}
