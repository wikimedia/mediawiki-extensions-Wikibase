<?php

namespace Wikibase;

/**
 * Allows storing and accessing of templates (e.g. snippets commonly used in server-side HTML
 * generation and client-side JavaScript processing).
 *
 * This class Stores plain templates.
 *
 * @since 0.2
 *
 * @licence GNU GPL v2+
 * @author H. Snater <mediawiki@snater.com>
 */
class TemplateRegistry {

	/**
	 * @var array
	 */
	private $templates;

	/**
	 * Gets the array containing all templates.
	 *
	 * @return array
	 */
	public function getTemplates() {
		return $this->templates;
	}

	/**
	 * Gets a specific template.
	 *
	 * @param string $key
	 * @return string
	 */
	public function getTemplate( $key ) {
		return $this->templates[$key];
	}

	/**
	 * Adds multiple templates to the store.
	 *
	 * @param array $templates
	 */
	public function addTemplates( $templates ) {
		foreach ( $templates as $key => $snippet ) {
			$this->addTemplate( $key, $snippet );
		}
	}

	/**
	 * Adds a single template to the store.
	 *
	 * @param string $key
	 * @param string $snippet
	 */
	public function addTemplate( $key, $snippet ) {
		$this->templates[$key] = str_replace( "\t", '', $snippet );
	}

}
