<?php

namespace Wikibase\Client\Specials;

use HTMLForm;
use Html;
use InvalidArgumentException;
use Linker;
use QueryPage;
use Skin;
use Title;
use Wikibase\Client\WikibaseClient;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\DataModel\Entity\ItemId;

/**
 * Get usage of any given entity.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani (ladsgroup@gmail.com)
 */
class SpecialEntityUsage extends QueryPage {

	/**
	 * @var ItemId|null
	 */
	private $entityId;

	/**
	 * @see SpecialPage::__construct
	 *
	 * @param string $name
	 */
	public function __construct( $name = 'EntityUsage' ) {
		parent::__construct( $name );
	}

	/**
	 * @see QueryPage::execute
	 *
	 * @param string $subPage
	 */
	public function execute( $subPage ) {
		$this->prepareParams( $subPage );

		if ( $this->entityId !== null ) {
			parent::execute( $subPage );
		} else {
			$this->setHeaders();
			$this->outputHeader();
			$this->getOutput()->addHTML( $this->getPageHeader() );
		}
	}

	private function prepareParams( $subPage ) {
		$entity = $this->getRequest()->getText( 'entity', $subPage );

		if ( $entity ) {
			try {
				$this->entityId = new ItemId( $entity );
			} catch ( InvalidArgumentException $ex ) {
				$this->getOutput()->addHTML(
					Html::element(
						'p',
						[
							'class' => 'error'
						],
						$this->msg( 'wikibase-entityusage-invalid-id', $entity )
					)
				);
			}
		}
	}

	/**
	 * @see QueryPage::getPageHeader
	 *
	 * @return string
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
				'default' => $this->msg( 'wikibase-entityusage-submit' )->text()
			]
		];

		if ( $this->entityId !== null ) {
			$formDescriptor['entity']['default'] = $this->entityId->getSerialization();
		}

		return HTMLForm::factory( 'inline', $formDescriptor, $this->getContext() )
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
		$groupConcat = wfGetDB( DB_REPLICA )
			->buildGroupConcatField( '|', 'wbc_entity_usage',
				'eu_aspect', $conds, $joinConds
		);

		return [
			'tables' => [
				'page',
				'wbc_entity_usage'
			],
			'fields' => [
				'value' => 'page_id',
				'namespace' => 'page_namespace',
				'title' => 'page_title',
				'aspects' => $groupConcat,
				'eu_page_id',
			],
			'conds' => $conds,
			'options' => ['GROUP BY' => 'eu_page_id' ],
			'join_conds' => $joinConds
		];
	}

	/**
	 * @see QueryPage::formatResult
	 *
	 * @param Skin $skin
	 * @param object $result
	 *
	 * @return string
	 */
	public function formatResult( $skin, $row ) {
		global $wgContLang;

		$title = Title::makeTitleSafe( $row->namespace, $row->title );
		$aspects = $this->formatAspects( $row->aspects );
		if ( $title instanceof Title ) {
			$text = $wgContLang->convert( $title->getPrefixedText() );
			return Linker::link( $title, htmlspecialchars( $text ) ) . ': ' . $aspects;
		} else {
			return Html::element( 'span', [ 'class' => 'mw-invalidtitle' ],
				Linker::getInvalidTitleDescription( $this->getContext(), $row->namespace, $row->title ) );
		}
	}

	/**
	 * @param string $rowAspects
	 * @return string
	 */
	public function formatAspects( $rowAspects ) {
		$rowAspects = explode( '|', $rowAspects );
		$aspects = [];

		foreach ( $rowAspects as $aspect ) {
			$aspect = EntityUsage::splitAspectKey( $aspect );
			$aspects[] = $this->getContext()->msg(
				'wikibase-pageinfo-entity-usage-' . $aspect[0], $aspect[1] )->parse();
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
	 * @return array
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
