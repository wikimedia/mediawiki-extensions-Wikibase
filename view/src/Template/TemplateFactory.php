<?php

namespace Wikibase\View\Template;

/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 * @author Thiemo Kreuz
 */
class TemplateFactory {

	/**
	 * @var self
	 */
	private static $instance;

	/**
	 * @var TemplateRegistry
	 */
	private $templateRegistry;

	public static function getDefaultInstance() {
		if ( self::$instance === null ) {
			self::$instance = new self(
				new TemplateRegistry( require __DIR__ . '/../../resources/templates.php' )
			);
		}

		return self::$instance;
	}

	public function __construct( TemplateRegistry $templateRegistry ) {
		$this->templateRegistry = $templateRegistry;
	}

	/**
	 * @return string[] Array containing all raw template strings.
	 */
	public function getTemplates() {
		return $this->templateRegistry->getTemplates();
	}

	/**
	 * @param string $key
	 * @param array $params
	 *
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
	 * @param string $key template key
	 * Varargs: normal template parameters
	 * @param string|array ...$params
	 *
	 * @return string
	 */
	public function render( $key, ...$params ) {
		if ( isset( $params[0] ) && is_array( $params[0] ) ) {
			$params = $params[0];
		}

		$template = $this->get( $key, $params );

		return $template->render();
	}

}
