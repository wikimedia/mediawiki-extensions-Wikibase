<?php

declare( strict_types = 1 );

namespace Wikibase\View\Template;

/**
 * Allows storing and accessing of templates (e.g. snippets commonly used in server-side HTML
 * generation and client-side JavaScript processing).
 *
 * This class Represents a template that can contain placeholders just like MediaWiki messages.
 *
 * @license GPL-2.0-or-later
 * @author H. Snater <mediawiki@snater.com>
 */
class Template {

	private TemplateRegistry $templateRegistry;
	private string $key;
	private array $params;

	/**
	 * important! note that the Template class does not escape anything.
	 * be sure to escape your params before using this class!
	 *
	 * @param TemplateRegistry $templateRegistry
	 * @param string $key template key
	 * @param array $params Array template parameters
	 */
	public function __construct( TemplateRegistry $templateRegistry, string $key, array $params = [] ) {
		$this->templateRegistry = $templateRegistry;
		$this->key = $key;
		$this->params = $params;
	}

	public function render(): string {
		$template = $this->templateRegistry->getTemplate( $this->key );
		$replacements = [];
		foreach ( $this->params as $n => $param ) {
			$replacements['$' . ( $n + 1 )] = $param;
		}
		return strtr( $template, $replacements );
	}

}
