<?php

namespace Wikibase\Repo\Localisation;

use Exception;
use Message;
use ValueFormatters\ValueFormatter;
use Wikibase\BadgesParsingException;
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
	 * @param Exception $ex
	 *
	 * @return Message
	 */
	public function getExceptionMessage( Exception $ex ) {
		if ( $ex instanceof BadgesParsingException ) {
			return $this->getBadgesParsingExceptionMessage( $ex );
		}

		return parent::getExceptionMessage( $ex );
	}

	/**
	 * @note does not handle escaping, should be parsed or handle escaping when using this.
	 *
	 * @param BadgesParsingException $exception
	 *
	 * @return Message
	 */
	protected function getBadgesParsingExceptionMessage( BadgesParsingException $exception ) {
		$key = $exception->getMessageKey();
		$parseInput = $exception->getParseInput();
		$message = wfMessage( $key )->params( $parseInput );

		return $message;
	}

}
