<?php

declare( strict_types = 1 );

namespace Wikibase\View;

/**
 * Registry for wbui2025 component templates.
 *
 * Single source of truth for which .vue files belong to wbui2025.
 * Used to create the rendering App (with per-component setup callbacks)
 * and to count template files for style extraction.
 *
 * @license GPL-2.0-or-later
 * @author Mahmoud Abdelsattar <mahmoud.abdelsattar@wikimedia.de>
 */
class Wbui2025ComponentsFactory {

	private const COMPONENTS_ABS = __DIR__ . '/../../repo/resources/wikibase.wbui2025/';
	private const COMPONENTS_REL = 'resources/wikibase.wbui2025/';

	/**
	 * Map of component tag name => template file path, relative to COMPONENTS_ABS.
	 * Add new components here when adding them to SSR rendering.
	 *
	 * @var array<string, string>
	 */
	private const COMPONENT_FILES = [
		'wbui2025-statement-sections' => 'components/statementSections.vue',
		'wbui2025-statement-group-view' => 'components/statementGroupView.vue',
		'wbui2025-main-snak' => 'components/mainSnak.vue',
		'wbui2025-qualifiers' => 'components/qualifiers.vue',
		'wbui2025-references' => 'components/references.vue',
		'wbui2025-snak-value' => 'components/snakValue.vue',
		'wbui2025-statement-view' => 'components/statementView.vue',
		'wbui2025-property-name' => 'components/propertyName.vue',
	];

	/**
	 * Return a callable that reads the template file for the given component.
	 * Used by VueNoScriptRendering to register component templates without knowing file paths.
	 *
	 * @throws \InvalidArgumentException if $componentName is not a known wbui2025 component
	 */
	public function getTemplateCallable( string $componentName ): callable {
		$relPath = self::COMPONENT_FILES[$componentName]
			?? throw new \InvalidArgumentException( "Unknown wbui2025 component: '$componentName'" );
		$absPath = self::COMPONENTS_ABS . $relPath;
		if ( !file_exists( $absPath ) ) {
			throw new \RuntimeException( "Template file for component '$componentName' not found at expected path: '$absPath'" );
		}
		return fn () => file_get_contents( $absPath );
	}

	/**
	 * @return array<string, string> Map of component name to its relative path (from repo root)
	 */
	public function getTemplateFiles(): array {
		$result = [];
		foreach ( self::COMPONENT_FILES as $componentName => $fileName ) {
			$result[$componentName] = self::COMPONENTS_REL . $fileName;
		}
		return $result;
	}

}
