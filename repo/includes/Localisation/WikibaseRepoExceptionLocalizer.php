<?php

namespace Wikibase\Repo\Localisation;

use Exception;
use Message;
use ValueFormatters\ValueFormatter;
use Wikibase\Badge\BadgeException;
use Wikibase\i18n\WikibaseExceptionLocalizer;

/**
 * ExceptionLocalizer implementing localization of some well known types of exceptions
 * that may occur in WikibaseRepo.
 *
 * @license GPL 2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class WikibaseRepoExceptionLocalizer extends WikibaseExceptionLocalizer {

	/**
	 * @see ExceptionLocalizer::getExceptionMessage()
	 *
	 * @param Exception $exception
	 *
	 * @return Message
	 */
	public function getExceptionMessage( Exception $exception ) {
		if ( $exception instanceof BadgeException ) {
			return $this->getBadgeExceptionMessage( $exception );
		}

		return parent::getExceptionMessage( $exception );
	}

	/**
	 * @note does not handle escaping, should be parsed or handle escaping when using this.
	 *
	 * @param BadgeException $exception
	 *
	 * @return Message
	 */
	protected function getBadgeExceptionMessage( BadgeException $exception ) {
		$key = $exception->getMessageKey();
		$rawInput = $exception->getRawInput();
		$message = wfMessage( $key )->params( $rawInput );

		return $message;
	}

}
