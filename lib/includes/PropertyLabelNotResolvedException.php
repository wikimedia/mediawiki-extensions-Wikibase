<?php

namespace Wikibase\Lib;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class PropertyLabelNotResolvedException extends \RuntimeException {

	protected $label;

	protected $lang;

	public function __construct( $label, $lang, $message = null, \Exception $previous = null ) {
		$this->label = $label;
		$this->lang = $lang;

		if ( $message === null ) {
			$message = "Property not found for label '$label' and language '$lang'";
		}

		parent::__construct( $message, 0, $previous );
	}

}
