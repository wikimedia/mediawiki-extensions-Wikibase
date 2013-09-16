<?php

namespace Wikibase;

use Language;
use Wikibase\Repo\WikibaseRepo;

/**
 * A Summary object can be used to build complex, translatable summaries.
 *
 * @since 0.1, major refactoring in 0.4
 *
 * @file
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad
 * @author Daniel Kinzler
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class Summary {

	/**
	 * @var string
	 */
	protected $moduleName;

	/**
	 * @var string
	 */
	protected $actionName;

	/**
	 * @var Language
	 */
	protected $language;

	/**
	 * @var array
	 */
	protected $commentArgs;

	/**
	 * @var array
	 */
	protected $summaryArgs;

	/**
	 * @var string
	 */
	protected $userSummary;

	/**
	 * indicates a specific type of formatting
	 */
	const USE_COMMENT = 2;
	const USE_SUMMARY = 4;
	const USE_ALL = 6;

	/**
	 * Constructs a new Summary
	 *
	 * @since 0.4
	 *
	 * @param string     $moduleName  the module part of the autocomment
	 * @param string     $actionName  the action part of the autocomment
	 * @param string     $language    the language to use as the second autocomment argument
	 * @param object[]   $commentArgs the arguments to the autocomment
	 * @param object[]   $summaryArgs the arguments to the autosummary
	 */
	public function __construct( $moduleName = null, $actionName = null, $language = null, $commentArgs = array(), $summaryArgs = array() ) {
		$this->moduleName = $moduleName;
		$this->actionName = $actionName;
		$this->language = $language === null ? null : (string)$language;
		$this->commentArgs = $commentArgs;
		$this->summaryArgs = $summaryArgs;
	}

	/**
	 * Set the user provided edit summary
	 *
	 * @since 0.4
	 *
	 * @param string $summary edit summary provided by the user
	 */
	public function setUserSummary( $summary = null ) {
		$this->userSummary = $summary === null ? null : (string)$summary;
	}

	/**
	 * Set the language code to use as the second autocomment argument
	 *
	 * @since 0.4
	 *
	 * @param string $lang the language code
	 */
	public function setLanguage( $lang = null ) {
		$this->language = $lang === null ? null : (string)$lang;
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
	 * @return string
	 */
	public function getModuleName() {
		return $this->moduleName;
	}

	/**
	 * Set the action part of the autocomment
	 *
	 * @since 0.4
	 *
	 * @param string $name
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
		return $this->language;
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
	 * Add autocomment arguments.
	 *
	 * @since 0.4
	 *
	 * @param array|strings... parts to be stringed together
	 */
	public function addAutoCommentArgs( /*...*/ ) {
		$args = func_get_args();

		if ( isset( $args[0] ) && is_array( $args[0] ) ) {
			$args = $args[0];
		}

		$this->commentArgs = array_merge( $this->commentArgs, $args );
	}


	/**
	 * Add to the summary part
	 *
	 * @since 0.4
	 *
	 * @param array|strings... parts to be stringed together
	 */
	public function addAutoSummaryArgs( /*...*/ ) {
		$args = func_get_args();

		if ( isset( $args[0] ) && is_array( $args[0] ) ) {
			$args = $args[0];
		}

		$this->summaryArgs = array_merge( $this->summaryArgs, $args );
	}

	/**
	 * @return object[]
	 */
	public function getCommentArgs() {
		return $this->commentArgs;
	}

	/**
	 * @return object[]
	 */
	public function getAutoSummaryArgs() {
		return $this->summaryArgs;
	}

}
