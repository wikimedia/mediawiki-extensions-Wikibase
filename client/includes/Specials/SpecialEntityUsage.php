<?php

namespace Wikibase\Client\Specials;

use Html;
use HtmlArmor;
use HTMLForm;
use Linker;
use MediaWiki\Languages\LanguageConverterFactory;
use QueryPage;
use Skin;
use Title;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\Lib\Rdbms\ClientDomainDb;
use Wikibase\Lib\Rdbms\ClientDomainDbFactory;

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
			} catch ( EntityIdParsingException $ex ) {
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
		$joinConds = [ 'wbc_entity_usage' => [ 'JOIN', [ 'page_id = eu_page_id' ] ] ];
		$conds = [ 'eu_entity_id' => $this->entityId->getSerialization() ];
		$groupConcat = $this->db->connections()->getReadConnection()->buildGroupConcatField(
			'|',
			'wbc_entity_usage',
			'eu_aspect',
			[ 'eu_page_id = page_id' ] + $conds
		);

		return [
			'tables' => [
				'page',
				'wbc_entity_usage',
			],
			'fields' => [
				'value' => 'page_id',
				'namespace' => 'page_namespace',
				'title' => 'page_title',
				'aspects' => $groupConcat,
				'eu_page_id',
			],
			'conds' => $conds,
			'options' => [ 'GROUP BY' => 'eu_page_id' ],
			'join_conds' => $joinConds,
		];
	}

	/**
	 * @see QueryPage::formatResult
	 *
	 * @param Skin $skin
	 * @param object $row
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

}
