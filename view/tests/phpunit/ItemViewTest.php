<?php

namespace Wikibase\View\Tests;

use Language;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Statement\Grouper\NullStatementGrouper;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\View\ItemView;
use Wikibase\View\Template\TemplateFactory;

/**
 * @covers Wikibase\View\ItemView
 * @covers Wikibase\View\EntityView
 *
 * @uses Wikibase\View\Template\Template
 * @uses Wikibase\View\Template\TemplateFactory
 * @uses Wikibase\View\Template\TemplateRegistry
 * @uses Wikibase\View\TextInjector
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class ItemViewTest extends EntityViewTest {

	protected function makeEntity( EntityId $id ) {
		$item = new Item( $id );

		$item->setLabel( 'en', "label:$id" );
		$item->setDescription( 'en', "description:$id" );

		return $item;
	}

	/**
	 * @return EntityId
	 */
	protected function getEntityId() {
		return new ItemId( "Q1" );
	}

	/**
	 * @return ItemView
	 */
	protected function newEntityView() {
		$templateFactory = TemplateFactory::getDefaultInstance();
		$itemView = new ItemView(
			$templateFactory,
			$this->getMockBuilder( 'Wikibase\View\EntityTermsView' )
				->disableOriginalConstructor()
				->getMock(),
			$this->getMockBuilder( 'Wikibase\View\StatementSectionsView' )
				->disableOriginalConstructor()
				->getMock(),
			Language::factory( 'qqx' ),
			$this->getMockBuilder( 'Wikibase\View\SiteLinksView' )
				->disableOriginalConstructor()
				->getMock(),
			array()
		);
		return $itemView;
	}

	public function provideTestGetHtml() {
		$id = $this->getEntityId();
		$item = $this->makeEntity( $id );

		// FIXME: add statements
		$statements = array();
		$item->setStatements( new StatementList( $statements ) );

		$cases = parent::provideTestGetHtml();
		$cases[] = array(
			$this->newEntityRevision( $item ),
			array(
				'CSS class' => '!class="wikibase-entityview wb-item"!', // FIXME: where?!
				// FIXME: make sure statements are shown
				// FIXME: make sure the termbox is shown
				// FIXME: make sure sitelinks are shown
			)
		);

		return $cases;
	}

}
