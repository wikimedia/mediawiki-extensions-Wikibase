<?php

namespace Wikibase\Template;

use Wikibase\Template;
use Wikibase\TemplateRegistry;

/**
 * @license GNU GPL v2+
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 */
class TemplateFactory {

	/**
	 * @var TemplateRegistry
	 */
	private $templateRegistry;

	/**
	 * @param TemplateRegistry $templateRegistry
	 */
	public function __construct( TemplateRegistry $templateRegistry ) {
		$this->templateRegistry = $templateRegistry;
	}

	/**
	 * @param string $key
	 * @param array $params
	 * @return Template
	 */
	public function get( $key, array $params ) {
		return new Template( $this->templateRegistry, $key, $params );
	}

	/**
	 * Shorthand function to retrieve a template filled with the specified parameters.
	 *
	 * important! note that the Template class does not escape anything.
	 * be sure to escape your params before using this function!
	 *
	 * @since 0.2
	 *
	 * @param $key string template key
	 * Varargs: normal template parameters
	 *
	 * @return string
	 */
	public function render( $key /* ... */ ) {
		$params = func_get_args();
		array_shift( $params );

		if ( isset( $params[0] ) && is_array( $params[0] ) ) {
			$params = $params[0];
		}

		$template = $this->get( $key, $params );

		return $template->render();
	}

}
