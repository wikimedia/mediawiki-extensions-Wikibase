<?php

namespace Wikibase\View\Template;

/**
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 * @author Thiemo Mättig
 */
class TemplateFactory {

	/**
	 * @var self
	 */
	private static $instance;

	/**
	 * @var string[]
	 */
	private $templates = [];

	public static function getDefaultInstance() {
		if ( self::$instance === null ) {
			self::$instance = new self( include __DIR__ . '/../../resources/templates.php' );
		}

		return self::$instance;
	}

	/**
	 * @param string[] $templates
	 */
	public function __construct( array $templates = [] ) {
		foreach ( $templates as $key => $template ) {
			$this->templates[$key] = str_replace( "\t", '',
				preg_replace( '/<!--.*-->/Us', '', $template )
			);
		}
	}

	/**
	 * @return string[] Array containing all raw template strings.
	 */
	public function getTemplates() {
		return $this->templates;
	}

	/**
	 * Shorthand function to retrieve a template filled with the specified parameters.
	 *
	 * important! note that the Template class does not escape anything.
	 * be sure to escape your params before using this function!
	 *
	 * @since 0.2
	 *
	 * @param string $key template key
	 * @param string [$param,...]
	 *
	 * @return string
	 */
	public function render( $key /*...*/ ) {
		$params = func_get_args();
		array_shift( $params );

		if ( isset( $params[0] ) && is_array( $params[0] ) ) {
			$params = $params[0];
		}

		$template = new Template(
			$key,
			array_key_exists( $key, $this->templates ) ? $this->templates[$key] : null,
			$params
		);

		return $template->render();
	}

}
