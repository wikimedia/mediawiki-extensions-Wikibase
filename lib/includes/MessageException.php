<?php

/**
 * Should be moved to core!
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class MessageException extends Exception {

	/**
	 * @var Message
	 */
	protected $mwMessage;

	/**
	 * @param Message $mwMessage
	 * @param string $exceptionMsg (optional, default '')
	 * @param Exception $previous (optional, default null)
	 */
	public function __construct( Message $mwMessage, $exceptionMsg = '', Exception $previous = null ) {
		$exceptionMsg = $this->getMessageOrDefault( $mwMessage, $exceptionMsg );

		parent::__construct( $exceptionMsg, 0, $previous );

		$this->mwMessage = $mwMessage;
	}

	public function getMwMessage() {
		return $this->mwMessage;
	}

	/**
	 * @param Message $mwMessage
	 * @param string $exceptionMsg
	 *
	 * @return string
	 */
	private function getMessageOrDefault( $mwMessage, $exceptionMsg ) {
		if ( $exceptionMsg === '' ) {
			$exceptionMsg = $mwMessage->inLanguage( 'en' )->parse();
		}

		return $exceptionMsg;
	}

}
