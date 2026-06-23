<?php

declare( strict_types = 1 );

namespace Wikibase\View;

use MediaWiki\Language\Language;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Serializers\StatementSerializer;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\DataModel\Statement\StatementList;
use Wikimedia\Assert\Assert;
use WMDE\VueJsTemplating\App;

/**
 * Registry for wbui2025 component templates.
 *
 * Single source of truth for which .vue files belong to wbui2025.
 * Used to create the rendering App and to count template files for style extraction.
 *
 * COMPONENT_FILES is the only list of which components exist. Two different paths both stay
 * tied to it: VueStylesModule calls registerComponentTemplates() directly to build a
 * request-data-free App for style extraction, while registerTemplates() (the SSR rendering
 * path) resolves every component's file through getTemplateCallable(), which reads
 * COMPONENT_FILES too and throws for anything not listed there. Either way, a component can't
 * be wired up for one purpose without existing for the other.
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
	 * Register just the templates for every wbui2025 component, driven entirely by
	 * COMPONENT_FILES. No per-request data needed, so this can be called from anywhere that
	 * needs to know the component templates without a real page loaded -- VueStylesModule uses
	 * it to build a throwaway App purely to extract <style> blocks. registerTemplates() does
	 * not call this: it registers every component directly (with its real setup data), which
	 * would otherwise mean doing this same work twice per render.
	 */
	public function registerComponentTemplates( App $app ): void {
		foreach ( self::COMPONENT_FILES as $componentName => $relPath ) {
			$this->registerComponentTemplate( $app, $componentName );
		}
	}

	public function registerTemplates(
		App $app,
		StatementList $allStatements,
		array $propertyExistence,
		LocalizedTextProvider $textProvider,
		EntityIdParser $entityIdParser,
		StatementSerializer $statementSerializer,
		EntityIdFormatterFactory $entityIdFormatterFactory,
		Language $language,
		PropertyDataTypeLookup $propertyDataTypeLookup
	): void {
		$this->registerStatementSectionsView( $app );
		$this->registerMainSnakView( $app, $textProvider );
		$this->registerStatementGroupView(
			$app,
			$allStatements,
			$propertyExistence,
			$entityIdParser,
			$statementSerializer
		);
		$this->registerStatementView( $app, $allStatements, $statementSerializer );
		$this->registerPropertyNameView(
			$app,
			$propertyExistence,
			$entityIdParser,
			$entityIdFormatterFactory,
			$language
		);
		$this->registerReferencesView( $app, $textProvider );
		$this->registerQualifiersView( $app );
		$this->registerSnakValueView( $app, $entityIdParser, $propertyDataTypeLookup );
	}

	public function registerComponentTemplate(
		App $app,
		string $componentName,
		?callable $computedFunctions = null
	): void {
		$app->registerComponentTemplate(
			$componentName,
			$this->getTemplateCallable( $componentName ),
			$computedFunctions
		);
	}

	private function registerStatementSectionsView( App $app ): void {
		$this->registerComponentTemplate(
			$app,
			'wbui2025-statement-sections',
			function ( array $data ): array {
				$data['propertyIds'] = $data['propertyList'];
				$data['javaScriptLoaded'] = false;
				return $data;
			}
		);
	}

	private function registerMainSnakView(
		App $app,
		LocalizedTextProvider $textProvider
	): void {
		$this->registerComponentTemplate(
			$app,
			'wbui2025-main-snak',
			function ( array $data ) use ( $textProvider ): array {
				$data['rankTitleString'] = $textProvider->get(
				// messages that can be used here:
				// * wikibase-statementview-rank-normal
				// * wikibase-statementview-rank-preferred
				// * wikibase-statementview-rank-deprecated
				'wikibase-statementview-rank-' . $data['rank']
				);
				$data['showIndicators'] = false;

				return $data;
			}
		);
	}

	private function registerStatementGroupView(
		App $app,
		StatementList $allStatements,
		array $propertyExistence,
		EntityIdParser $entityIdParser,
		StatementSerializer $statementSerializer
	): void {
		$this->registerComponentTemplate(
			$app,
			'wbui2025-statement-group-view',
			function ( array $data ) use (
				$allStatements,
				$propertyExistence,
				$entityIdParser,
				$statementSerializer
			): array {
				/** @var PropertyId $propertyId */
				$propertyId = $entityIdParser->parse( $data['propertyId'] );
				'@phan-var PropertyId $propertyId';

				$data['isDeletedProperty'] = !( $propertyExistence[$propertyId->getSerialization()] ?? false );
				$data['statements'] = array_map(
					$statementSerializer->serialize( ... ),
					$allStatements->getByPropertyId( $propertyId )->toArray()
				);
				$data['showModalEditForm'] = false;

				return $data;
			}
		);
	}

	private function registerStatementView(
		App $app,
		StatementList $allStatements,
		StatementSerializer $statementSerializer
	): void {
		$this->registerComponentTemplate(
			$app,
			'wbui2025-statement-view',
			function ( array $data ) use ( $allStatements, $statementSerializer ): array {
				$statementId = $data['statementId'];
				$statementById = $allStatements->getFirstStatementWithGuid( $statementId );

				Assert::invariant( $statementById !== null, "Statement $statementId not found" );

				$data['statement'] = $statementSerializer->serialize( $statementById );
				$data['references'] = array_key_exists( 'references', $data['statement'] ) ? $data['statement']['references'] : [];
				$data['qualifiers'] = array_key_exists( 'qualifiers', $data['statement'] ) ? $data['statement']['qualifiers'] : [];
				$data['qualifiersOrder'] =
					array_key_exists( 'qualifiers-order', $data['statement'] ) ? $data['statement']['qualifiers-order'] : [];
				$data['activeClasses'] = [ 'wikibase-wbui2025-statement-view' ];

				if ( array_key_exists( 'rank', $data['statement'] ) ) {
					if ( $data['statement']['rank'] === 'preferred' ) {
						$data['activeClasses'][] = 'wb-preferred';
					} elseif ( $data['statement']['rank'] === 'deprecated' ) {
						$data['activeClasses'][] = 'wb-deprecated';
					}
				}

				return $data;
			}
		);
	}

	private function registerPropertyNameView(
		App $app,
		array $propertyExistence,
		EntityIdParser $entityIdParser,
		EntityIdFormatterFactory $entityIdFormatterFactory,
		Language $language
	): void {
		$this->registerComponentTemplate(
			$app,
			'wbui2025-property-name',
			function ( array $data ) use (
				$propertyExistence,
				$entityIdParser,
				$entityIdFormatterFactory,
				$language
			): array {
				/** @var PropertyId $propertyId */
				$propertyId = $entityIdParser->parse( $data['propertyId'] );
				'@phan-var PropertyId $propertyId';

				$data['propertyLinkHtml'] = $entityIdFormatterFactory
					->getEntityIdFormatter( $language )
					->formatEntityId( $propertyId );
				$data['isDeletedProperty'] = !( $propertyExistence[$propertyId->getSerialization()] ?? false );

				return $data;
			}
		);
	}

	private function registerReferencesView(
		App $app,
		LocalizedTextProvider $textProvider
	): void {
		$this->registerComponentTemplate(
			$app,
			'wbui2025-references',
			function ( array $data ) use ( $textProvider ): array {
				$data['referenceCount'] = count( $data['references'] );
				$data['hasReferences'] = $data['referenceCount'] > 0;
				$data['referencesMessage'] = $textProvider->getEscaped(
					'wikibase-statementview-references-counter',
					[
						strval( $data['referenceCount'] ),
					],
				);
				$data['showReferences'] = false;
				$data['showIndicators'] = false;

				return $data;
			}
		);
	}

	private function registerQualifiersView( App $app ): void {
		$this->registerComponentTemplate(
			$app,
			'wbui2025-qualifiers',
			function ( array $data ): array {
				$qualifierCount = count( $data['qualifiers'] );
				$data['hasQualifiers'] = $qualifierCount > 0;
				$data['showIndicators'] = false;
				return $data;
			}
		);
	}

	private function registerSnakValueView(
		App $app,
		EntityIdParser $entityIdParser,
		PropertyDataTypeLookup $propertyDataTypeLookup
	): void {
		$this->registerComponentTemplate(
			$app,
			'wbui2025-snak-value',
			function ( array $data ) use ( $entityIdParser, $propertyDataTypeLookup ): array {
				/** @var PropertyId $propertyId */
				$propertyId = $entityIdParser->parse( $data['snak']['property'] );
				'@phan-var PropertyId $propertyId';

				try {
					$dataType = $propertyDataTypeLookup->getDataTypeIdForProperty( $propertyId );
				} catch ( PropertyDataTypeLookupException ) {
					$dataType = null;
				}

				$data['snakValueClass'] = [
					'wikibase-wbui2025-media-value' => $dataType == 'commonsMedia',
					'wikibase-wbui2025-globe-coordinate-value' => $dataType == 'globe-coordinate',
					'wikibase-wbui2025-time-value' => $dataType == 'time',
					'wikibase-wbui2025-tabular-data-value' => $dataType == 'tabular-data',
					'wikibase-wbui2025-geo-shape-value' => $dataType == 'geo-shape',
					'wikibase-wbui2025-musical-notation-value' => $dataType == 'musical-notation',
					'wikibase-wbui2025-math-value' => $dataType == 'math',
					'wikibase-wbui2025-quantity-value' => $dataType == 'quantity',
				];

				return $data;
			}
		);
	}

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
	 * @return array<string, string> Map of component name to its relative path (from repo root).
	 */
	public function getTemplateFiles(): array {
		$result = [];
		foreach ( self::COMPONENT_FILES as $componentName => $fileName ) {
			$result[$componentName] = self::COMPONENTS_REL . $fileName;
		}
		return $result;
	}
}
