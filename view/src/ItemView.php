<?php

namespace Wikibase\View;

use InvalidArgumentException;
use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\DataModel\Term\AliasesProvider;
use Wikibase\DataModel\Term\DescriptionsProvider;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\View\Template\TemplateFactory;
use WMDE\VueJsTemplating\App;

/**
 * Class for creating views for Item instances.
 * For the Item this basically is what the Parser is for WikitextContent.
 *
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 * @author Daniel Werner
 */
class ItemView extends EntityView {

	/**
	 * @var StatementSectionsView
	 */
	private $statementSectionsView;

	/**
	 * @var SerializerFactory
	 */
	private $serializerFactory;

	/**
	 * @var SiteLinksView
	 */
	private $siteLinksView;

	/**
	 * @var string[]
	 */
	private $siteLinkGroups;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $propertyDataTypeLookup;

	/**
	 * @var LocalizedTextProvider
	 */
	private $textProvider;

	/**
	 * @var CacheableEntityTermsView
	 */
	private $entityTermsView;

	private SnakFormatter $snakFormatter;

	private SnakHtmlGenerator $snakHtmlGenerator;

	private bool $vueStatementsView;

	/**
	 * @see EntityView::__construct
	 *
	 * @param TemplateFactory $templateFactory
	 * @param CacheableEntityTermsView $entityTermsView
	 * @param LanguageDirectionalityLookup $languageDirectionalityLookup
	 * @param StatementSectionsView $statementSectionsView
	 * @param SerializerFactory $serializerFactory
	 * @param string $languageCode
	 * @param SiteLinksView $siteLinksView
	 * @param string[] $siteLinkGroups
	 * @param LocalizedTextProvider $textProvider
	 * @param PropertyDataTypeLookup $propertyDataTypeLookup
	 * @param SnakFormatter $snakFormatter
	 * @param SnakHtmlGenerator $snakHtmlGenerator
	 * @param bool $vueStatementsView
	 */
	public function __construct(
		TemplateFactory $templateFactory,
		CacheableEntityTermsView $entityTermsView,
		LanguageDirectionalityLookup $languageDirectionalityLookup,
		StatementSectionsView $statementSectionsView,
		SerializerFactory $serializerFactory,
		$languageCode,
		SiteLinksView $siteLinksView,
		array $siteLinkGroups,
		LocalizedTextProvider $textProvider,
		PropertyDataTypeLookup $propertyDataTypeLookup,
		SnakFormatter $snakFormatter,
		SnakHtmlGenerator $snakHtmlGenerator,
		bool $vueStatementsView
	) {
		parent::__construct( $templateFactory, $languageDirectionalityLookup, $languageCode );

		$this->statementSectionsView = $statementSectionsView;
		$this->serializerFactory = $serializerFactory;
		$this->siteLinksView = $siteLinksView;
		$this->siteLinkGroups = $siteLinkGroups;
		$this->textProvider = $textProvider;
		$this->entityTermsView = $entityTermsView;
		$this->propertyDataTypeLookup = $propertyDataTypeLookup;
		$this->snakFormatter = $snakFormatter;
		$this->snakHtmlGenerator = $snakHtmlGenerator;
		$this->vueStatementsView = $vueStatementsView;
	}

	/**
	 * @inheritDoc
	 */
	public function getTitleHtml( EntityDocument $entity ) {
		if ( $entity instanceof LabelsProvider ) {
			return $this->entityTermsView->getTitleHtml(
				$entity->getId()
			);
		}

		return '';
	}

	/**
	 * Builds and returns the main content representing a whole WikibaseEntity
	 *
	 * @param EntityDocument $entity the entity to render
	 * @param int $revision The revision of the entity to render
	 *
	 * @return ViewContent
	 */
	public function getContent( EntityDocument $entity, $revision ): ViewContent {
		return new ViewContent(
			$this->renderEntityView( $entity ),
			$this->entityTermsView->getPlaceholders( $entity, $revision, $this->languageCode )
		);
	}

