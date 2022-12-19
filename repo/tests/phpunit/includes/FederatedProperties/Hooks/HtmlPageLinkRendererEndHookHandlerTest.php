<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Test\Hooks\FederatedProperties;

use MediaWiki\Linker\LinkRenderer;
use RequestContext;
use Title;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Repo\FederatedProperties\ApiRequestExecutionException;
use Wikibase\Repo\Tests\Hooks\HtmlPageLinkRendererEndHookHandlerTestBase;

/**
 * @covers \Wikibase\Repo\Hooks\HtmlPageLinkRendererEndHookHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class HtmlPageLinkRendererEndHookHandlerTest extends HtmlPageLinkRendererEndHookHandlerTestBase {

	/**
	 * @dataProvider validLinkRendererAndContextProvider
	 */
	public function testDoHtmlPageLinkRendererBegin( LinkRenderer $linkRenderer, RequestContext $context ) {
		$handler = $this->newInstance( 'foo', false, true, Property::ENTITY_TYPE );

		$title = Title::makeTitle( WB_NS_PROPERTY, self::PROPERTY_WITH_LABEL );
		$text = $title->getFullText();
		$customAttribs = [];

		$ret = $handler->doHtmlPageLinkRendererEnd(
			$linkRenderer, $title, $text, $customAttribs, $context );

		$this->assertTrue( $ret );
		$this->assertStringContainsString( 'fedprop', $customAttribs['class'] );
		$this->assertContains( 'wikibase.federatedPropertiesLeavingSiteNotice', $context->getOutput()->getModules() );
	}

	public function testFederatedPropertiesFailure() {
		$customAttribs = [ 'class' => 'new another' ];
		$html = 'change-me';

		$this->entityUrlLookup->expects( $this->once() )
			->method( 'getLinkUrl' )
			->with( new ItemId( 'Q1' ) )
			->willThrowException( new ApiRequestExecutionException() );

		$returnValue = $this->newInstance( 'foo', false, true )->doHtmlPageLinkRendererEnd(
			$this->getLinkRenderer(),
			$this->newTitle( self::ITEM_WITH_LABEL ),
			$text,
			$customAttribs,
			$this->newContext(),
			$html
		);
		$this->assertTrue( $returnValue );

		// This will fallback to using the plain entity id as title and link text, and it will
		// link via Special:EntityData (as we can't lookup namespaces).
		$expectedAttribs = [
			'href' => 'http://source.wiki/script/index.php?title=Special:EntityData/Q1',
			'class' => 'another',
			'title' => 'Q1',
		];
		$this->assertSame( 'Q1', $text );
		$this->assertEquals( $expectedAttribs, $customAttribs );
	}

}
