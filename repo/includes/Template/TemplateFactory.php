<?php

namespace Wikibase\Template;

/**
 * @license GNU GPL v2+
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 * @author Thiemo Mättig
 */
class TemplateFactory {

	/**
	 * @var TemplateFactory
	 */
	private static $instance;

	/**
	 * @var string[]
	 */
	private $templates = array();

	public static function getDefaultInstance() {
		if ( self::$instance === null ) {
			self::$instance = new self( include( __DIR__ . '/../../resources/templates.php' ) );
		}

		return self::$instance;
	}

	/**
	 * @param string[] $templates
	 */
	public function __construct( array $templates = array() ) {
		$this->addTemplates( $templates );
	}

	/**
	 * Adds multiple raw template strings to the internal store.
	 *
	 * @param string[] $templates
	 */
	public function addTemplates( array $templates ) {
		foreach ( $templates as $key => $template ) {
			$this->addTemplate( $key, $template );
		}
	}

	/**
	 * Adds a single raw template string to the internal store.
	 *
	 * @param string $key
	 * @param string $template
	 */
	public function addTemplate( $key, $template ) {
		$this->templates[$key] = str_replace( "\t", '', $template );
	}

	/**
	 * @return string[] Array containing all raw template strings.
	 */
	public function getTemplates() {
		return $this->templates;
	}

	/**
	 * @param string $key
	 *
	 * @return string|null A specific raw template string or null, if a template with that name
	 * could not be found.
	 */
	public function getTemplate( $key ) {
		return array_key_exists( $key, $this->templates ) ? $this->templates[$key] : null;
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

		$template = new Template( $key, $this->getTemplate( $key ), $params );

		return $template->render();
	}

}
