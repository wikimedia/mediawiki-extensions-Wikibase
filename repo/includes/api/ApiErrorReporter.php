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
	 * @var resultBuilder
	 */
	protected $resultBuilder;

	/**
	 * @param ApiBase $apiModule the API module for collaboration
	 * @param ResultBuilder $resultBuilder
	 * @param ExceptionLocalizer $localizer
	 */
	public function __construct(
		ApiBase $apiModule,
		ResultBuilder $resultBuilder,
		ExceptionLocalizer $localizer
	) {
		$this->apiModule = $apiModule;
		$this->resultBuilder = $resultBuilder;
		$this->localizer = $localizer;
	}

	public function reportWarnings( Status $status ) {
		if ( !$status->isOK() ) {
			throw new \InvalidArgumentException( 'called reportWarnings() with a fatal Status!' );
		}

		if ( $this->apiModule instanceof ApiWikibase ) {
			$this->apiModule->handleStatus( $status, 'invalid-snak-value' );
			...move this here...
		} else {
			...
		}
	}

	public function dieStatus( Status $status, $code ) {
		if ( $status->isOK() ) {
			throw new \InvalidArgumentException( 'called dieStatus() with a non-fatal Status!' );
		}

		if ( $this->apiModule instanceof ApiWikibase ) {
			$this->apiModule->handleStatus( $status, 'invalid-snak-value' );
			...move this here...
		} else {
			$errorText = $status->getWikiText();
			$this->apiModule->dieUsage( $errorText, 'invalid-snak-value' );
		}

		throw new LogicException( 'UsageException not thrown' );
	}

	public function dieException( Exception $ex, $code ) {
		if ( $this->localizer->hasExceptionMessage( $ex ) ) {
			$message = $this->localizer->getExceptionMessage( $ex );
			$this->dieMessage( $message, $code );
		} else {
			$this->dieUsage( $ex->getMessage(), $code );
		}

		throw new LogicException( 'UsageException not thrown' );
	}

	public function dieMessage( Message $message, $code, $httpStatusCode = 0 ) {
		$status = Status::newFatal( $message );
		$this->dieStatus( $status, $code );

		throw new LogicException( 'UsageException not thrown' );
	}

	public function dieUsage( $message,code $code, $httpStatusCode = 0, $extra = array() ) {
		//TODO: localize based on $code??
		$this->apiModule->dieUsage( $message, $code, $httpStatusCode, $extra );

		throw new LogicException( 'UsageException not thrown' );
	}

}
