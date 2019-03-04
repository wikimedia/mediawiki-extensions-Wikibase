<?php

/**
 * Minimal set of classes necessary to fulfill needs of parts of Wikibase relying on
 * the Echo extension.
 * @codingStandardsIgnoreFile
 */

class EchoAttributeManager {
	const ATTR_LOCATORS = 'user-locators';
}

class EchoEvent {
	/**
	 * @param array $info
	 * @return EchoEvent|bool
	 */
	public static function create( $info = [] ) {
	}

	/**
	 * @param string $key
	 * @param mixed|null $default
	 * @return mixed|null
	 */
	public function getExtraParam( $key, $default = null ) {
	}

	/**
	 * @param bool $fromMaster
	 * @return Title|null
	 */
	public function getTitle( $fromMaster = false ) {
	}

	/**
	 * @return string
	 */
	public function getType() {
	}
}

class EchoEventPresentationModel {
	/**
	 * @var EchoEvent
	 */
	protected $event;

	/**
	 * @var string
	 */
	protected $type;

	/**
	 * @return array|null
	 */
	final protected function getAgentLink() {
	}

	/**
	 * @param bool $includeCurrent
	 * @param callable $groupCallback
	 * @return int
	 */
	final protected function getBundleCount( $includeCurrent = true, $groupCallback = null ) {
	}

	/**
	 * @param string $key
	 * @return Message
	 */
	final protected function getMessageWithAgent( $key ) {
	}

	/**
	 * @param bool $includeCurrent
	 * @param callable $groupCallback
	 * @return int
	 */
	final protected function getNotificationCountForOutput( $includeCurrent = true, $groupCallback = null ) {
	}

	/**
	 * @return Message
	 */
	public function getSubjectMessage() {
	}

	/**
	 * @param Title $title
	 * @param bool $includeNamespace
	 * @return string
	 */
	protected function getTruncatedTitleText( Title $title, $includeNamespace = false ) {
	}

	/**
	 * @return string
	 */
	final protected function getViewingUserForGender() {
	}

	/**
	 * @param mixed ...$parameters
	 * @return Message
	 */
	protected function msg( ...$parameters ) {
	}
}

class EchoUserLocator {
}
