<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiResult;
use Exception;
use InvalidArgumentException;
use Language;
use LogicException;
use Message;
use Status;
use UsageException;
use Wikibase\Repo\Localizer\ExceptionLocalizer;

/**
 * ApiErrorReporter is a component for API modules that handles
 * error reporting. It supports localization of error messages.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class ApiErrorReporter {

	/**
	 * @var ApiBase
	 */
	private $apiModule;

	/**
	 * @var ExceptionLocalizer
	 */
	private $localizer;

	/**
	 * @var Language
	 */
	private $language;

	/**
	 * @param ApiBase $apiModule the API module for collaboration
	 * @param ExceptionLocalizer $localizer
	 * @param Language $language
	 */
	public function __construct(
		ApiBase $apiModule,
		ExceptionLocalizer $localizer,
		Language $language
	) {
		$this->apiModule = $apiModule;
		$this->localizer = $localizer;
		$this->language = $language;
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
			$warnings = $this->convertMessagesToResult( $warnings );
			$this->setWarning( 'messages', $warnings );
		}
	}

	/**
	 * Set warning section for this module.
	 *
	 * @param string $key
	 * @param string|array $warningData Warning message
	 */
	private function setWarning( $key, $warningData ) {
		$result = $this->apiModule->getResult();
		$moduleName = $this->apiModule->getModuleName();

		$result->addValue(
			array( 'warnings', $moduleName ),
			$key,
			$warningData,
			ApiResult::NO_SIZE_CHECK
		);
	}

	/**
	 * Aborts the request with an error based on the given (fatal) Status.
	 * This is intended as an alternative for ApiBase::dieUsage().
	 *
	 * If possible, a localized error message based on the exception is
	 * included in the error sent to the client. Localization of errors
	 * is attempted using the ExceptionLocalizer service provided to the
	 * constructor. If that fails, dieUsage() is called, which in turn
	 * attempts localization based on the error code.
	 *
	 * @see ApiBase::dieUsage()
	 *
	 * @param Status $status The status to report. $status->getMessage() will be used
	 * to generate the error's free form description.
	 * @param string $errorCode A code identifying the error.
	 * @param int $httpRespCode The HTTP error code to send to the client
	 * @param array|null $extradata Any extra data to include in the error report
	 *
	 * @throws UsageException
	 * @throws LogicException
	 */
	public function dieStatus( Status $status, $errorCode, $httpRespCode = 0, $extradata = [] ) {
		if ( $status->isOK() ) {
			throw new InvalidArgumentException( 'called dieStatus() with a non-fatal Status!' );
		}

		$this->addStatusToResult( $status, $extradata );

		//XXX: when to prefer $statusCode over $errorCode?
		list( , $description ) = $this->apiModule->getErrorFromStatus( $status );

		$this->throwUsageException( $description, $errorCode, $httpRespCode, $extradata );

		throw new LogicException( 'UsageException not thrown' );
	}

	/**
	 * Aborts the request with an error based on the given Exception.
	 * This is intended as an alternative for ApiBase::dieUsage().
	 *
	 * If possible, a localized error message based on the exception is
	 * included in the error sent to the client. Localization of errors
	 * is attempted using the ExceptionLocalizer service provided to the
	 * constructor. If that fails, dieUsage() is called, which in turn
	 * attempts localization based on the error code.
	 *
	 * @see ApiBase::dieUsage()
	 *
	 * @param Exception $ex The exception to report. $ex->getMessage() will be used as the error's
	 * free form description.
	 * @param string $errorCode A code identifying the error.
	 * @param int $httpRespCode The HTTP error code to send to the client
	 * @param array|null $extradata Any extra data to include in the error report
	 *
	 * @throws UsageException
	 * @throws LogicException
	 */
	public function dieException( Exception $ex, $errorCode, $httpRespCode = 0, $extradata = [] ) {
		if ( $this->localizer->hasExceptionMessage( $ex ) ) {
			$message = $this->localizer->getExceptionMessage( $ex );
			$key = $message->getKey();

			// NOTE: Ignore generic error messages, rely on the code instead!
			// XXX: No better way to do this?
			if ( $key !== 'wikibase-error-unexpected' ) {
				$this->dieMessageObject( $message, $errorCode, $httpRespCode, $extradata );
			}
		}

		$this->dieError( $ex->getMessage(), $errorCode, $httpRespCode, $extradata );

		throw new LogicException( 'UsageException not thrown' );
	}

	/**
	 * Aborts the request with an error message derived from the error code.
	 *
	 * @param string $errorCode A code identifying the error.
	 * @param string [$param,...] Parameters for the Message.
	 *
	 * @throws UsageException
	 * @throws LogicException
	 */
	public function dieMessage( $errorCode /*...*/ ) {
		$messageName = "wikibase-api-$errorCode";
		$params = func_get_args();
		array_shift( $params );
		$message = wfMessage( $messageName, $params );

		if ( !$message->exists() ) {
			// TODO: log warning
			// TODO: replace with generic message
		}

		$this->dieMessageObject( $message, $errorCode );

		throw new LogicException( 'UsageException not thrown' );
	}

	/**
	 * Aborts the request with an error message. The given message is included in
	 * the error's extra data.
	 *
	 * @see ApiBase::dieUsage()
	 *
	 * @param Message $message The error message. Will be used to generate the free form description
	 * of the error (as plain text in the content language) and included in the extra data (as
	 * HTML in the user's language, and as a data structure including the message key and
	 * parameters).
	 * @param string $errorCode A code identifying the error.
	 * @param int $httpRespCode The HTTP error code to send to the client
	 * @param array|null $extradata Any extra data to include in the error report
	 *
	 * @throws UsageException
	 * @throws LogicException
	 */
	private function dieMessageObject( Message $message, $errorCode, $httpRespCode = 0, $extradata = [] ) {
		$description = $message->inLanguage( 'en' )->useDatabase( false )->plain();

		$this->addMessageToResult( $message, $extradata );

		$this->throwUsageException( $description, $errorCode, $httpRespCode, $extradata );

		throw new LogicException( 'UsageException not thrown' );
	}

	/**
	 * Aborts the request with an error code. This is intended as a drop-in
	 * replacement for ApiBase::dieUsage().
	 *
	 * Localization of the error code is attempted by looking up a message key
	 * constructed using the given code in "wikibase-api-$errorCode". If such a message
	 * exists, it is included in the error's extra data.
	 *
	 * @see ApiBase::dieUsage()
	 *
	 * @param string $description An english, plain text description of the errror,
	 * for use in logs.
	 * @param string $errorCode A code identifying the error
	 * @param int $httpRespCode The HTTP error code to send to the client
	 * @param array|null $extradata Any extra data to include in the error report
	 *
	 * @throws UsageException
	 * @throws LogicException
	 */
	public function dieError( $description, $errorCode, $httpRespCode = 0, $extradata = [] ) {
		//TODO: try a reverse lookup in ApiBase::$messageMap
		$messageKey = "wikibase-api-$errorCode";
		$message = wfMessage( $messageKey );

		if ( $message->exists() ) {
			$this->addMessageToResult( $message, $extradata );

			$text = $message->inLanguage( 'en' )->useDatabase( false )->plain();

			if ( $description == '' ) {
				$description = $text;
			} else {
				$description = "$text ($description)";
			}
		}

		$this->throwUsageException( $description, $errorCode, $httpRespCode, $extradata );

		throw new LogicException( 'UsageException not thrown' );
	}

	/**
	 * Throws a UsageException by calling ApiBase::dieUsage().
	 *
	 * @see ApiBase::dieUsage()
	 *
	 * @param string $description
	 * @param string $errorCode
	 * @param int $httpRespCode
	 * @param null|array $extradata
	 *
	 * @throws UsageException
	 * @throws LogicException
	 */
	private function throwUsageException( $description, $errorCode, $httpRespCode = 0, $extradata = null ) {
		$this->apiModule->getStats()->increment( 'wikibase.repo.api.errors.total' );
		$this->apiModule->getMain()->dieUsage( $description, $errorCode, $httpRespCode, $extradata );

		throw new LogicException( 'UsageException not thrown' );
	}

	/**
	 * Add the given message to the $data array, for use in an error report.
	 *
	 * @param Message $message
	 * @param array|null &$data
	 *
	 * @throws InvalidArgumentException
	 */
	public function addMessageToResult( Message $message, &$data ) {
		if ( $data === null ) {
			$data = [];
		}

		if ( !is_array( $data ) ) {
			throw new InvalidArgumentException( '$extradata must be an array' );
		}

		$messageData = $this->convertMessageToResult( $message );

		$messageList = isset( $data['messages'] ) ? $data['messages'] : [];
		ApiResult::setIndexedTagName( $messageList, 'message' );

		$messageList[] = $messageData;
		ApiResult::setValue( $data, 'messages', $messageList, ApiResult::OVERRIDE );
	}

	/**
	 * Add the messages from the given Status object to the $data array,
	 * for use in an error report.
	 *
	 * @param Status $status
	 * @param array|null &$data
	 *
	 * @throws InvalidArgumentException
	 */
	public function addStatusToResult( Status $status, &$data ) {
		$messageSpecs = $status->getErrorsArray();
		$messages = $this->convertToMessageList( $messageSpecs );

		foreach ( $messages as $message ) {
			$this->addMessageToResult( $message, $data );
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
	 * @param $messageSpecs array a list of errors, as returned by Status::getErrorsByType()
	 *        or Title::getUserPermissionsErrors()
	 *
	 * @return array a result structure containing the messages from $errors as well as what
	 *         was already present in the $messages parameter.
	 */
	private function convertMessagesToResult( array $messageSpecs ) {
		$result = [];

		foreach ( $messageSpecs as $message ) {
			$type = null;

			if ( !( $message instanceof Message ) ) {
				if ( is_array( $message ) && isset( $message['type'] ) ) {
					$type = $message['type'];
				}

				$message = $this->convertToMessage( $message );
			}

			if ( !$message ) {
				continue;
			}

			$row = $this->convertMessageToResult( $message );

			if ( $type !== null ) {
				ApiResult::setValue( $row, 'type', $type );
			}

			$result[] = $row;
		}

		ApiResult::setIndexedTagName( $result, 'message' );
		return $result;
	}

	/**
	 * Utility method for building a list of Message objects from
	 * an array of message specs.
	 *
	 * @see convertToMessage()
	 *
	 * @param $messageSpecs array a list of errors, as returned by Status::getErrorsByType()
	 *        or Title::getUserPermissionsErrors().
	 *
	 * @return array a result structure containing the messages from $errors as well as what
	 *         was already present in the $messages parameter.
	 */
	private function convertToMessageList( array $messageSpecs ) {
		$messages = [];

		foreach ( $messageSpecs as $message ) {
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
	private function convertMessageToResult( Message $message ) {
		$name = $message->getKey();
		$params = $message->getParams();

		$row = [];
		ApiResult::setValue( $row, 'name', $name );

		ApiResult::setValue( $row, 'parameters', $params );
		ApiResult::setIndexedTagName( $row['parameters'], 'parameter' );

		$html = $message->inLanguage( $this->language )->useDatabase( true )->parse();
		ApiResult::setValue( $row, 'html', $html );
		$row[ApiResult::META_BC_SUBELEMENTS][] = 'html';

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
	 * @param string|array $messageSpec The message spec.
	 *
	 * @return Message|null
	 */
	private function convertToMessage( $messageSpec ) {
		$name = null;
		$params = null;

		if ( is_string( $messageSpec ) ) {
			// it's a plain string containing a message key
			$name = $messageSpec;
		} elseif ( is_array( $messageSpec ) ) {
			if ( isset( $messageSpec[0] ) ) {
				// it's an indexed array, the first entriy is the message key, the rest are paramters
				$name = $messageSpec[0];
				$params = array_slice( $messageSpec, 1 );
			} else {
				// it's an assoc array, find message key and params in fields.
				$params = isset( $messageSpec['params'] ) ? $messageSpec['params'] : null;

				if ( isset( $messageSpec['message'] ) ) {
					if ( $messageSpec['message'] instanceof Message ) {
						// message object found, done.
						return $messageSpec['message'];
					} else {
						// plain key and param list
						$name = strval( $messageSpec['message'] );
					}
				}
			}
		}

		if ( $name !== null ) {
			$message = wfMessage( $name );

			if ( !empty( $params ) ) {
				$message->params( $params );
			}

			return $message;
		}

		return null;
	}

}
