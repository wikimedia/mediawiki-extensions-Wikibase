<?php

declare( strict_types = 1 );

namespace Wikibase\View;

use MediaWiki\Language\Language;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\DataModel\Serializers\StatementSerializer;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\Lib\Store\EntityExistenceChecker;
use Wikimedia\Assert\Assert;
use WMDE\VueJsTemplating\App;

/**
 * Handle the server-side / no-script rendering of Vue templates for the wbui2025 mobile
 * user experience implementation.
 *
 * @license GPL-2.0-or-later
 */
class VueNoScriptRendering {

	private EntityIdFormatterFactory $entityIdFormatterFactory;
	private EntityIdParser $entityIdParser;
	private Language $language;
	private LocalizedTextProvider $textProvider;
	private PropertyDataTypeLookup $propertyDataTypeLookup;
	private EntityExistenceChecker $entityExistenceChecker;
	private StatementSerializer $statementSerializer;
	private SnakFormatter $snakFormatter;
	private array $snakValueHtmlLookup;
	private array $propertyExistence;
	private Wbui2025FeatureFlag $wbui2025FeatureFlag;
	private Wbui2025ComponentsFactory $componentsFactory;
	private App $app;

	public function __construct(
		EntityIdFormatterFactory $entityIdFormatterFactory,
		EntityIdParser $entityIdParser,
		Language $language,
		LocalizedTextProvider $textProvider,
		PropertyDataTypeLookup $propertyDataTypeLookup,
		EntityExistenceChecker $entityExistenceChecker,
		SerializerFactory $serializerFactory,
		SnakFormatter $snakFormatter,
		Wbui2025FeatureFlag $wbui2025FeatureFlag,
		Wbui2025ComponentsFactory $componentsFactory,
	) {
		$this->entityIdFormatterFactory = $entityIdFormatterFactory;
		$this->entityIdParser = $entityIdParser;
		$this->language = $language;
		$this->textProvider = $textProvider;
		$this->propertyDataTypeLookup = $propertyDataTypeLookup;
		$this->entityExistenceChecker = $entityExistenceChecker;
		$this->statementSerializer = $serializerFactory->newStatementSerializer();
		$this->snakFormatter = $snakFormatter;
		$this->wbui2025FeatureFlag = $wbui2025FeatureFlag;
		$this->componentsFactory = $componentsFactory;
	}

