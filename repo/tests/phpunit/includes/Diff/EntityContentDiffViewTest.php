<?php

namespace Wikibase\Repo\Tests\Diff;

use Content;
use DerivativeContext;
use MediaWikiIntegrationTestCase;
use RequestContext;
use Title;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Content\ItemContent;
use Wikibase\Repo\Diff\EntityContentDiffView;

/**
 * @covers \Wikibase\Repo\Diff\EntityContentDiffView
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thiemo Kreuz
 */
class EntityContentDiffViewTest extends MediaWikiIntegrationTestCase {

	public function testConstructor() {
		new EntityContentDiffView( RequestContext::getMain() );
		$this->assertTrue( true );
	}

	/**
	 * @dataProvider provideEmptyDiffs
	 */
	public function testEmptyDiffs( Content $oldContent, Content $newContent ) {
		$html = $this->newDiffView()->generateContentDiffBody( $oldContent, $newContent );
		$this->assertSame( '', $html );
	}

	public function provideEmptyDiffs() {
		$item = new Item( new ItemId( 'Q1' ) );
		$emptyContent1 = ItemContent::newFromItem( clone $item );
		$emptyContent2 = ItemContent::newFromItem( clone $item );

		$item->setLabel( 'en', 'Not empty any more' );
		$itemContent1 = ItemContent::newFromItem( clone $item );
		$itemContent2 = ItemContent::newFromItem( clone $item );

		return [
			'same object' => [ $itemContent1, $itemContent1 ],
			'empty objects' => [ $emptyContent1, $emptyContent2 ],
			'two non-empty equal objects' => [ $itemContent1, $itemContent2 ],
		];
	}

	public function itemProvider() {
		$emptyItem = new Item( new ItemId( 'Q1' ) );

		$item = new Item( new ItemId( 'Q11' ) );
		$item->setDescription( 'en', 'ohi there' );
		$item->setLabel( 'de', 'o_O' );
		$item->setAliases( 'nl', [ 'foo', 'bar' ] );

		$item2 = new Item( new ItemId( 'Q12' ) );
		$item2->setLabel( 'de', 'o_O' );
		$item2->setLabel( 'en', 'O_o' );
		$item2->setAliases( 'nl', [ 'daaaah' ] );

		$redirect = new EntityRedirect( new ItemId( 'Q11' ), new ItemId( 'Q21' ) );
		$redirect2 = new EntityRedirect( new ItemId( 'Q11' ), new ItemId( 'Q22' ) );

		$insTags = [
			'has <td>label / de</td>' => '>label / de</td>',
			'has <ins>foo</ins>' => '>foo</ins>',
			'has <td>aliases / nl / 0</td>' => '>aliases / nl / 0</td>',
			'has <ins>bar</ins>' => '>bar</ins>',
			'has <td>description / en</td>' => '>description / en</td>',
			'has <ins>ohi there</ins>' => '>ohi there</ins>',
		];

		$delTags = [
			'has <td>label / de</td>' => '>label / de</td>',
			'has <del>foo</del>' => '>foo</del>',
			'has <td>aliases / nl / 0</td>' => '>aliases / nl / 0</td>',
			'has <del>bar</del>' => '>bar</del>',
			'has <td>description / en</td>' => '>description / en</td>',
			'has <del>ohi there</del>' => '>ohi there</del>',
		];

		$changeTags = [
			'has <td>label / en</td>' => '>label / en</td>',
			'has <ins>O_o</ins>' => '>O_o</ins>',
			'has <td>aliases / nl / 0</td>' => '>aliases / nl / 0</td>',
			'has <ins>daaaah</ins>' => '>daaaah</ins>',
			'has <td>aliases / nl / 1</td>' => '>aliases / nl / 1</td>',
			'has <del>foo</del>' => '>foo</del>',
			'has <td>aliases / nl / 2</td>' => '>aliases / nl / 2</td>',
			'has <del>bar</del>' => '>bar</del>',
			'has <td>description / en</td>' => '>description / en</td>',
			'has <del>ohi there</del>' => '>ohi there</del>',
		];

		$fromRedirTags = [
			'has <td>label / de</td>' => '>label / de</td>',
			'has <ins>foo</ins>' => '>foo</ins>',

			'has <td>redirect</td>' => '>redirect</td>',
			'has <del>Q21</del>' => '>Q21</del>',
		];

		$toRedirTags = [
			'has <td>label / de</td>' => '>label / de</td>',
			'has <del>foo</del>' => '>foo</del>',

			'has <td>redirect</td>' => '>redirect</td>',
			'has <ins>Q21</ins>' => '>Q21</ins>',
		];

		$changeRedirTags = [
			'has <td>redirect</td>' => '>redirect</td>',
			'has <del>Q21</del>' => '>Q21</del>',
			'has <ins>Q22</del>' => '>Q22</ins>',
		];

		$empty = ItemContent::newFromItem( $emptyItem );
		$itemContent = ItemContent::newFromItem( $item );
		$itemContent2 = ItemContent::newFromItem( $item2 );

		$redirectContent = ItemContent::newFromRedirect(
			$redirect,
			$this->createMock( Title::class )
		);
		$redirectContent2 = ItemContent::newFromRedirect(
			$redirect2,
			$this->createMock( Title::class )
		);

		return [
			'from empty' => [ $empty, $itemContent, $insTags ],
			'to empty' => [ $itemContent, $empty, $delTags ],
			'changed' => [ $itemContent, $itemContent2, $changeTags ],
			'to redirect' => [ $itemContent, $redirectContent, $toRedirTags ],
			'from redirect' => [ $redirectContent, $itemContent, $fromRedirTags ],
			'redirect changed' => [ $redirectContent, $redirectContent2, $changeRedirTags ],
		];
	}

	/**
	 * @dataProvider itemProvider
	 */
	public function testGenerateContentDiffBody( ItemContent $itemContent, ItemContent $itemContent2, array $matchers ) {
		$diffView = $this->newDiffView();
		$html = $diffView->generateContentDiffBody( $itemContent, $itemContent2 );

		$this->assertIsString( $html );
		$this->assertContains( 'wikibase.alltargets', $diffView->getOutput()->getModuleStyles() );
		foreach ( $matchers as $name => $matcher ) {
			$this->assertStringContainsString( $matcher, $html, $name );
		}
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
