<?php

namespace Wikibase;

/**
 * A Summary object can be used to build complex, translatable summaries.
 *
 * @since 0.1, major refactoring in 0.4
 *
 * @license GPL-2.0+
 * @author John Erling Blad
 * @author Daniel Kinzler
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class Summary {

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
	 * @var string
	 */
	private $userSummary;

	/**
	 * @since 0.4
	 *
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
	 * Set the user provided edit summary
	 *
	 * @since 0.4
	 *
	 * @param string|null $summary edit summary provided by the user
	 */
	public function setUserSummary( $summary = null ) {
		$this->userSummary = $summary === null ? null : (string)$summary;
	}

	/**
	 * Set the language code to use as the second autocomment argument
	 *
	 * @since 0.4
	 *
	 * @param string|null $languageCode
	 */
	public function setLanguage( $languageCode = null ) {
		$this->languageCode = $languageCode === null ? null : (string)$languageCode;
	}

	/**
	 * Set the module part of the autocomment
	 *
	 * @since 0.4
	 *
	 * @param string $name
	 */
	public function setModuleName( $name ) {
		$this->moduleName = (string)$name;
	}

	/**
	 * Get the module part of the autocomment
	 *
	 * @since 0.4
	 *
	 * @return string|null
	 */
	public function getModuleName() {
		return $this->moduleName;
	}

	/**
	 * Set the action part of the autocomment
	 *
	 * @since 0.4
	 *
	 * @param string|null $name
	 */
	public function setAction( $name ) {
		$this->actionName = $name === null ? null : (string)$name;
	}

	/**
	 * Get the action part of the autocomment
	 *
	 * @since 0.4
	 *
	 * @return string|null
	 */
	public function getActionName() {
		return $this->actionName;
	}

	/**
	 * Get the user-provided edit summary
	 *
	 * @since 0.4
	 *
	 * @return string|null
	 */
	public function getUserSummary() {
		return $this->userSummary;
	}

	/**
	 * Get the language part of the autocomment
	 *
	 * @since 0.4
	 *
	 * @return string|null
	 */
	public function getLanguageCode() {
		return $this->languageCode;
	}

	/**
	 * Format the message key using the object-specific values
	 *
	 * @since 0.3
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
	 * @since 0.4
	 *
	 * @param mixed $args,... Parts to be stringed together
	 */
	public function addAutoCommentArgs( $args /*...*/ ) {
		if ( !is_array( $args ) ) {
			$args = func_get_args();
		}

		$this->commentArgs = array_merge( $this->commentArgs, $args );
	}

	/**
	 * Add arguments to the summary part.
	 *
	 * @since 0.4
	 *
	 * @param mixed $args,... Parts to be stringed together
	 */
	public function addAutoSummaryArgs( $args /*...*/ ) {
		if ( !is_array( $args ) ) {
			$args = func_get_args();
		}

		$this->summaryArgs = array_merge( $this->summaryArgs, $args );
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
