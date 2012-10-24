<?php

namespace Wikibase;

/**
 * Allows storing and accessing of templates (e.g. snippets commonly used in server-side HTML
 * generation and client-side JavaScript processing).
 *
 * @since 0.2
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater <mediawiki@snater.com>
 */

/**
 * Stores plain templates.
 */
class TemplateStore {
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
		foreach ( $templates AS $key => $snippet ) {
			$this->addTemplate( $key, $snippet );
		}
	}

	/**
	 * Adds a single template to the store.
	 *
	 * @param string $key
	 * @param string $html
	 */
	public function addTemplate( $key, $snippet ) {
		$this->templates[$key] = str_replace( "\t", '', $snippet );
	}

	/**
	 * Singleton pattern integration.
	 *
	 * @return TemplateStore
	 */
	public static function singleton() {
		static $instance = false;

		if ( $instance === false ) {
			$instance = new static();
		}

		return $instance;
	}
}

/**
 * Represents a template that can contain placeholders just like MediWiki messages.
 */
class Template extends \Message {

	/**
	 * Fetch a template from the template store.
	 * @see \Message.fetchMessage()
	 *
	 * @return string template
	 */
	function fetchMessage() {
		if ( !isset( $this->message ) ) {
			$cache = TemplateStore::singleton();
			$this->message = $cache->getTemplate( $this->key );
		}
		return $this->message;
	}

}