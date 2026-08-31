<?php

namespace Wikibase\Client\Specials;

use MediaWiki\Html\Html;
use MediaWiki\HTMLForm\HTMLForm;
use MediaWiki\Language\LanguageConverterFactory;
use MediaWiki\Linker\Linker;
use MediaWiki\Skin\Skin;
use MediaWiki\SpecialPage\QueryPage;
use MediaWiki\Title\Title;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\Lib\Rdbms\ClientDomainDb;
use Wikibase\Lib\Rdbms\ClientDomainDbFactory;
use Wikimedia\HtmlArmor\HtmlArmor;
use Wikimedia\Rdbms\FakeResultWrapper;
use Wikimedia\Rdbms\IReadableDatabase;
use Wikimedia\Rdbms\IResultWrapper;

/**
 * A special page that lists client wiki pages that use a given entity ID from the repository, and
 * which aspects each page uses.
 *
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani (ladsgroup@gmail.com)
 */
class SpecialEntityUsage extends QueryPage {

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/** @var LanguageConverterFactory */
	private $languageConverterFactory;

	/** @var ClientDomainDb */
	private $db;

	/**
	 * @var EntityId|null
	 */
	private $entityId = null;

	public function __construct(
		LanguageConverterFactory $languageConverterFactory,
		ClientDomainDbFactory $dbFactory,
		EntityIdParser $idParser
	) {
		parent::__construct( 'EntityUsage' );

		$this->idParser = $idParser;
		$this->languageConverterFactory = $languageConverterFactory;
		$this->db = $dbFactory->newLocalDb();
	}

	/**
	 * @see QueryPage::execute
	 *
	 * @param string|null $subPage
	 */
	public function execute( $subPage ) {
		$entity = $this->getRequest()->getText( 'entity', $subPage ?: '' );
		$this->prepareParams( $entity );

		if ( $this->entityId !== null ) {
			parent::execute( $subPage );
		} else {
			$this->setHeaders();
			$this->outputHeader();
			$this->getOutput()->addHTML( $this->getPageHeader() );
		}
	}

	/**
	 * @param string $entity
	 */
	public function prepareParams( $entity ) {
		if ( $entity ) {
			try {
				$this->entityId = $this->idParser->parse( $entity );
			} catch ( EntityIdParsingException ) {
				$this->getOutput()->addHTML(
					Html::element(
						'p',
						[
							'class' => 'error',
						],
						$this->msg( 'wikibase-entityusage-invalid-id', $entity )->text()
					)
				);
			}
		}
	}

