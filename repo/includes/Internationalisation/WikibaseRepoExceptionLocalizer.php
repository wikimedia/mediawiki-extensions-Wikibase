<?php

namespace Wikibase\Repo\Internationalisation;

use Exception;
use Message;
use ValueFormatters\ValueFormatter;
use Wikibase\ChangeOp\ChangeOpValidationException;
use Wikibase\Lib\Internationalisation\WikibaseExceptionLocalizer;
use Wikibase\Validators\ValidatorErrorLocalizer;

/**
 * ExceptionLocalizer implementing localization of some well known types of exceptions
 * that may occur in the context of the Wikibase exception.
 *
 * This hardcodes knowledge about different kinds of exceptions. A more generic approach
 * based on a dispatcher can be implemented later if needed.
 *
 * @todo: Extend the interface to allow multiple messages to be returned, for use
 *        with chained exceptions, multiple validation errors, etc.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class WikibaseRepoExceptionLocalizer extends WikibaseExceptionLocalizer {

	/**
	 * @var ValidatorErrorLocalizer
	 */
	protected $validatorErrorLocalizer;

	/**
	 * @param ValueFormatter $paramFormatter A formatter for formatting message parameters
	 *        as wikitext. Typically some kind of dispatcher.
	 */
	public function __construct( ValueFormatter $paramFormatter ) {
		$this->validatorErrorLocalizer = new ValidatorErrorLocalizer( $paramFormatter );
	}

	/**
	 * @see ExceptionLocalizer::getExceptionMessage()
	 *
	 * @param Exception $ex
	 *
	 * @return Message
	 */
	public function getExceptionMessage( Exception $ex ) {
		if ( $ex instanceof ChangeOpValidationException ) {
			return $this->getChangeOpValidationExceptionMessage( $ex );
		}

		return parent::getExceptionMessage( $ex );
	}

	/**
	 * @param ChangeOpValidationException $ex
	 *
	 * @return Message
	 */
	public function getChangeOpValidationExceptionMessage( ChangeOpValidationException $ex ) {
		$result = $ex->getValidationResult();

		foreach ( $result->getErrors() as $error ) {
			$msg = $this->validatorErrorLocalizer->getErrorMessage( $error );
			return $msg;
		}

		return wfMessage( 'wikibase-validator-invalid' );
	}

}
