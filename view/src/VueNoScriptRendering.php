<?php

declare( strict_types = 1 );

namespace Wikibase\View;

use MediaWiki\Language\Language;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\DataModel\Serializers\StatementSerializer;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikimedia\Assert\Assert;
use WMDE\VueJsTemplating\App;

/**
 * Handle the server-side / no-script rendering of Vue templates for the wbui2025 mobile
 * user experience implementation.
 *
 * @license GPL-2.0-or-later
 */
class VueNoScriptRendering {

	/** Data types supported by the Vue statements view. */
	public const WBUI2025_SUPPORTED_DATATYPES = [
		'string',
		'tabular-data',
		'geo-shape',
	];

	private const VUE_TEMPLATE_FOLDER = __DIR__ . '/../../repo/resources/wikibase.wbui2025/';

	private EntityIdFormatterFactory $entityIdFormatterFactory;
	private EntityIdParser $entityIdParser;
	private Language $language;
	private LocalizedTextProvider $textProvider;
	private PropertyDataTypeLookup $propertyDataTypeLookup;
	private StatementSerializer $statementSerializer;
	private SnakFormatter $snakFormatter;
	private array $snakValueHtmlLookup;
	private App $app;

	public function __construct(
		EntityIdFormatterFactory $entityIdFormatterFactory,
		EntityIdParser $entityIdParser,
		Language $language,
		LocalizedTextProvider $textProvider,
		PropertyDataTypeLookup $propertyDataTypeLookup,
		SerializerFactory $serializerFactory,
		SnakFormatter $snakFormatter,
	) {
		$this->entityIdFormatterFactory = $entityIdFormatterFactory;
		$this->entityIdParser = $entityIdParser;
		$this->language = $language;
		$this->textProvider = $textProvider;
		$this->propertyDataTypeLookup = $propertyDataTypeLookup;
		$this->statementSerializer = $serializerFactory->newStatementSerializer();
		$this->snakFormatter = $snakFormatter;
	}

	public function loadStatementData( StatementList $allStatements ): void {
		$this->snakValueHtmlLookup = [];
		$this->app = new App( $this->globalTemplateFunctions() );
		$this->registerTemplates( $allStatements );
	}

	/**
	 * @return array<callable>
	 */
	private function globalTemplateFunctions(): array {
		return [
			'snakValueHtmlForHash' => function ( $hash ) {
				if ( array_key_exists( $hash, $this->snakValueHtmlLookup ) ) {
					return $this->snakValueHtmlLookup[$hash];
				}
				return '<p>No server-side HTML stored for snak ' . $hash . '</p>';
			},
			'concat' => function( ...$args ) {
				return implode( '', $args );
			},
			'$i18n' => function ( string $messageKey, string ...$params ): string {
				return $this->textProvider->get( $messageKey, $params );
			},
		];
	}

	private function registerComponentTemplate(
		string $componentName,
		string $templateFile,
		?callable $computedFunctions = null
	): void {
		$templateFilePath = self::VUE_TEMPLATE_FOLDER . $templateFile;
		$this->app->registerComponentTemplate(
			$componentName,
			fn () => file_get_contents( $templateFilePath ),
			$computedFunctions
		);
	}

	private function registerTemplates( StatementList $allStatements ): void {
		$this->registerComponentTemplate(
			'wbui2025-statement-sections', 'wikibase.wbui2025.statementSections.vue'
		);
		$this->registerMainSnakView();
		$this->registerStatementGroupView( $allStatements );
		$this->registerStatementView( $allStatements );
		$this->registerPropertyNameView();
		$this->registerReferencesView();
		$this->registerQualifiersView();
		$this->registerSnakValueView();
	}

	private function registerMainSnakView(): void {
		$this->registerComponentTemplate(
			'wbui2025-main-snak',
			'wikibase.wbui2025.mainSnak.vue',
			function( array $data ) {
				$data['rankTitleString'] = $this->textProvider->get(
					// messages that can be used here:
					// * wikibase-statementview-rank-normal
					// * wikibase-statementview-rank-preferred
					// * wikibase-statementview-rank-deprecated
				'wikibase-statementview-rank-' . $data['rank']
				);
				return $data;
			}
		);
	}

	private function registerStatementGroupView( StatementList $allStatements ): void {
		$this->registerComponentTemplate(
			'wbui2025-statement-group-view',
			'wikibase.wbui2025.statementGroupView.vue',
			function( array $data ) use ( $allStatements ): array {
				/** @var PropertyId $propertyId */
				$propertyId = $this->entityIdParser
					->parse( $data['propertyId'] );
				'@phan-var PropertyId $propertyId';
				$dataType = $this->propertyDataTypeLookup->getDataTypeIdForProperty( $propertyId );
				$statementsByProperty = $allStatements->getByPropertyId( $propertyId )->toArray();
				$data['statements'] = array_map(
					fn ( $statement ) => $this->statementSerializer->serialize( $statement ),
					$statementsByProperty
				);
				$data['isUnsupportedDataType'] = !in_array( $dataType, self::WBUI2025_SUPPORTED_DATATYPES, strict: true );
				$data['showModalEditForm'] = false;
				return $data;
			}
		);
	}

	private function registerStatementView( StatementList $allStatements ): void {
		$this->registerComponentTemplate(
			'wbui2025-statement-view',
			'wikibase.wbui2025.statementView.vue',
			function ( array $data ) use ( $allStatements ): array {
				$statementId = $data['statementId'];
				$statementById = $allStatements->getFirstStatementWithGuid( $statementId );
				Assert::invariant( $statementById !== null, "Statement $statementId not found" );
				$data['statement'] = $this->statementSerializer->serialize( $statementById );
				$data['references'] = array_key_exists( 'references', $data['statement'] ) ? $data['statement']['references'] : [];
				$data['qualifiers'] = array_key_exists( 'qualifiers', $data['statement'] ) ? $data['statement']['qualifiers'] : [];
				$data['qualifiersOrder'] =
					array_key_exists( 'qualifiers-order', $data['statement'] ) ? $data['statement']['qualifiers-order'] : [];
				return $data;
			}
		);
	}

