<?php

namespace Wikibase\View;

use InvalidArgumentException;
use MediaWiki\MediaWikiServices;
use Traversable;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Statement\Grouper\StatementGrouper;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\View\Template\TemplateFactory;
use WMDE\VueJsTemplating\App;

/**
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class StatementSectionsView {

	/**
	 * @var TemplateFactory
	 */
	private $templateFactory;

	/**
	 * @var StatementGrouper
	 */
	private $statementGrouper;

	/**
	 * @var StatementGroupListView
	 */
	private $statementListView;

	/**
	 * @var LocalizedTextProvider
	 */
	private $textProvider;

	/**
	 * @var SnakHtmlGenerator
	 */
	private $snakHtmlGenerator;

	/**
	 * @var SnakFormatter
	 */
	private $snakFormatter;

	/**
	 * @var SerializerFactory
	 */
	private $serializerFactory;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $propertyDataTypeLookup;

	/**
	 * @var string
	 */
	private $languageCode;

	/**
	 * @var bool
	 */
	private $vueStatementsView;

	public function __construct(
		TemplateFactory $templateFactory,
		StatementGrouper $statementGrouper,
		StatementGroupListView $statementListView,
		LocalizedTextProvider $textProvider,
		SnakHtmlGenerator $snakHtmlGenerator,
		SnakFormatter $snakFormatter,
		SerializerFactory $serializerFactory,
		PropertyDataTypeLookup $propertyDataTypeLookup,
		string $languageCode,
		bool $vueStatementsView
	) {
		$this->templateFactory = $templateFactory;
		$this->statementGrouper = $statementGrouper;
		$this->statementListView = $statementListView;
		$this->textProvider = $textProvider;
		$this->snakHtmlGenerator = $snakHtmlGenerator;
		$this->snakFormatter = $snakFormatter;
		$this->serializerFactory = $serializerFactory;
		$this->propertyDataTypeLookup = $propertyDataTypeLookup;
		$this->languageCode = $languageCode;
		$this->vueStatementsView = $vueStatementsView;
	}

	private function populateReferenceSnakHtml( Statement $statement, array $statementData, array &$snakHtmlLookup ) {
		if ( !array_key_exists( 'references', $statementData ) || !$statementData['references'] ) {
			return;
		}
		foreach ( $statementData['references'] as $referenceData ) {
			$reference = $statement->getReferences()->getReference( $referenceData['hash'] );
			foreach ( $reference->getSnaks() as $snakObject ) {
				foreach ( $referenceData['snaks'] as $snakList ) {
					foreach ( $snakList as $snakData ) {
						if ( $snakData['hash'] === $snakObject->getHash() ) {
							$snakHtmlLookup[$snakObject->getHash()] = $this->snakHtmlGenerator->getSnakHtml( $snakObject, true );
						}
					}
				}
			}
		}
	}

	private function populateQualifierSnakHtml( Statement $statement, array $statementData, array &$snakHtmlLookup ) {
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
				$snakHtmlLookup[$qualifierData['hash']] = $this->snakHtmlGenerator->getSnakHtml( $qualifier, true );
			}
		}
	}

	/**
	 * @param App $app The Vue App
	 * @param string $sectionHeadingHtml Section heading as HTML
	 * @param StatementList $statementsList
	 * @param array &$snakHtmlLookup
	 * @return string Rendered HTML
	 */
	private function renderStatementsSectionHtml(
		App $app,
		string $sectionHeadingHtml,
		StatementList $statementsList,
		array &$snakHtmlLookup
	): string {
		$propertyStatementMap = [];
		$propertyList = [];
		foreach ( $statementsList->getPropertyIds() as $propertyId ) {
			$propertyStatements = $statementsList->getByPropertyId( $propertyId )->toArray();
			$propertyList[] = $propertyId->getSerialization();

			$statementsData = [];
			foreach ( $propertyStatements as $statement ) {
				$mainSnak = $statement->getMainSnak();
				$statementSerializer = $this->serializerFactory->newStatementSerializer();
				$statementData = $statementSerializer->serialize( $statement );

				$dataType = $this->propertyDataTypeLookup->getDataTypeIdForProperty( $mainSnak->getPropertyId() );
				$statementData['mainsnak']['datatype'] = $dataType;
				$this->populateReferenceSnakHtml( $statement, $statementData, $snakHtmlLookup );
				$this->populateQualifierSnakHtml( $statement, $statementData, $snakHtmlLookup );
				if ( array_key_exists( 'hash', $statementData['mainsnak'] ) ) {
					$snakHtmlLookup[$statementData['mainsnak']['hash']] = $this->snakFormatter->formatSnak( $mainSnak );
				}
				$statementsData[] = $statementData;
			}
			$propertyStatementMap[$propertyId->getSerialization()] = $statementsData;
		}

		return $app->renderComponent( 'wbui2025-statement-sections', [
			'sectionHeadingHtml' => $sectionHeadingHtml,
			'propertyList' => $propertyList,
			'propertyStatementMap' => $propertyStatementMap,
		] );
	}

	/**
	 * @param StatementList[] $statementsLists
	 * @param App $app
	 * @param array &$snakHtmlLookup
	 * @return string HTML
	 */
	private function getVueStatementSectionsHtml( array $statementsLists, App $app, array &$snakHtmlLookup ): string {
		$rendered = '';
		foreach ( $this->iterateOverNonEmptyStatementSections( $statementsLists ) as $key => $statementsList ) {
			$rendered .= $this->renderStatementsSectionHtml(
				$app,
				$this->getHtmlForSectionHeading( $key ),
				$statementsList,
				$snakHtmlLookup
			);
		}
		return $rendered;
	}

	private function setupVueTemplateRenderer( array &$snakHtmlLookup ): App {
		$app = new App( [
			'snakHtml' => function ( $snak ) use ( &$snakHtmlLookup ) {
				if ( array_key_exists( $snak['hash'], $snakHtmlLookup ) ) {
					return $snakHtmlLookup[$snak['hash']];
				}
				return '<p>No server-side HTML stored for snak ' . $snak['hash'] . '</p>';
			},
			'concat' => function( ...$args ) {
				return implode( '', $args );
			},
		] );
		$app->registerComponentTemplate(
			'wbui2025-statement-sections',
			file_get_contents( __DIR__ . '/../../repo/resources/wikibase.wbui2025/wikibase.wbui2025.statementSections.vue' ),
		);
		$app->registerComponentTemplate(
			'wbui2025-statement',
			file_get_contents( __DIR__ . '/../../repo/resources/wikibase.wbui2025/wikibase.wbui2025.statementView.vue' ),
		);
		$app->registerComponentTemplate(
			'wbui2025-statement-detail',
			file_get_contents( __DIR__ . '/../../repo/resources/wikibase.wbui2025/wikibase.wbui2025.statementDetailView.vue' ),
			function ( array $data ): array {
				$data['references'] = array_key_exists( 'references', $data['statement'] ) ? $data['statement']['references'] : [];
				$data['qualifiers'] = array_key_exists( 'qualifiers', $data['statement'] ) ? $data['statement']['qualifiers'] : [];
				$data['qualifiersOrder'] =
					array_key_exists( 'qualifiers-order', $data['statement'] ) ? $data['statement']['qualifiers-order'] : [];
				$data['statementDump'] = json_encode( $data['statement'] );
				return $data;
			}
		);
		$app->registerComponentTemplate(
			'wbui2025-property-name',
			fn () => file_get_contents( __DIR__ . '/../../repo/resources/wikibase.wbui2025/wikibase.wbui2025.propertyName.vue' ),
			function ( array $data ): array {
				$propertyId = WikibaseRepo::getEntityIdParser() // TODO inject (T396633)
					->parse( $data['propertyId'] );
				$data['propertyLinkHtml'] = WikibaseRepo::getEntityIdHtmlLinkFormatterFactory() // TODO inject (T396633)
					->getEntityIdFormatter( MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( $this->languageCode ) )
					->formatEntityId( $propertyId );
				return $data;
			}
		);
		$app->registerComponentTemplate(
			'wbui2025-main-snak',
			fn () => file_get_contents( __DIR__ . '/../../repo/resources/wikibase.wbui2025/wikibase.wbui2025.mainSnak.vue' ),
			function ( array $data ): array {
				$dataType = $data['type'];

				$data['snakValueClass'] = [
					'wikibase-wbui2025-media-value' => $dataType == 'commonsMedia',
					'wikibase-wbui2025-time-value' => $dataType == 'time',
				];

				return $data;
			}
		);
		$app->registerComponentTemplate(
			'wbui2025-references',
			fn () => file_get_contents( __DIR__ . '/../../repo/resources/wikibase.wbui2025/wikibase.wbui2025.references.vue' ),
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
		$app->registerComponentTemplate(
			'wbui2025-qualifiers',
			fn () => file_get_contents( __DIR__ . '/../../repo/resources/wikibase.wbui2025/wikibase.wbui2025.qualifiers.vue' ),
			function ( array $data ): array {
				$qualifierCount = count( $data['qualifiers'] );
				$data['hasQualifiers'] = $qualifierCount > 0;
				$data['qualifiersMessage'] = $this->textProvider->getEscaped(
					'wikibase-statementview-qualifiers-counter', [
						strval( $qualifierCount ),
					],
				);
				return $data;
			}
		);
		return $app;
	}

	/**
	 * @param StatementList[] $statementsLists
	 * @return string HTML
	 */
	private function getVueStatementsHtml( array $statementsLists ): string {
		$snakHtmlLookup = [];
		$app = $this->setupVueTemplateRenderer( $snakHtmlLookup );

		return "<div id='wikibase-wbui2025-statementgrouplistview'>" .
			$this->getVueStatementSectionsHtml( $statementsLists, $app, $snakHtmlLookup ) .
			"</div>";
	}

	/**
	 * @param StatementList[] $statementLists
	 */
	private function iterateOverNonEmptyStatementSections( array $statementLists ): Traversable {
		foreach ( $statementLists as $key => $statements ) {
			if ( !is_string( $key ) || !( $statements instanceof StatementList ) ) {
				throw new InvalidArgumentException(
					'$statementLists must be an associative array of StatementList objects'
				);
			}

			if ( $key !== 'statements' && $statements->isEmpty() ) {
				continue;
			}

			yield $key => $statements;
		}
	}

	/**
	 * @param StatementList $statementList
	 * @param bool $wbui2025Ready whether the caller supports wbui2025
	 *
	 * @throws InvalidArgumentException
	 * @return string HTML
	 */
	public function getHtml( StatementList $statementList, bool $wbui2025Ready = false ) {
		$statementLists = $this->statementGrouper->groupStatements( $statementList );
		if ( $wbui2025Ready && $this->vueStatementsView ) {
			return $this->getVueStatementsHtml( $statementLists );
		}

		$html = '';
		foreach ( $this->iterateOverNonEmptyStatementSections( $statementLists ) as $key => $statements ) {
			$html .= $this->getHtmlForSectionHeading( $key );
			$html .= $this->statementListView->getHtml( $statements->toArray() );
		}

		return $html;
	}

	/**
	 * @param string $key
	 *
	 * @return string HTML
	 */
	private function getHtmlForSectionHeading( $key ) {
		/**
		 * Message keys:
		 * wikibase-statementsection-statements
		 * wikibase-statementsection-identifiers
		 */
		$messageKey = 'wikibase-statementsection-' . strtolower( $key );
		$className = 'wikibase-statements';

		if ( $key === 'statements' ) {
			$id = 'claims';
		} else {
			$id = $key;
			$className .= ' wikibase-statements-' . $key;
		}

		// TODO: Add link to SpecialPage that allows adding a new statement.
		return $this->templateFactory->render(
			'wb-section-heading',
			$this->textProvider->getEscaped( $messageKey ),
			$id,
			$className
		);
	}

}
