<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\FederatedProperties\Diff;

use DerivativeContext;
use RequestContext;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Repo\Content\ItemContent;
use Wikibase\Repo\Diff\EntityContentDiffView;
use Wikibase\Repo\Tests\FederatedProperties\FederatedPropertiesTestCase;

/**
 * @covers \Wikibase\Repo\Diff\EntityContentDiffView
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class EntityContentDiffViewTest extends FederatedPropertiesTestCase {

	public function testGenerateContentDiffBodyWithStatement_doesNotResultInException() {
		$this->setSourceWikiUnavailable();

		$item = new Item( new ItemId( 'Q11' ) );
		$item2 = new Item( new ItemId( 'Q12' ) );
		$item2->setStatements( new StatementList( new Statement(
			new PropertyNoValueSnak( $this->newFederatedPropertyIdFromPId( 'P10' ) )
		) ) );
		$itemContent = ItemContent::newFromItem( $item );
		$itemContent2 = ItemContent::newFromItem( $item2 );

		$html = $this->newDiffView()->generateContentDiffBody( $itemContent, $itemContent2 );

		$this->assertIsString( $html );
		$this->assertNotEmpty( $html );
	}

	/**
	 * @return EntityContentDiffView
	 */
	private function newDiffView() {
		$context = new DerivativeContext( RequestContext::getMain() );
		$context->setLanguage( $this->getServiceContainer()->getLanguageFactory()->getLanguage( 'en' ) );

		return new EntityContentDiffView( $context );
	}

}
