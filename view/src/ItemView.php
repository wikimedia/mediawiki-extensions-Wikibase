<?php

namespace Wikibase\View;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\DataModel\Term\AliasesProvider;
use Wikibase\DataModel\Term\DescriptionsProvider;
use Wikibase\DataModel\Term\LabelsProvider;
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

	/**
	 * @param Statement $statement
	 * @param App $app
	 * @return string HTML
	 */
	private function getVueStatementHtml( Statement $statement, App $app ): string {
		$mainSnak = $statement->getMainSnak();
		$statementSerializer = $this->serializerFactory->newStatementSerializer();
		$statementData = $statementSerializer->serialize( $statement );

		$dataType = $this->propertyDataTypeLookup->getDataTypeIdForProperty( $mainSnak->getPropertyId() );
		$statementData['mainsnak']['datatype'] = $dataType;

		return $app->renderComponent( 'mex-statement', [ 'statement' => $statementData ] );
	}

	/** @return string HTML */
	private function getVueStatementsHtml( StatementListProvider $item ): string {
		$app = new App( [] );
		$app->registerComponentTemplate(
			'mex-statement',
			file_get_contents( __DIR__ . '/../../repo/resources/wikibase.mobileUi/wikibase.mobileUi.statementView.vue' )
		);
		$app->registerComponentTemplate(
			'mex-property-name',
			fn () => file_get_contents( __DIR__ . '/../../repo/resources/wikibase.mobileUi/wikibase.mobileUi.propertyName.vue' ),
			function ( array $data ): array {
				$propertyId = WikibaseRepo::getEntityIdParser() // TODO inject (T396633)
					->parse( $data['propertyId'] );
				$data['propertyUrl'] = WikibaseRepo::getEntityTitleLookup() // TODO inject (T396633)
					->getTitleForId( $propertyId )
					->getLinkURL();
				$data['propertyLabel'] = $propertyId->getSerialization(); // TODO get label (T396633)
				return $data;
			}
		);
		$app->registerComponentTemplate(
			'mex-main-snak',
			fn () => file_get_contents( __DIR__ . '/../../repo/resources/wikibase.mobileUi/wikibase.mobileUi.mainSnak.vue' ),
			function ( array $data ): array {
				$data['loadedValue'] = $data['value'];
				if ( $data['type'] === 'commonsMedia' ) {
					// The CommonsInlineImageFormatter loads additional metadata about commonsMedia objects.
					// We will need similar such functionality here - T398314
					$data['loadedValue'] = [
						'src' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/' .
							'd/d5/Rihanna-signature.svg/250px-Rihanna-signature.svg.png',
						'altText' => 'Some alt text',
						'filename' => $data['value'],
						'widthPx' => 348,
						'heightPx' => 178,
						'fileSizeKb' => 9,
					];
				}
				return $data;
			}
		);

		$rendered = '';
		// Renders a placeholder statement element for each property, creating a mounting point for the client-side version
		foreach ( $item->getStatements()->getPropertyIds() as $propertyId ) {
			$statement = $item->getStatements()->getByPropertyId( $propertyId )->toArray()[ 0 ];
			$renderedStatement = $this->getVueStatementHtml( $statement, $app );
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
