<?php

namespace Wikibase\View;

use InvalidArgumentException;
use MediaWiki\MediaWikiServices;
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
	 * @param string $propertyId
	 * @param Statement[] $statements
	 * @param App $app
	 * @param array &$snakHtmlLookup
	 * @return string HTML
	 */
	private function getVueStatementHtml( string $propertyId, array $statements, App $app, array &$snakHtmlLookup ): string {
		$statementsData = [];
		foreach ( $statements as $statement ) {
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

		return $app->renderComponent( 'wbui2025-statement', [
			'statements' => $statementsData,
			'propertyId' => $propertyId,
		] );
	}

	/** @return string HTML */
	private function getVueStatementsHtml( StatementList $statementsList ): string {
		$snakHtmlLookup = [];
		$app = new App( [ 'snakHtml' => function ( $snak ) use ( &$snakHtmlLookup ) {
			if ( array_key_exists( $snak['hash'], $snakHtmlLookup ) ) {
				return $snakHtmlLookup[$snak['hash']];
			}
			return '<p>No server-side HTML stored for snak ' . $snak['hash'] . '</p>';
		} ] );
		$app->registerComponentTemplate(
			'wbui2025-statement',
			file_get_contents( __DIR__ . '/../../repo/resources/wikibase.mobileUi/wikibase.mobileUi.statementView.vue' ),
		);
		$app->registerComponentTemplate(
			'wbui2025-statement-detail',
			file_get_contents( __DIR__ . '/../../repo/resources/wikibase.mobileUi/wikibase.mobileUi.statementDetailView.vue' ),
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
			fn () => file_get_contents( __DIR__ . '/../../repo/resources/wikibase.mobileUi/wikibase.mobileUi.propertyName.vue' ),
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
			fn () => file_get_contents( __DIR__ . '/../../repo/resources/wikibase.mobileUi/wikibase.mobileUi.mainSnak.vue' ),
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
			fn () => file_get_contents( __DIR__ . '/../../repo/resources/wikibase.mobileUi/wikibase.mobileUi.references.vue' ),
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
			fn () => file_get_contents( __DIR__ . '/../../repo/resources/wikibase.mobileUi/wikibase.mobileUi.qualifiers.vue' ),
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

		$rendered = '';
		// Renders a placeholder statement element for each property, creating a mounting point for the client-side version
		foreach ( $statementsList->getPropertyIds() as $propertyId ) {
			$statements = $statementsList->getByPropertyId( $propertyId )->toArray();
			$renderedStatement = $this->getVueStatementHtml( $propertyId, $statements, $app, $snakHtmlLookup );
			$rendered .= "<div id='wikibase-wbui2025-statementwrapper-$propertyId'>$renderedStatement</div>";
		}

		return "<div id='wikibase-wbui2025-statementgrouplistview'>$rendered</div>";
	}

	/**
	 * @param StatementList $statementList
	 * @param bool $wbui2025Ready whether the caller supports wbui2025
	 *
	 * @throws InvalidArgumentException
	 * @return string HTML
	 */
	public function getHtml( StatementList $statementList, bool $wbui2025Ready = false ) {
		if ( $wbui2025Ready && $this->vueStatementsView ) {
			return $this->getVueStatementsHtml( $statementList );
		}
		$statementLists = $this->statementGrouper->groupStatements( $statementList );
		$html = '';

		foreach ( $statementLists as $key => $statements ) {
			if ( !is_string( $key ) || !( $statements instanceof StatementList ) ) {
				throw new InvalidArgumentException(
					'$statementLists must be an associative array of StatementList objects'
				);
			}

			if ( $key !== 'statements' && $statements->isEmpty() ) {
				continue;
			}

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