	private function registerPropertyNameView(): void {
		$this->registerComponentTemplate(
			'wbui2025-property-name',
			'wikibase.wbui2025.propertyName.vue',
			function ( array $data ): array {
				$propertyId = $this->entityIdParser
					->parse( $data['propertyId'] );

				$data['propertyLinkHtml'] = $this->entityIdFormatterFactory
					->getEntityIdFormatter( $this->language )
					->formatEntityId( $propertyId );
				return $data;
			}
		);
	}

	private function registerReferencesView(): void {
		$this->registerComponentTemplate(
			'wbui2025-references',
			'wikibase.wbui2025.references.vue',
			function ( array $data ): array {
				$data['referenceCount'] = count( $data['references'] );
				$data['hasReferences'] = $data['referenceCount'] > 0;
				$data['referencesMessage'] = $this->textProvider->getEscaped(
					'wikibase-statementview-references-counter', [
						strval( $data[ 'referenceCount' ] ),
					],
				);
				$data['showReferences'] = false;
				return $data;
			}
		);
	}

	private function registerQualifiersView(): void {
		$this->registerComponentTemplate(
			'wbui2025-qualifiers',
			'wikibase.wbui2025.qualifiers.vue',
			function ( array $data ): array {
				$qualifierCount = count( $data['qualifiers'] );
				$data['hasQualifiers'] = $qualifierCount > 0;
				return $data;
			}
		);
	}

	private function registerSnakValueView(): void {
		$this->registerComponentTemplate(
			'wbui2025-snak-value',
			'wikibase.wbui2025.snakValue.vue',
			function ( array $data ): array {
				/** @var PropertyId $propertyId */
				$propertyId = $this->entityIdParser
					->parse( $data['snak']['property'] );
				'@phan-var PropertyId $propertyId';
				$dataType = $this->propertyDataTypeLookup->getDataTypeIdForProperty( $propertyId );

				$data['snakValueClass'] = [
					'wikibase-wbui2025-media-value' => $dataType == 'commonsMedia',
					'wikibase-wbui2025-time-value' => $dataType == 'time',
				];

				return $data;
			}
		);
	}

	private function populateReferenceSnakValueHtml(
		Statement $statement,
		array $statementData,
	): void {
		if ( !array_key_exists( 'references', $statementData ) || !$statementData['references'] ) {
			return;
		}
		foreach ( $statementData['references'] as $referenceData ) {
			$reference = $statement->getReferences()->getReference( $referenceData['hash'] );
			foreach ( $reference->getSnaks() as $snakObject ) {
				foreach ( $referenceData['snaks'] as $snakList ) {
					foreach ( $snakList as $snakData ) {
						if ( $snakData['hash'] === $snakObject->getHash() ) {
							$this->snakValueHtmlLookup[$snakObject->getHash()] = $this->snakFormatter->formatSnak( $snakObject );
						}
					}
				}
			}
		}
	}

	private function populateQualifierSnakValueHtml(
		Statement $statement,
		array $statementData,
	): void {
		if (
			!array_key_exists( 'qualifiers', $statementData ) ||
			!array_key_exists( 'qualifiers-order', $statementData ) ||
			!$statementData['qualifiers']
		) {
			return;
		}
		foreach ( $statementData['qualifiers-order'] as $propertyId ) {
			foreach ( $statementData['qualifiers'][$propertyId] as $qualifierData ) {
				$qualifier = $statement->getQualifiers()->getSnak( $qualifierData['hash'] );
				$this->snakValueHtmlLookup[$qualifierData['hash']] = $this->snakFormatter->formatSnak( $qualifier );
			}
		}
	}

	/**
	 * @param string $entityId
	 * @param string $sectionHeadingHtml Section heading as HTML
	 * @param StatementList $statementsList
	 * @return string Rendered HTML
	 */
	public function renderStatementsSectionHtml(
		string $entityId,
		string $sectionHeadingHtml,
		StatementList $statementsList,
	): string {
		$propertyStatementMap = [];
		$propertyList = [];
		foreach ( $statementsList->getPropertyIds() as $propertyId ) {
			$propertyStatements = $statementsList->getByPropertyId( $propertyId )->toArray();
			$propertyList[] = $propertyId->getSerialization();

			$statementsData = [];
			foreach ( $propertyStatements as $statement ) {
				$mainSnak = $statement->getMainSnak();
				$statementData = $this->statementSerializer->serialize( $statement );

				$this->populateReferenceSnakValueHtml( $statement, $statementData );
				$this->populateQualifierSnakValueHtml( $statement, $statementData );
				if ( array_key_exists( 'hash', $statementData['mainsnak'] ) ) {
					$this->snakValueHtmlLookup[$statementData['mainsnak']['hash']] = $this->snakFormatter->formatSnak( $mainSnak );
				}
				$statementsData[] = $statementData;
			}
			$propertyStatementMap[$propertyId->getSerialization()] = $statementsData;
		}

		return $this->app->renderComponent( 'wbui2025-statement-sections', [
			'sectionHeadingHtml' => $sectionHeadingHtml,
			'propertyList' => $propertyList,
			'propertyStatementMap' => $propertyStatementMap,
			'entityId' => $entityId,
		] );
	}

}
