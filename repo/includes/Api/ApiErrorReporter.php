<?php

namespace Wikibase\Repo\Api;

use Exception;
use InvalidArgumentException;
use LogicException;
use MediaWiki\Api\ApiBase;
use MediaWiki\Api\ApiErrorFormatter;
use MediaWiki\Api\ApiErrorFormatter_BackCompat;
use MediaWiki\Api\ApiMessage;
use MediaWiki\Api\ApiResult;
use MediaWiki\Api\ApiUsageException;
use StatusValue;
use Wikibase\Repo\Localizer\ExceptionLocalizer;
use Wikimedia\Message\MessageSpecifier;

/**
 * ApiErrorReporter is a component for API modules that handles
 * error reporting. It supports localization of error messages.
 *
 * @license GPL-2.0-or-later
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
	 * Reports any warnings in the StatusValue object on the warnings section
	 * of the result.
	 *
	 * @param StatusValue $status
	 */
	public function reportStatusWarnings( StatusValue $status ) {
		$formatter = $this->apiModule->getErrorFormatter();

		if ( !( $formatter instanceof ApiErrorFormatter_BackCompat ) ) {
			$formatter->addMessagesFromStatus( $this->apiModule->getModulePath(), $status, 'warning' );
			return;
		}

		$warnings = $status->getMessages( 'warning' );

		if ( $warnings ) {
			$warnings = $this->convertWarningsToResult( $warnings );
			$this->setWarning( 'messages', $warnings );
		}
	}

	/**
	 * Set warning section for this module.
	 *
	 * @note Only needed when ApiErrorFormatter_BackCompat is in use.
	 *
	 * @param string $key
	 * @param string|array $warningData Warning message
	 */
	private function setWarning( $key, $warningData ) {
		$result = $this->apiModule->getResult();
		$moduleName = $this->apiModule->getModuleName();

		$result->addValue(
			[ 'warnings', $moduleName ],
			$key,
			$warningData,
			ApiResult::NO_SIZE_CHECK
		);
	}

	/**
	 * Aborts the request with an error based on the given (fatal) StatusValue.
	 * This is intended as an alternative for ApiBase::dieWithError().
	 *
	 * If possible, a localized error message based on the exception is
	 * included in the error sent to the client. Localization of errors
	 * is attempted using the ExceptionLocalizer service provided to the
	 * constructor. If that fails, dieWithError() is called, which in turn
	 * attempts localization based on the error code.
	 *
	 * @see ApiBase::dieWithError()
	 *
	 * @param StatusValue $status The status to report. $status->getMessage() will be used
	 * to generate the error's free form description.
	 * @param string $errorCode A code identifying the error.
	 * @return never
	 *
	 * @throws ApiUsageException
	 * @throws LogicException
	 */
	public function dieStatus( StatusValue $status, $errorCode ) {
		if ( $status->isOK() ) {
			throw new InvalidArgumentException( 'called dieStatus() with a non-fatal StatusValue!' );
		}

		$error = $this->getPlainErrorMessageFromStatus( $status );
		$msg = $this->getMessageForCode( $errorCode, $error );

		$extendedStatus = StatusValue::newFatal( $msg );
		$extendedStatus->merge( $status, true );
		$status = $extendedStatus;

		$this->addStatusToResult( $status, $extraData );
		$msg->setApiData( $extraData );

		$this->apiModule->dieStatus( $status );
	}

	/**
	 * @param StatusValue $status
	 *
	 * @return string|null a plain text english error message, or null
	 */
	private function getPlainErrorMessageFromStatus( StatusValue $status ) {
		$errors = $status->getMessages( 'error' );
		if ( !$errors ) {
			return null;
		}
		// @phan-suppress-next-line PhanUndeclaredMethod
		$msg = ApiMessage::create( $errors[0] )
			->inLanguage( 'en' )
			->useDatabase( false );
		return ApiErrorFormatter::stripMarkup( $msg->text() );
	}

	/**
	 * Aborts the request with an error based on the given Exception.
	 * This is intended as an alternative for ApiBase::dieWithError().
	 *
	 * If possible, a localized error message based on the exception is
	 * included in the error sent to the client. Localization of errors
	 * is attempted using the ExceptionLocalizer service provided to the
	 * constructor. If that fails, dieWithError() is called, which in turn
	 * attempts localization based on the error code.
	 *
	 * @see ApiBase::dieWithError()
	 *
	 * @param Exception $ex The exception to report. $ex->getMessage() will be used as the error's
	 * free form description.
	 * @param string $errorCode A code identifying the error.
	 * @param int $httpRespCode The HTTP error code to send to the client
	 * @param array|null $extraData Any extra data to include in the error report
	 * @return never
	 *
	 * @throws ApiUsageException
	 * @throws LogicException
	 */
	public function dieException( Exception $ex, $errorCode, $httpRespCode = 0, $extraData = [] ) {
		if ( $this->localizer->hasExceptionMessage( $ex ) ) {
			$message = $this->localizer->getExceptionMessage( $ex );
			$key = $message->getKey();

			// NOTE: Ignore generic error messages, rely on the code instead!
			// XXX: No better way to do this?
			if ( $key !== 'wikibase-error-unexpected' ) {
				$this->dieWithError( $message, $errorCode, $httpRespCode, $extraData );
			}
		}

		$this->dieError( $ex->getMessage(), $errorCode, $httpRespCode, $extraData );

		// @phan-suppress-next-line PhanPluginUnreachableCode Wanted to guarantee return never at the language level
		throw new LogicException( 'ApiUsageException not thrown' );
	}

	/**
	 * @see ApiBase::dieWithError
	 *
	 * @param string|string[]|MessageSpecifier $msg
	 * @param string $errorCode A code identifying the error.
	 * @param int $httpRespCode The HTTP error code to send to the client
	 * @param array|null $extraData Any extra data to include in the error report
	 * @return never
	 *
	 * @throws ApiUsageException always
	 * @throws LogicException
	 */
	public function dieWithError( $msg, $errorCode, $httpRespCode = 0, $extraData = [] ) {
		if ( !( $msg instanceof MessageSpecifier ) ) {
			$params = (array)$msg;
			$messageKey = array_shift( $params );
			$msg = wfMessage( $messageKey, $params );
		}

		$this->addMessageToResult( $msg, $extraData );

		$this->apiModule->getMain()->dieWithError( $msg, $errorCode, $extraData, $httpRespCode );

		// @phan-suppress-next-line PhanPluginUnreachableCode Wanted to guarantee return never at the language level
		throw new LogicException( 'ApiUsageException not thrown' );
	}

	/**
	 * Aborts the request with an error code. This is intended as a drop-in
	 * replacement for ApiBase::dieWithError().
	 *
	 * Localization of the error code is attempted by looking up a message key
	 * constructed using the given code in "wikibase-api-$errorCode". If such a message
	 * exists, it is included in the error's extra data.
	 *
	 * @see ApiBase::dieWithError()
	 * @deprecated Use dieWithError() instead.
	 *
	 * @param string $description An english, plain text description of the error,
	 * for use in logs.
	 * @param string $errorCode A code identifying the error
	 * @param int $httpRespCode The HTTP error code to send to the client
	 * @param array|null $extraData Any extra data to include in the error report
	 * @return never
	 *
	 * @throws ApiUsageException always
	 * @throws LogicException
	 */
	public function dieError( $description, $errorCode, $httpRespCode = 0, $extraData = [] ) {
		$msg = $this->getMessageForCode( $errorCode, $description, $extraData );

		$this->addMessageToResult( $msg, $extraData );
		$msg->setApiData( $extraData );

		$this->apiModule->getMain()->dieWithError( $msg, $errorCode, $extraData, $httpRespCode );

		// @phan-suppress-next-line PhanPluginUnreachableCode Wanted to guarantee return never at the language level
		throw new LogicException( 'ApiUsageException not thrown' );
	}

	/**
	 * @param string $errorCode A code identifying the error.
	 * @param string|null $description Plain text english message
	 * @param array|null $extraData Any extra data to include in the error report
	 *
	 * @return ApiMessage
	 */
	private function getMessageForCode( $errorCode, $description, array $extraData = null ) {
		$messageKey = "wikibase-api-$errorCode";

		$msg = wfMessage( $messageKey );

		if ( !$msg->exists() ) {
			// NOTE: Use key fallback, so the nominal message key will be the original.
			$params = $description === null ? [] : [ $description ];
			// TODO: Should we use the ApiRawMessage class instead?
			$msg = wfMessage( [ $messageKey, 'rawmessage' ], $params );
		}

		// @phan-suppress-next-line PhanTypeMismatchReturnSuperType
		return ApiMessage::create( $msg, $errorCode, $extraData );
	}

	/**
	 * Add the given message to the $data array, for use in an error report.
	 *
	 * @param MessageSpecifier $message
	 * @param array|null &$data
	 *
	 * @throws InvalidArgumentException
	 */
	private function addMessageToResult( MessageSpecifier $message, &$data ) {
		$data ??= [];

		if ( !is_array( $data ) ) {
			throw new InvalidArgumentException( '$data must be an array' );
		}

		$messageData = $this->convertMessageToResult( $message );

		$messageList = $data['messages'] ?? [];
		ApiResult::setIndexedTagName( $messageList, 'message' );

		$messageList[] = $messageData;
		ApiResult::setValue( $data, 'messages', $messageList, ApiResult::OVERRIDE );
	}

	/**
	 * Add the messages from the given StatusValue object to the $data array,
	 * for use in an error report.
	 *
	 * @param StatusValue $status
	 * @param array|null &$data
	 *
	 * @throws InvalidArgumentException
	 */
	public function addStatusToResult( StatusValue $status, &$data ) {
		// Use Wikibase specific representation of messages in the result.
		// TODO: This should be phased out in favor of using ApiErrorFormatter, see below.
		$messageSpecs = $status->getMessages( 'error' );

		foreach ( $messageSpecs as $message ) {
			$this->addMessageToResult( $message, $data );
		}

		// Additionally, provide new (2016) API error reporting output.
		$this->apiModule->getErrorFormatter()->addMessagesFromStatus(
			$this->apiModule->getModulePath(),
			$status,
			'error'
		);
	}

	/**
	 * Utility method for compiling a list of warning messages into a form suitable for use
	 * in an API result structure.
	 *
	 * @param MessageSpecifier[] $messageSpecs Warnings returned by StatusValue::getMessages()
	 * @return array A result structure containing the messages
	 */
	private function convertWarningsToResult( array $messageSpecs ) {
		$result = [];

		foreach ( $messageSpecs as $message ) {
			$row = $this->convertMessageToResult( $message );
			ApiResult::setValue( $row, 'type', 'warning' );

			$result[] = $row;
		}

		ApiResult::setIndexedTagName( $result, 'message' );
		return $result;
	}

	/**
	 * Constructs a result structure from the given message
	 *
	 * @param MessageSpecifier $message
	 *
	 * @return array
	 */
	private function convertMessageToResult( MessageSpecifier $message ) {
		$name = $message->getKey();
		$params = $message->getParams();

		$row = [];
		ApiResult::setValue( $row, 'name', $name );

		ApiResult::setValue( $row, 'parameters', $params );
		ApiResult::setIndexedTagName( $row['parameters'], 'parameter' );

		$html = $this->apiModule->msg( $message )->useDatabase( true )->parse();
		ApiResult::setValue( $row, 'html', $html );
		$row[ApiResult::META_BC_SUBELEMENTS][] = 'html';

		return $row;
	}

}