	/**
	 * @see QueryPage::getPageHeader
	 *
	 * @return string HTML
	 */
	public function getPageHeader() {
		$formDescriptor = [
			'entity' => [
				'name' => 'entity',
				'type' => 'text',
				'id' => 'wb-entityusage-entity',
				'label-message' => 'wikibase-entityusage-entity',
			],
			'submit' => [
				'name' => '',
				'type' => 'submit',
				'id' => 'wikibase-entityusage-submit',
				'default' => $this->msg( 'wikibase-entityusage-submit' )->text(),
			],
		];

		if ( $this->entityId !== null ) {
			$formDescriptor['entity']['default'] = $this->entityId->getSerialization();
		}

		return HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext() )
			->setMethod( 'get' )
			->setWrapperLegendMsg( 'wikibase-entityusage-legend' )
			->suppressDefaultSubmit()
			->prepareForm()
			->getHTML( '' );
	}

	/**
	 * @see QueryPage::getQueryInfo
	 *
	 * @return array[]
	 */
	public function getQueryInfo() {
		$conds = [ 'eu_entity_id' => $this->entityId->getSerialization() ];
		$euDb = WikibaseClient::getEntityUsageDomainDb()->getReadConnection();

		return $euDb->newSelectQueryBuilder()
			->select(
				[
					'value' => 'eu_page_id',
					'aspects' => $euDb->buildGroupConcat( 'eu_aspect', '|' ),
					'eu_page_id',
				]
			)
			->table( 'wbc_entity_usage' )
			->andWhere( $conds )
			->groupBy( 'eu_page_id' )
			->getQueryInfo();
	}

	/**
	 * This method overrides the parent method to execute two queries instead of previous join:
	 * @param int|false $limit Numerical limit or false for no limit
	 * @param int|false $offset Numerical offset or false for no offset
	 * @return IResultWrapper
	 * @since 1.18
	 */
	public function reallyDoQuery( $limit, $offset = false ): IResultWrapper {
		$result = parent::reallyDoQuery( $limit, $offset );
		if ( $this->usesExternalSource() ) {
			return $result;
		}

		$euResultArr = iterator_to_array( $result );
		$pageIds = [];

		foreach ( $euResultArr as $page ) {
			$pageIds[] = $page->eu_page_id;
		}

		if ( count( $pageIds ) === 0 ) {
			return new FakeResultWrapper( [] );
		}

		$pages = $this->fetchPagesWithEntity( $pageIds );

		$fields = [ 'value', 'namespace', 'title' ];
		$joinedQueryRes = $this->joinResults( $euResultArr, $pages, $fields );
		return new FakeResultWrapper( $joinedQueryRes );
	}

	/**
	 * @see QueryPage::formatResult
	 *
	 * @param Skin $skin
	 * @param \stdClass $row
	 *
	 * @return string HTML
	 */
	public function formatResult( $skin, $row ) {
		$title = Title::makeTitleSafe( $row->namespace, $row->title );

		if ( !$title ) {
			return Html::element(
				'span',
				[ 'class' => 'mw-invalidtitle' ],
				Linker::getInvalidTitleDescription(
					$this->getContext(),
					$row->namespace,
					$row->title
				)
			);
		}

		$languageConverter = $this->languageConverterFactory->getLanguageConverter();
		$linkText = $languageConverter->convert( htmlspecialchars( $title->getPrefixedText() ) );
		return $this->getLinkRenderer()->makeLink(
				$title,
				new HtmlArmor( $linkText )
			) . $this->msg( 'colon-separator' )->escaped() . $this->formatAspects( $row->aspects );
	}

	/**
	 * @param string $rowAspects
	 *
	 * @return string
	 */
	public function formatAspects( $rowAspects ) {
		$rowAspects = explode( '|', $rowAspects );
		$aspects = [];

		foreach ( $rowAspects as $aspect ) {
			$aspect = EntityUsage::splitAspectKey( $aspect );
			// Possible messages:
			//   wikibase-pageinfo-entity-usage-L
			//   wikibase-pageinfo-entity-usage-L-with-modifier
			//   wikibase-pageinfo-entity-usage-D
			//   wikibase-pageinfo-entity-usage-D-with-modifier
			//   wikibase-pageinfo-entity-usage-C
			//   wikibase-pageinfo-entity-usage-C-with-modifier
			//   wikibase-pageinfo-entity-usage-S
			//   wikibase-pageinfo-entity-usage-T
			//   wikibase-pageinfo-entity-usage-X
			//   wikibase-pageinfo-entity-usage-O
			$msgKey = 'wikibase-pageinfo-entity-usage-' . $aspect[0];
			if ( $aspect[1] !== null ) {
				$msgKey .= '-with-modifier';
			}
			$aspects[] = $this->getContext()->msg( $msgKey, $aspect[1] )->parse();
		}

		return $this->getContext()->getLanguage()->commaList( $aspects );
	}

	/**
	 * @see QueryPage::isSyndicated
	 *
	 * @return bool
	 */
	public function isSyndicated() {
		return false;
	}

	/**
	 * @see QueryPage::isCacheable
	 *
	 * @return bool
	 */
	public function isCacheable() {
		return false;
	}

	/**
	 * @see QueryPage::linkParameters
	 *
	 * @return string[]
	 */
	public function linkParameters() {
		return [ 'entity' => $this->entityId->getSerialization() ];
	}

	/**
	 * @see SpecialPage::getGroupName
	 *
	 * @return string
	 */
	protected function getGroupName() {
		return 'pages';
	}

	/**
	 * @param array $pageIds
	 * @return IResultWrapper
	 */
	private function fetchPagesWithEntity( array $pageIds ): IResultWrapper {
		$db = $this->db->connections()->getReadConnection();
		$pageIds = array_unique( $pageIds );
		return $db->newSelectQueryBuilder()
			->select( [
				'value' => 'page_id',
				'namespace' => 'page_namespace',
				'title' => 'page_title',
			] )
			->from( 'page' )
			->where( [ 'page_id' => $pageIds ] )
			->caller( __METHOD__ . '::reallyDoQueryInternal' )
			->fetchResultSet();
	}

	/**
	 * @param array $euData
	 * @param IResultWrapper $pageData
	 * @param array $fieldsToCopy
	 * @return array
	 */
	private function joinResults( array $euData, IResultWrapper $pageData, array $fieldsToCopy ): array {
		$groupedByPageId = [];
		$joinedQueryRes = array_map( fn( object $row ): object => clone $row, $euData );

		foreach ( $pageData as $row ) {
			$groupedByPageId[(int)$row->value] = $row;
		}

		foreach ( $joinedQueryRes as $euRow ) {
			$pageId = (int)$euRow->eu_page_id;
			if ( isset( $groupedByPageId[$pageId] ) ) {
				$pageRow = $groupedByPageId[$pageId];
				foreach ( $fieldsToCopy as $field ) {
					$euRow->$field = $pageRow->$field;
				}
			}
		}
		return $joinedQueryRes;
	}

	protected function getRecacheDB(): IReadableDatabase {
		return WikibaseClient::getEntityUsageDomainDb()->getReadConnection( [ 'vslow' ] );
	}
}
