<?php

namespace Wikibase\Test;

use DerivativeContext;
use Language;
use RequestContext;
use Title;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\ItemContent;
use Wikibase\Repo\Diff\EntityContentDiffView;

/**
 * @covers Wikibase\Repo\Diff\EntityContentDiffView
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group Database
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityContentDiffViewTest extends \MediaWikiTestCase {

	//@todo: make this a baseclass to use with all types of entities.

	public function testConstructor() {
		new EntityContentDiffView( RequestContext::getMain() );
		$this->assertTrue( true );
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
			'has <td>label / de</td>' => [ 'tag' => 'td', 'content' => 'label / de' ],
			'has <ins>foo</ins>' => [ 'tag' => 'ins', 'content' => 'foo' ],
			'has <td>aliases / nl / 0</td>' => [ 'tag' => 'td', 'content' => 'aliases / nl / 0' ],
			'has <ins>bar</ins>' => [ 'tag' => 'ins', 'content' => 'bar' ],
			'has <td>description / en</td>' => [ 'tag' => 'td', 'content' => 'description / en' ],
			'has <ins>ohi there</ins>' => [ 'tag' => 'ins', 'content' => 'ohi there' ],
		];

		$delTags = [
			'has <td>label / de</td>' => [ 'tag' => 'td', 'content' => 'label / de' ],
			'has <del>foo</del>' => [ 'tag' => 'del', 'content' => 'foo' ],
			'has <td>aliases / nl / 0</td>' => [ 'tag' => 'td', 'content' => 'aliases / nl / 0' ],
			'has <del>bar</del>' => [ 'tag' => 'del', 'content' => 'bar' ],
			'has <td>description / en</td>' => [ 'tag' => 'td', 'content' => 'description / en' ],
			'has <del>ohi there</del>' => [ 'tag' => 'del', 'content' => 'ohi there' ],
		];

		$changeTags = [
			'has <td>label / en</td>' => [ 'tag' => 'td', 'content' => 'label / en' ],
			'has <ins>O_o</ins>' => [ 'tag' => 'ins', 'content' => 'O_o' ],
			'has <td>aliases / nl / 0</td>' => [ 'tag' => 'td', 'content' => 'aliases / nl / 0' ],
			'has <ins>daaaah</ins>' => [ 'tag' => 'ins', 'content' => 'daaaah' ],
			'has <td>aliases / nl / 1</td>' => [ 'tag' => 'td', 'content' => 'aliases / nl / 1' ],
			'has <del>foo</del>' => [ 'tag' => 'del', 'content' => 'foo' ],
			'has <td>aliases / nl / 2</td>' => [ 'tag' => 'td', 'content' => 'aliases / nl / 2' ],
			'has <del>bar</del>' => [ 'tag' => 'del', 'content' => 'bar' ],
			'has <td>description / en</td>' => [ 'tag' => 'td', 'content' => 'description / en' ],
			'has <del>ohi there</del>' => [ 'tag' => 'del', 'content' => 'ohi there' ],
		];

		$fromRedirTags = [
			'has <td>label / de</td>' => [ 'tag' => 'td', 'content' => 'label / de' ],
			'has <ins>foo</ins>' => [ 'tag' => 'ins', 'content' => 'foo' ],

			'has <td>redirect</td>' => [ 'tag' => 'td', 'content' => 'redirect' ],
			'has <del>Q21</del>' => [ 'tag' => 'del', 'content' => 'Q21' ],
		];

		$toRedirTags = [
			'has <td>label / de</td>' => [ 'tag' => 'td', 'content' => 'label / de' ],
			'has <del>foo</del>' => [ 'tag' => 'del', 'content' => 'foo' ],

			'has <td>redirect</td>' => [ 'tag' => 'td', 'content' => 'redirect' ],
			'has <ins>Q21</ins>' => [ 'tag' => 'ins', 'content' => 'Q21' ],
		];

		$changeRedirTags = [
			'has <td>redirect</td>' => [ 'tag' => 'td', 'content' => 'redirect' ],
			'has <del>Q21</del>' => [ 'tag' => 'del', 'content' => 'Q21' ],
			'has <ins>Q22</del>' => [ 'tag' => 'ins', 'content' => 'Q22' ],
		];

		$empty = ItemContent::newFromItem( $emptyItem );
		$itemContent = ItemContent::newFromItem( $item );
		$itemContent2 = ItemContent::newFromItem( $item2 );

		$redirectContent = ItemContent::newFromRedirect(
			$redirect,
			$this->getMock( Title::class )
		);
		$redirectContent2 = ItemContent::newFromRedirect(
			$redirect2,
			$this->getMock( Title::class )
		);

		return [
			'empty' => [ $empty, $empty, [ 'empty' => '/^$/', ] ],
			'same' => [ $itemContent, $itemContent, [ 'empty' => '/^$/', ] ],
			'from emtpy' => [ $empty, $itemContent, $insTags ],
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
		$context = new DerivativeContext( RequestContext::getMain() );
		$context->setLanguage( Language::factory( 'en' ) );

		$diffView = new EntityContentDiffView( $context );

		$html = $diffView->generateContentDiffBody( $itemContent, $itemContent2 );

		$this->assertInternalType( 'string', $html );

		foreach ( $matchers as $name => $matcher ) {
			if ( is_string( $matcher ) ) {
				$this->assertRegExp( $matcher, $html );
			} else {
				$this->assertTag( $matcher, $html, $name );
			}
		}
	}

}