	public function loadStatementData( StatementList $allStatements ): void {
		$this->snakValueHtmlLookup = [];
		$allPropertyIds = [];
		foreach ( $allStatements->getAllSnaks() as $snak ) {
			$propId = $snak->getPropertyId();
			$allPropertyIds[$propId->getSerialization()] = $propId;
		}
		$this->propertyExistence = $this->entityExistenceChecker->existsBatch(
			array_values( $allPropertyIds )
		);
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
			'implode' => function( $separator, $array ) {
				return implode( $separator, $array );
			},
			'$i18n' => function ( string $messageKey, string ...$params ): string {
				return $this->textProvider->get( $messageKey, $params );
			},
		];
	}

	private function registerComponentTemplate(
		string $componentName,
		?callable $computedFunctions = null
	): void {
		$this->app->registerComponentTemplate(
			$componentName,
			$this->componentsFactory->getTemplateCallable( $componentName ),
			$computedFunctions
		);
	}

	// TODO: T429596 - Refactoring is needed since we've introduced the factory Wbui2025ComponentsFactory.
	private function registerTemplates( StatementList $allStatements ): void {
		$this->registerStatementSectionsView();
		$this->registerMainSnakView();
		$this->registerStatementGroupView( $allStatements );
		$this->registerStatementView( $allStatements );
		$this->registerPropertyNameView();
		$this->registerReferencesView();
		$this->registerQualifiersView();
		$this->registerSnakValueView();
	}

	private function registerStatementSectionsView(): void {
		$this->registerComponentTemplate(
			'wbui2025-statement-sections',
			function ( array $data ): array {
				$data[ 'propertyIds' ] = $data[ 'propertyList' ];
				$data[ 'javaScriptLoaded' ] = false;
				return $data;
			}
		);
	}

	private function registerMainSnakView(): void {
		$this->registerComponentTemplate(
			'wbui2025-main-snak',
			function( array $data ): array {
				$data['rankTitleString'] = $this->textProvider->get(
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

	private function registerStatementGroupView( StatementList $allStatements ): void {
		$this->registerComponentTemplate(
			'wbui2025-statement-group-view',
			function( array $data ) use ( $allStatements ): array {
				/** @var PropertyId $propertyId */
				$propertyId = $this->entityIdParser
					->parse( $data['propertyId'] );
				'@phan-var PropertyId $propertyId';
				$data['isDeletedProperty'] = !( $this->propertyExistence[$propertyId->getSerialization()] ?? false );
				$data['statements'] = array_map(
					$this->statementSerializer->serialize( ... ),
					$allStatements->getByPropertyId( $propertyId )->toArray()
				);
				$data['showModalEditForm'] = false;
				return $data;
			}
		);
	}

	private function registerStatementView( StatementList $allStatements ): void {
		$this->registerComponentTemplate(
			'wbui2025-statement-view',
			function ( array $data ) use ( $allStatements ): array {
				$statementId = $data['statementId'];
				$statementById = $allStatements->getFirstStatementWithGuid( $statementId );
				Assert::invariant( $statementById !== null, "Statement $statementId not found" );
				$data['statement'] = $this->statementSerializer->serialize( $statementById );
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

	private function registerPropertyNameView(): void {
		$this->registerComponentTemplate(
			'wbui2025-property-name',
			function ( array $data ): array {
				/** @var PropertyId $propertyId */
				$propertyId = $this->entityIdParser
					->parse( $data['propertyId'] );
				'@phan-var PropertyId $propertyId';

				$data['propertyLinkHtml'] = $this->entityIdFormatterFactory
					->getEntityIdFormatter( $this->language )
					->formatEntityId( $propertyId );
				$data['isDeletedProperty'] = !( $this->propertyExistence[$propertyId->getSerialization()] ?? false );
				return $data;
			}
		);
	}

	private function registerReferencesView(): void {
		$this->registerComponentTemplate(
			'wbui2025-references',
			function ( array $data ): array {
				$data['referenceCount'] = count( $data['references'] );
				$data['hasReferences'] = $data['referenceCount'] > 0;
				$data['referencesMessage'] = $this->textProvider->getEscaped(
					'wikibase-statementview-references-counter', [
						strval( $data[ 'referenceCount' ] ),
					],
				);
				$data['showReferences'] = false;
				$data['showIndicators'] = false;
				return $data;
			}
		);
	}

	private function registerQualifiersView(): void {
		$this->registerComponentTemplate(
			'wbui2025-qualifiers',
			function ( array $data ): array {
				$qualifierCount = count( $data['qualifiers'] );
				$data['hasQualifiers'] = $qualifierCount > 0;
				$data['showIndicators'] = false;
				return $data;
			}
		);
	}

	private function registerSnakValueView(): void {
		$this->registerComponentTemplate(
			'wbui2025-snak-value',
			function ( array $data ): array {
				/** @var PropertyId $propertyId */
				$propertyId = $this->entityIdParser
					->parse( $data['snak']['property'] );
				'@phan-var PropertyId $propertyId';
				try {
					$dataType = $this->propertyDataTypeLookup->getDataTypeIdForProperty( $propertyId );
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
	 * @param string $sectionKey the identifier for this statement section
	 * @param string $sectionHeadingHtml Section heading as HTML
	 * @param StatementList $statementsList
	 * @return string Rendered HTML
	 */
	public function renderStatementsSectionHtml(
		string $entityId,
		string $sectionKey,
		string $sectionHeadingHtml,
		StatementList $statementsList,
	): string {
		$propertyList = [];
		foreach ( $statementsList->getPropertyIds() as $propertyId ) {
			$propertyStatements = $statementsList->getByPropertyId( $propertyId )->toArray();
			$propertyList[] = $propertyId->getSerialization();

			foreach ( $propertyStatements as $statement ) {
				$mainSnak = $statement->getMainSnak();
				$statementData = $this->statementSerializer->serialize( $statement );

				$this->populateReferenceSnakValueHtml( $statement, $statementData );
				$this->populateQualifierSnakValueHtml( $statement, $statementData );
				if ( array_key_exists( 'hash', $statementData['mainsnak'] ) ) {
					$this->snakValueHtmlLookup[$statementData['mainsnak']['hash']] = $this->snakFormatter->formatSnak( $mainSnak );
				}
			}
		}

		return $this->app->renderComponent( 'wbui2025-statement-sections', [
			'sectionHeadingHtml' => $sectionHeadingHtml,
			'sectionKey' => $sectionKey,
			'propertyList' => $propertyList,
			'entityId' => $entityId,
		] );
	}
}
