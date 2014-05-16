<?php

namespace Wikibase\Test;

use DerivativeContext;
use Language;
use RequestContext;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityContentDiffView;
use Wikibase\Item;
use Wikibase\ItemContent;

/**
 * @covers Wikibase\EntityContentDiffView
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
		$empty = Item::newEmpty();
		$empty->setId( new ItemId( 'Q1' ) );

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

		return array(
			'empty' => array( $empty, $empty, array( 'empty' => '/^$/', ) ),
			'same' => array( $item, $item, array( 'empty' => '/^$/', ) ),
			'from emtpy' => array( $empty, $item, $insTags ),
			'to empty' => array( $item, $empty, $delTags ),
			'changed' => array( $item, $item2, $changeTags ),
		);
	}

	/**
	 * @dataProvider itemProvider
	 */
	public function testGenerateContentDiffBody( Item $item0, Item $item1, $matchers ) {
		$context = new DerivativeContext( RequestContext::getMain() );
		$context->setLanguage( Language::factory( 'en' ) );

		$diffView = new EntityContentDiffView( $context );

		$html = $diffView->generateContentDiffBody(
			ItemContent::newFromItem( $item0 ),
			ItemContent::newFromItem( $item1 )
		);

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
