<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
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

	protected function makeEntity( EntityId $id, array $statements = array() ) {
		$item = new Item( $id );

		$item->setLabel( 'en', "label:$id" );
		$item->setDescription( 'en', "description:$id" );

		$item->setStatements( new StatementList( $statements ) );

		return $item;
	}

	/**
	 * Generates a suitable entity ID based on $n.
	 *
	 * @param int|string $n
	 *
	 * @return EntityId
	 */
	protected function makeEntityId( $n ) {
		return new ItemId( "Q$n");
	}

	public function provideTestGetHtml() {
		$templateFactory = TemplateFactory::getDefaultInstance();
		$itemView = new ItemView(
			$templateFactory,
			$this->getMockBuilder( 'Wikibase\View\EntityTermsView' )
				->disableOriginalConstructor()
				->getMock(),
			$this->getMockBuilder( 'Wikibase\View\StatementGroupListView' )
				->disableOriginalConstructor()
				->getMock(),
			$this->getMock( 'Language' ),
			$this->getMockBuilder( 'Wikibase\View\SiteLinksView' )
				->disableOriginalConstructor()
				->getMock()
		);

		return array(
			array(
				$itemView,
				$this->newEntityRevisionForStatements( array() ),
				'/wb-item/'
			)
		);
	}

}
