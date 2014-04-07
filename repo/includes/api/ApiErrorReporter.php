<?php

namespace Wikibase\Api;

use ApiBase;
use Exception;
use LogicException;
use Message;
use Status;
use Wikibase\i18n\ExceptionLocalizer;

/**
 * ApiErrorReporter is a component for API modules that handles
 * error reporting. It supports localization of error messages.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class ApiErrorReporter {

	/**
	 * @var ApiBase
	 */
	protected $apiModule;

	/**
	 * @var ExceptionLocalizer
	 */
	protected $localizer;

	/**
	 * @param ApiBase $apiModule the API module for collaboration
	 * @param ExceptionLocalizer $localizer
	 */
	public function __construct(
		ApiBase $apiModule,
		ExceptionLocalizer $localizer
	) {
		$this->apiModule = $apiModule;
		$this->localizer = $localizer;
	}
	/**
	 * Reports any warnings in the Status object on the warnings section
	 * of the result.
	 *
	 * @param Status $status
	 */
	public function reportStatusWarnings( Status $status ) {
		$warnings = $status->getWarningsArray();

		if ( !empty( $warnings ) ) {
			$warnigns = $this->convertMessagesToResult( $warnings );
			$this->setWarning( 'messages', $warnigns );
		}
	}

	/**
	 * Set warning section for this module.
	 *
	 * @param string $key
	 * @param string|array $warningData Warning message
	 */
	protected function setWarning( $key, $warningData ) {
		$result = $this->apiModule->getResult();
		$moduleName = $this->apiModule->getModuleName();

		$result->disableSizeCheck();
		$result->addValue( array( 'warnings', $moduleName ), $key, $warningData );
		$result->enableSizeCheck();
	}

	/**
	 * Aborts the request with an error based on the given (fatal) Status.
	 * This is intended as an alternative for ApiBase::dieUsage().
	 *
	 * If possible, a localized error message based on the exception is
	 * included in the error sent to the client. Localization of errors
	 * is attempted using the ExceptionLocalizer service provided to the
	 * constructor. If that fails, dieUSage() is called, which in turn
	 * attempts localization based on the error code.
	 *
	 * @see ApiBase::dieUsage()
	 *
	 * @param Status $status The status to report. $status->getMessage() will be used
	 * to generate the error's free form description.
	 * @param string $code A code identifying the error.
	 * @param int $httpStatusCode The HTTP error code to send to the client
	 * @param array|null $extradata Any extra data to include in the error report
	 *
	 * @throws LogicException
	 */
	public function dieStatus( Status $status, $code, $httpStatusCode = 0, $extradata = array() ) {
		if ( $status->isOK() ) {
			throw new \InvalidArgumentException( 'called dieStatus() with a non-fatal Status!' );
		}

		$this->addStatusToExtraData( $status, $extradata );

		$message = $status->getMessage();
		$description = $message->inLanguage( 'en' )->useDatabase( false )->plain();

		$this->throwUsageException( $description, $code, $httpStatusCode, $extradata );

		throw new LogicException( 'UsageException not thrown' );
	}

	/**
	 * Aborts the request with an error based on the given Exception.
	 * This is intended as an alternative for ApiBase::dieUsage().
	 *
	 * If possible, a localized error message based on the exception is
	 * included in the error sent to the client. Localization of errors
	 * is attempted using the ExceptionLocalizer service provided to the
	 * constructor. If that fails, dieUSage() is called, which in turn
	 * attempts localization based on the error code.
	 *
	 * @see ApiBase::dieUsage()
	 *
	 * @param Exception $ex The exception to report. $ex->getMessage() will be used as the error's
	 * free form description.
	 * @param string $code A code identifying the error.
	 * @param int $httpStatusCode The HTTP error code to send to the client
	 * @param array|null $extradata Any extra data to include in the error report
	 *
	 * @throws LogicException
	 */
	public function dieException( Exception $ex, $code, $httpStatusCode = 0, $extradata = array() ) {
		if ( $this->localizer->hasExceptionMessage( $ex ) ) {
			$message = $this->localizer->getExceptionMessage( $ex );
			$this->dieMessage( $message, $code, $httpStatusCode, $extradata );
		} else {
			$this->dieError( $ex->getMessage(), $code, $httpStatusCode, $extradata );
		}

		throw new LogicException( 'UsageException not thrown' );
	}

	/**
	 * Aborts the request with an error message. This is intended as an alternative
	 * for ApiBase::dieUsage(). The given message is included in the error's extra data.
	 *
	 * @see ApiBase::dieUsage()
	 *
	 * @param Message $message The error message. Will be used to generate the free form description
	 * of the error (as plain text in the content language) and included in the extra data (as
	 * HTML in the user's language, and as a data structure including the message key and
	 * parameters).
	 * @param string $code A code identifying the error. If not provided, the message key is used.
	 * @param int $httpStatusCode The HTTP error code to send to the client
	 * @param array|null $extradata Any extra data to include in the error report
	 *
	 * @throws LogicException
	 */
	public function dieMessage( Message $message, $code = null, $httpStatusCode = 0, $extradata = array() ) {
		if ( $code === null ) {
			$code = $message->getKey();
		}

		$description = $message->inLanguage( 'en' )->useDatabase( false )->plain();

		$this->addMessageToExtraData( $message, $extradata );
		$this->throwUsageException( $description, $code, $httpStatusCode, $extradata );

		throw new LogicException( 'UsageException not thrown' );
	}

	/**
	 * Aborts the request with an error code. This is intended as a drop-in
	 * replacement for ApiBase::dieUsage().
	 *
	 * Localization of the error code is attempted by looking up a message key
	 * constructed using the given code in "wikibase-error-$code". If such a message
	 * exists, it is included in the error's extra data.
	 *
	 * @see ApiBase::dieUsage()
	 *
	 * @param string $description An english, plain text description of the errror,
	 * for use in logs.
	 * @param string $code A code identifying the error
	 * @param int $httpStatusCode The HTTP error code to send to the client
	 * @param array|null $extradata Any extra data to include in the error report
	 *
	 * @throws LogicException
	 */
	public function dieError( $description, $code, $httpStatusCode = 0, $extradata = array() ) {
		$messageKey = "wikibase-error-$code";
		$message = wfMessage( $messageKey );

		if ( $message->exists() ) {
			$this->addMessageToExtraData( $message, $extra );
		}

		$this->throwUsageException( $description, $code, $httpStatusCode, $extradata );

		throw new LogicException( 'UsageException not thrown' );
	}

	/**
	 * Throws a UsageException by calling $this->apiModule->dieUsage().
	 *
	 * @see ApiBase::dieUsage()
	 *
	 * @param $description
	 * @param $code
	 * @param int $httpStatusCode
	 * @param null|array $extradata
	 *
	 * @throws \LogicException
	 */
	protected function throwUsageException( $description, $code, $httpStatusCode = 0, $extradata = null ) {
		$this->apiModule->dieUsage( $description, $code, $httpStatusCode, $extradata );

		throw new LogicException( 'UsageException not thrown' );
	}


	/**
	 * Add the given message to the $extradata array, for use in an error report.
	 *
	 * @param Message $message
	 * @param array &$extradata
	 */
	protected function addMessageToExtraData( Message $message, array &$extradata ) {
		$messageArray = $this->convertMessagesToResult( $message );

		$res = $this->apiModule->getResult();
		$res->setElement( $extradata, 'messages', $messageArray );
	}

	/**
	 * Add the messages from the given Status object to the $extradata array,
	 * for use in an error report.
	 *
	 * @param Status $status
	 * @param array &$extradata
	 */
	protected function addStatusToExtraData( Status $status, array &$extradata ) {
		$errors = $status->getErrorsArray();
		$errors = $this->convertToMessageList( $errors );

		foreach ( $errors as $message ) {
			$this->addMessageToExtraData( $message, $extradata );
		}
	}

	/**
	 * Utility method for compiling a list of messages into a form suitable for use
	 * in an API result structure.
	 *
	 * The $errors parameters is a list of (error) messages. Each entry in that array
	 * represents on message; the message can be represented as:
	 *
	 * * a message key, as a string
	 * * an indexed array with the message key as the first element, and the remaining elements
	 *   acting as message parameters
	 * * an associative array with the following fields:
	 *   - message: the message key (as a string); may also be a Message object, see below for that.
	 *   - params: a list of parameters (optional)
	 *   - type: the type of message (warning or error) (optional)
	 *   - html: an HTML rendering of the message (optional)
	 * * an associative array like above, but containing a Message object in the 'message' field.
	 *   In that case, the 'params' field is ignored and the parameter list is taken from the
	 *   Message object.
	 *
	 * This provides support for message lists coming from Status::getErrorsByType() as well as
	 * Title::getUserPermissionsErrors() etc.
	 *
	 * @param $errors array a list of errors, as returned by Status::getErrorsByType()
	 *        or Title::getUserPermissionsErrors()
	 *
	 * @return array a result structure containing the messages from $errors as well as what
	 *         was already present in the $messages parameter.
	 */
	protected function convertMessagesToResult( $errors ) {
		$messages = array();
		$res = $this->apiModule->getResult();

		foreach ( $errors as $message ) {
			$type = null;
			$name = null;
			$html = null;
			$params = null;

			if ( !( $message instanceof Message ) ) {
				if ( is_array( $message ) && isset( $message['type'] ) ) {
					$type = $message['type'];
				}

				$message = $this->convertToMessage( $message );
			}

			if ( !$message ) {
				continue;
			}

			$row = $this->convertMessagesToResult( $message );

			if ( $type !== null ) {
				$res->setElement( $row, 'type', $type );
			}

			$messages[] = $row;
		}

		$res->setIndexedTagName( $messages, 'message' );
		return $messages;
	}

	/**
	 * Utility method for building a list of Message objects from
	 * an array of message specs.
	 *
	 * @see convertToMessage()
	 *
	 * @param $errors array a list of errors, as returned by Status::getErrorsByType()
	 *        or Title::getUserPermissionsErrors().
	 *
	 * @return array a result structure containing the messages from $errors as well as what
	 *         was already present in the $messages parameter.
	 */
	protected function convertToMessageList( $errors ) {
		$messages = array();

		foreach ( $errors as $message ) {
			if ( !( $message instanceof Message ) ) {
				$message = $this->convertToMessage( $message );
			}

			if ( !$message ) {
				continue;
			}

			$messages[] = $message;
		}

		return $messages;
	}

	/**
	 * Constructs a result structure from the given Message
	 *
	 * @param Message $message
	 *
	 * @return array
	 */
	protected function convertMessageToResult( Message $message ) {
		$res = $this->apiModule->getResult();

		$name = $message->getKey();
		$params = $message->getParams();

		$row = array();
		$res->setElement( $row, 'name', $name );

		$res->setElement( $row, 'parameters', $params );
		$res->setIndexedTagName( $row['parameters'], 'parameter' );

		$html = $message->parse(); //XXX: force language?
		$res->setContent( $row, $html, 'html' );

		return $row;
	}

	/**
	 * Utility function for converting a message specified as a string or array
	 * to a Message object. Returns null if this is not possible.
	 *
	 * The formats supported by this method are the formats used by the Status class as well as
	 * the one used by Title::getUserPermissionsErrors().
	 *
	 * The spec may be structured as follows:
	 * * a message key, as a string
	 * * an indexed array with the message key as the first element, and the remaining elements
	 *   acting as message parameters
	 * * an associative array with the following fields:
	 *   - message: the message key (as a string); may also be a Message object, see below for that.
	 *   - params: a list of parameters (optional)
	 *   - type: the type of message (warning or error) (optional)
	 *   - html: an HTML rendering of the message (optional)
	 * * an associative array like above, but containing a Message object in the 'message' field.
	 *   In that case, the 'params' field is ignored and the parameter list is taken from the
	 *   Message object.
	 *
	 * @param string|array $m The message spec.
	 *
	 * @return Message|null
	 */
	protected function convertToMessage( $m ) {
		$name = null;
		$params = null;

		if ( is_string( $m ) ) {
			// it's a plain string containing a message key
			$name = $m;
		} elseif ( is_array( $m ) ) {
			if ( isset( $m[0]) ) {
				// it's an indexed array, the first entriy is the message key, the rest are paramters
				$name = $m[0];
				$params = array_slice( $m, 1 );
			} else{
				// it's an assoc array, find message key and params in fields.
				$params = isset( $m['params'] ) ? $m['params'] : null;

				if( isset( $m['message'] ) ) {
					if ( $m['message'] instanceof Message ) {
						// message object found, done.
						return $m['message'];
					} else {
						// plain key and param list
						$name = strval( $m['message'] );
					}
				}
			}
		}

		if ( $name !== null ) {
			$message = wfMessage( $name );

			if ( $params !== null && !empty( $params ) ) {
				$message->params( $params );
			}

			return $message;
		}

		return null;
	}

}
