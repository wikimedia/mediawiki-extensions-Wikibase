<?php

namespace Wikibase\Test;

use DerivativeContext;
use Language;
use RequestContext;
use Title;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\ItemContent;
use Wikibase\Lib\Store\EntityRedirect;
use Wikibase\Repo\Diff\EntityContentDiffView;

/**
 * @covers Wikibase\Repo\Diff\EntityContentDiffView
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityContentDiffViewTest extends \MediaWikiTestCase {

	//@todo: make this a baseclass to use with all types of entities.

	public function testConstructor() {
		new EntityContentDiffView( RequestContext::getMain() );
		$this->assertTrue( true );
	}

	public function itemProvider() {
		$emptyItem = Item::newEmpty();
		$emptyItem->setId( new ItemId( 'Q1' ) );

		$item = Item::newEmpty();
		$item->setId( new ItemId( 'Q11' ) );
		$item->setDescription( 'en', 'ohi there' );
		$item->setLabel( 'de', 'o_O' );
		$item->addAliases( 'nl', array( 'foo', 'bar' ) );

		$item2 = $item->copy();
		$item->setId( new ItemId( 'Q12' ) );
		$item2->setAliases( 'nl', array( 'daaaah' ) );
		$item2->setLabel( 'en', 'O_o' );
		$item2->removeDescription( 'en' );

		$redirect = new EntityRedirect( new ItemId( 'Q11' ), new ItemId( 'Q21' ) );
		$redirect2 = new EntityRedirect( new ItemId( 'Q11' ), new ItemId( 'Q22' ) );

		$insTags = array(
			'has <td>label / de</td>' => array( 'tag' => 'td', 'content' => 'label / de' ),
			'has <ins>foo</ins>' => array( 'tag' => 'ins', 'content' => 'foo' ),
			'has <td>aliases / nl / 0</td>' => array( 'tag' => 'td', 'content' => 'aliases / nl / 0' ),
			'has <ins>bar</ins>' => array( 'tag' => 'ins', 'content' => 'bar' ),
			'has <td>description / en</td>' => array( 'tag' => 'td', 'content' => 'description / en' ),
			'has <ins>ohi there</ins>' => array( 'tag' => 'ins', 'content' => 'ohi there' ),
		);

		$delTags = array(
			'has <td>label / de</td>' => array( 'tag' => 'td', 'content' => 'label / de' ),
			'has <del>foo</del>' => array( 'tag' => 'del', 'content' => 'foo' ),
			'has <td>aliases / nl / 0</td>' => array( 'tag' => 'td', 'content' => 'aliases / nl / 0' ),
			'has <del>bar</del>' => array( 'tag' => 'del', 'content' => 'bar' ),
			'has <td>description / en</td>' => array( 'tag' => 'td', 'content' => 'description / en' ),
			'has <del>ohi there</del>' => array( 'tag' => 'del', 'content' => 'ohi there' ),
		);

		$changeTags = array(
			'has <td>label / en</td>' => array( 'tag' => 'td', 'content' => 'label / en' ),
			'has <ins>O_o</ins>' => array( 'tag' => 'ins', 'content' => 'O_o' ),
			'has <td>aliases / nl / 0</td>' => array( 'tag' => 'td', 'content' => 'aliases / nl / 0' ),
			'has <ins>daaaah</ins>' => array( 'tag' => 'ins', 'content' => 'daaaah' ),
			'has <td>aliases / nl / 1</td>' => array( 'tag' => 'td', 'content' => 'aliases / nl / 1' ),
			'has <del>foo</del>' => array( 'tag' => 'del', 'content' => 'foo' ),
			'has <td>aliases / nl / 2</td>' => array( 'tag' => 'td', 'content' => 'aliases / nl / 2' ),
			'has <del>bar</del>' => array( 'tag' => 'del', 'content' => 'bar' ),
			'has <td>description / en</td>' => array( 'tag' => 'td', 'content' => 'description / en' ),
			'has <del>ohi there</del>' => array( 'tag' => 'del', 'content' => 'ohi there' ),
		);

		$fromRedirTags = array(
			'has <td>label / de</td>' => array( 'tag' => 'td', 'content' => 'label / de' ),
			'has <ins>foo</ins>' => array( 'tag' => 'ins', 'content' => 'foo' ),

			'has <td>redirect</td>' => array( 'tag' => 'td', 'content' => 'redirect' ),
			'has <del>Q21</del>' => array( 'tag' => 'del', 'content' => 'Q21' ),
		);

		$toRedirTags = array(
			'has <td>label / de</td>' => array( 'tag' => 'td', 'content' => 'label / de' ),
			'has <del>foo</del>' => array( 'tag' => 'del', 'content' => 'foo' ),

			'has <td>redirect</td>' => array( 'tag' => 'td', 'content' => 'redirect' ),
			'has <ins>Q21</ins>' => array( 'tag' => 'ins', 'content' => 'Q21' ),
		);

		$changeRedirTags = array(
			'has <td>redirect</td>' => array( 'tag' => 'td', 'content' => 'redirect' ),
			'has <del>Q21</del>' => array( 'tag' => 'del', 'content' => 'Q21' ),
			'has <ins>Q22</del>' => array( 'tag' => 'ins', 'content' => 'Q22' ),
		);

		$empty = ItemContent::newFromItem( $emptyItem );
		$itemContent = ItemContent::newFromItem( $item );
		$itemContent2 = ItemContent::newFromItem( $item2 );

		$redirectContent = ItemContent::newFromRedirect(
			$redirect,
			Title::newFromText( $redirect->getEntityId()->getSerialization() )
		);
		$redirectContent2 = ItemContent::newFromRedirect(
			$redirect2,
			Title::newFromText( $redirect2->getEntityId()->getSerialization() )
		);

		return array(
			'empty' => array( $empty, $empty, array( 'empty' => '/^$/', ) ),
			'same' => array( $itemContent, $itemContent, array( 'empty' => '/^$/', ) ),
			'from emtpy' => array( $empty, $itemContent, $insTags ),
			'to empty' => array( $itemContent, $empty, $delTags ),
			'changed' => array( $itemContent, $itemContent2, $changeTags ),
			'to redirect' => array( $itemContent, $redirectContent, $toRedirTags ),
			'from redirect' => array( $redirectContent, $itemContent, $fromRedirTags ),
			'redirect changed' => array( $redirectContent, $redirectContent2, $changeRedirTags ),
		);
	}

	/**
	 * @dataProvider itemProvider
	 */
	public function testGenerateContentDiffBody( ItemContent $itemContent, ItemContent $itemContent2, $matchers ) {
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