	/**
	 * @see EntityView::getMainHtml
	 *
	 * @param EntityDocument $item
	 *
	 * @throws InvalidArgumentException
	 * @return string HTML
	 */
	protected function getMainHtml( EntityDocument $item ) {
		if ( !( $item instanceof StatementListProvider ) ) {
			throw new InvalidArgumentException( '$item must be a StatementListProvider' );
		}

		$termsHtml = $this->getHtmlForTerms( $item );
		$tocHtml = $this->templateFactory->render( 'wikibase-toc' );
		$statementsHtml = $this->vueStatementsView ?
			$this->getVueStatementsHtml( $item ) :
			$this->statementSectionsView->getHtml( $item->getStatements() );

		return $termsHtml . $tocHtml . $statementsHtml;
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
	 * @param Statement $statement
	 * @param App $app
	 * @return string HTML
	 */
	private function getVueStatementHtml( Statement $statement, App $app, array &$snakHtmlLookup ): string {
		$mainSnak = $statement->getMainSnak();
		$statementSerializer = $this->serializerFactory->newStatementSerializer();
		// XXX: The serialization is mostly unused (only "propertyId" seems to be used)
		$statementData = $statementSerializer->serialize( $statement );

		$dataType = $this->propertyDataTypeLookup->getDataTypeIdForProperty( $mainSnak->getPropertyId() );
		$statementData['mainsnak']['datatype'] = $dataType;
		$this->populateReferenceSnakHtml( $statement, $statementData, $snakHtmlLookup );
		$this->populateQualifierSnakHtml( $statement, $statementData, $snakHtmlLookup );
		if ( array_key_exists( 'hash', $statementData['mainsnak'] ) ) {
			$snakHtmlLookup[$statementData['mainsnak']['hash']] = $this->snakFormatter->formatSnak( $mainSnak );
		}

		return $app->renderComponent( 'mex-statement', [
			'statement' => $statementData,
		] );
	}

	/** @return string HTML */
	private function getVueStatementsHtml( StatementListProvider $item ): string {
		$snakHtmlLookup = [];
		$app = new App( [ 'snakHtml' => function ( $snak ) use ( &$snakHtmlLookup ) {
			if ( array_key_exists( $snak['hash'], $snakHtmlLookup ) ) {
				return $snakHtmlLookup[$snak['hash']];
			}
			return '<p>No server-side HTML stored for snak ' . $snak['hash'] . '</p>';
		} ] );
		$app->registerComponentTemplate(
			'mex-statement',
			file_get_contents( __DIR__ . '/../../repo/resources/wikibase.mobileUi/wikibase.mobileUi.statementView.vue' ),
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
			'mex-property-name',
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
			'mex-main-snak',
			fn () => file_get_contents( __DIR__ . '/../../repo/resources/wikibase.mobileUi/wikibase.mobileUi.mainSnak.vue' ),
		);
		$app->registerComponentTemplate(
			'mex-references',
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
			'mex-qualifiers',
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
		foreach ( $item->getStatements()->getPropertyIds() as $propertyId ) {
			$statement = $item->getStatements()->getByPropertyId( $propertyId )->toArray()[ 0 ];
			$renderedStatement = $this->getVueStatementHtml( $statement, $app, $snakHtmlLookup );
			$rendered .= "<div id='wikibase-mex-statementwrapper-$propertyId'>$renderedStatement</div>";
		}

		return "<div id='wikibase-mex-statementgrouplistview'>$rendered</div>";
	}

	/**
	 * @see EntityView::getSideHtml
	 *
	 * @param EntityDocument $entity
	 *
	 * @throws InvalidArgumentException
	 * @return string HTML
	 */
	protected function getSideHtml( EntityDocument $entity ) {
		if ( !( $entity instanceof Item ) ) {
			throw new InvalidArgumentException( '$item must be an Item' );
		}

		return $this->getHtmlForPageImage()
			. $this->getHtmlForSiteLinks( $entity );
	}

	/**
	 * Builds and returns the HTML representing a WikibaseEntity's site-links.
	 *
	 * @param Item $item the entity to render
	 *
	 * @return string HTML
	 */
	private function getHtmlForSiteLinks( Item $item ) {
		return $this->siteLinksView->getHtml(
			$item->getSiteLinkList()->toArray(),
			$item->getId(),
			$this->siteLinkGroups
		);
	}

	/**
	 * Builds and returns the HTML representing a WikibaseEntity's page image.
	 *
	 * @return string
	 */
	private function getHtmlForPageImage() {
		return $this->templateFactory->render(
			'wikibase-pageimage',
			$this->textProvider->getEscaped( 'wikibase-pageimage-helptext' )
		);
	}

	/**
	 * Builds and returns the HTML for the entity's fingerprint.
	 *
	 * @param EntityDocument $entity
	 *
	 * @return string HTML
	 */
	protected function getHtmlForTerms( EntityDocument $entity ) {
		$id = $entity->getId();

		if ( $entity instanceof LabelsProvider && $entity instanceof DescriptionsProvider ) {
			return $this->entityTermsView->getHtml(
				$this->languageCode,
				$entity->getLabels(),
				$entity->getDescriptions(),
				$entity instanceof AliasesProvider ? $entity->getAliasGroups() : null,
				$id
			);
		}

		return '';
	}

}
