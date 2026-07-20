<?php

declare( strict_types = 1 );

namespace Wikibase\View;

use MediaWiki\Language\Language;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\DataModel\Serializers\StatementSerializer;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\Lib\Store\EntityExistenceChecker;
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
		$this->componentsFactory->registerTemplates(
			$this->app,
			$allStatements,
			$this->propertyExistence,
			$this->textProvider,
			$this->entityIdParser,
			$this->statementSerializer,
			$this->entityIdFormatterFactory,
			$this->language,
			$this->propertyDataTypeLookup
		);
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
