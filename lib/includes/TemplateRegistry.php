<?php

namespace Wikibase;

/**
 * Allows storing and accessing of templates (e.g. snippets commonly used in server-side HTML
 * generation and client-side JavaScript processing).
 *
 * @since 0.2
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater <mediawiki@snater.com>
 */

/**
 * Stores plain templates.
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
		foreach ( $templates AS $key => $snippet ) {
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

/**
 * Represents a template that can contain placeholders just like MediWiki messages.
 */
class Template extends \Message {

	protected $templateRegistry;

	/**
	 * Constructor.
	 *
	 * @param TemplateRegistry $templateRegistry
	 * @param $key: message key, or array of message keys to try and use the first non-empty message for
	 * @param $params Array message parameters
	 */
	public function __construct( TemplateRegistry $templateRegistry, $key, $params = array() ) {
		$this->templateRegistry = $templateRegistry;
		parent::__construct( $key, $params );
	}

	/**
	 * Fetch a template from the template store.
	 * @see \Message.fetchMessage()
	 *
	 * @return string template
	 */
	function fetchMessage() {
		if ( !isset( $this->message ) ) {
			$this->message = $this->templateRegistry->getTemplate( $this->key );
		}
		return $this->message;
	}

}
