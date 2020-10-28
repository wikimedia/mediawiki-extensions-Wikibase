<?php

namespace Wikibase\Lib;

/**
 * A Summary object can be used to build complex, translatable summaries.
 *
 * @license GPL-2.0-or-later
 * @author John Erling Blad
 * @author Daniel Kinzler
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class Summary implements FormatableSummary {

	/**
	 * @var string|null
	 */
	private $moduleName;

	/**
	 * @var string|null
	 */
	private $actionName;

	/**
	 * @var string|null
	 */
	private $languageCode;

	/**
	 * @var array
	 */
	private $commentArgs;

	/**
	 * @var array
	 */
	private $summaryArgs;

	/**
	 * @var string|null The user-provided edit summary, or null if none was given.
	 */
	private $userSummary;

	/**
	 * @param string|null $moduleName The module part of the auto comment
	 * @param string|null $actionName The action part of the auto comment
	 * @param string|null $languageCode The language code to use as the second auto comment argument
	 * @param array $commentArgs The arguments to the auto comment
	 * @param array $summaryArgs The arguments to the auto summary
	 */
	public function __construct(
		$moduleName = null,
		$actionName = null,
		$languageCode = null,
		array $commentArgs = [],
		array $summaryArgs = []
	) {
		$this->moduleName = $moduleName;
		$this->actionName = $actionName;
		$this->languageCode = $languageCode === null ? null : (string)$languageCode;
		$this->commentArgs = $commentArgs;
		$this->summaryArgs = $summaryArgs;
	}

	/**
	 * @param string|null $summary The user-provided edit summary, or null if none was given.
	 */
	public function setUserSummary( $summary = null ) {
		$this->userSummary = $summary === null ? null : (string)$summary;
	}

	/**
	 * Set the language code to use as the second autocomment argument
	 *
	 * @param string|null $languageCode
	 */
	public function setLanguage( $languageCode = null ) {
		$this->languageCode = $languageCode === null ? null : (string)$languageCode;
	}

	/**
	 * Set the action part of the autocomment
	 *
	 * @param string|null $name
	 */
	public function setAction( $name ) {
		$this->actionName = $name === null ? null : (string)$name;
	}

	/**
	 * @return string|null The user-provided edit summary, or null if none was given.
	 */
	public function getUserSummary() {
		return $this->userSummary;
	}

	/**
	 * Get the language part of the autocomment
	 *
	 * @return string|null
	 */
	public function getLanguageCode() {
		return $this->languageCode;
	}

	/**
	 * Format the message key using the object-specific values
	 *
	 * @return string with a message key, or possibly an empty string
	 */
	public function getMessageKey() {
		if ( $this->moduleName === null || $this->moduleName === '' ) {
			return $this->actionName;
		} elseif ( $this->actionName === null || $this->actionName === '' ) {
			return $this->moduleName;
		} else {
			return $this->moduleName . '-' . $this->actionName;
		}
	}

	/**
	 * Add auto comment arguments.
	 *
	 * @param mixed ...$args Parts to be stringed together
	 */
	public function addAutoCommentArgs( ...$args ) {
		if ( isset( $args[0] ) && is_array( $args[0] ) ) {
			$args = $args[0];
		}

		$this->commentArgs = array_merge( $this->commentArgs, $args );
	}

	/**
	 * Add arguments to the summary part.
	 *
	 * @param mixed ...$args Parts to be stringed together
	 */
	public function addAutoSummaryArgs( ...$args ) {
		if ( isset( $args[0] ) && is_array( $args[0] ) ) {
			$args = $args[0];
		}

		$this->summaryArgs = array_merge( $this->summaryArgs, $args );
	}

	/**
	 * @param array $args Parts to be used in auto comment
	 */
	public function setAutoCommentArgs( array $args ) {
		$this->commentArgs = $args;
	}

	/**
	 * @param array $args Parts to be used in auto summary
	 */
	public function setAutoSummaryArgs( array $args ) {
		$this->summaryArgs = $args;
	}

	/**
	 * @return array
	 */
	public function getCommentArgs() {
		return $this->commentArgs;
	}

	/**
	 * @return array
	 */
	public function getAutoSummaryArgs() {
		return $this->summaryArgs;
	}

}
