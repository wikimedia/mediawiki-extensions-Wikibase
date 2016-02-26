<?php

namespace Wikibase\Lib;

/**
 * Used in special pages and elsewhere to handle user input errors,
 * allow them to bubble up to presentation layer and contain message
 * that can be displayed to the user in their language.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class UserInputException extends MessageException {

}
