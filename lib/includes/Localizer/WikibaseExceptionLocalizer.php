<?php

namespace Wikibase\Lib\Localizer;

use Exception;
use Message;
use MessageException;
use ValueFormatters\ValueFormatter;
use ValueParsers\ParseException;
use Wikibase\ChangeOp\ChangeOpValidationException;
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
 * @deprecated 0.5
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class WikibaseExceptionLocalizer implements ExceptionLocalizer {

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
	 * @deprecated 0.5
	 * @return Message
	 */
	public function getExceptionMessage( Exception $ex ) {
		$localizers = array(
			'MessageException' => new MessageExceptionLocalizer(),
			'ParseException' => new ParseExceptionLocalizer()
		);

		$localizer = new DispatchingExceptionLocalizer( $localizers );

		return $localizer->getExceptionMessage( $ex );
	}

	/**
	 * @see ExceptionLocalizer::getExceptionMessage()
	 *
	 * @param Exception $ex
	 *
	 * @return bool Always true, since WikibaseExceptionLocalizer is able to provide
	 *         a Message for any kind of exception.
	 */
	public function hasExceptionMessage( Exception $ex ) {
		return true;
	}
}
