<?php

namespace Wikibase\Test;
use Wikibase\EntityContent;
use Wikibase\Entity;
use Wikibase\EntityView;
use Wikibase\Utils;

/**
 * Test Wikibase\EntityView.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseRepoTest
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseEntityView
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 */
class EntityViewTest extends \MediaWikiTestCase {

	/**
	 * @dataProvider provideFindSections
	 */
	public function testFindSections( $html, $expected ) {
	    $mock = $this->getMockForAbstractClass('\Wikibase\EntityView');
	    $this->assertEquals( $expected, $mock->findSections( $html ) );
	}

	public static function provideFindSections() {
		return array(
			array( '', array() ),
			array( '<h2>foo</h2>',
				array( array( 'args' => array(), 'content' => 'foo' ) )
			),
			array( '<h2>foo</h2><h2>bar</h2>',
				array(
					array( 'args' => array(), 'content' => 'foo' ),
					array( 'args' => array(), 'content' => 'bar' )
				)
			),
			array( '<div><h2>foo</h2><br><h2>bar</h2></div>',
				array(
					array( 'args' => array(), 'content' => 'foo' ),
					array( 'args' => array(), 'content' => 'bar' )
				)
			),
			array( '<h2 id="test">foo</h2>',
				array( array( 'args' => array( 'id' => 'test' ), 'content' => 'foo' ) )
			),
			array( '<h2 id="test1">foo</h2><h2 id="test2">bar</h2>',
				array(
					array( 'args' => array( 'id' => 'test1' ), 'content' => 'foo' ),
					array( 'args' => array( 'id' => 'test2' ), 'content' => 'bar' )
				)
			),
			array( '<div><h2 id="test1">foo</h2><br><h2 id="test2">bar</h2></div>',
				array(
					array( 'args' => array( 'id' => 'test1' ), 'content' => 'foo' ),
					array( 'args' => array( 'id' => 'test2' ), 'content' => 'bar' )
				)
			),
		);
	}

	/**
	 * @dataProvider provideBuildHtmlForTOC
	 */
	public function testBuildHtmlForTOC( $sections, $expected ) {
		$mock = $this->getMockForAbstractClass('\Wikibase\EntityView');
		$this->assertRegExp( $expected, $mock->buildHtmlForTOC( $sections ) );
	}

	public static function provideBuildHtmlForTOC() {
		return array(
			array(
				array(),
				'#<ul class="large"><!-- entries --></ul>#'
			),
			array(
				array( array( 'args' => array(), 'content' => 'foo' ) ),
				'!<a href="#".*?>.*?foo.*?</a></li>!'
			),
			array(
				array( array( 'args' => array( 'id' => 'test' ), 'content' => 'foo' ) ),
				'!<a href="#test".*?>.*?foo.*?</a>!'
			),
			array(
				array(
					array( 'args' => array( 'id' => 'test1' ), 'content' => 'foo' ),
					array( 'args' => array( 'id' => 'test2' ), 'content' => 'bar' )
				),
				'!<a href="#test1".*?>.*?foo.*?</a>.*?<a href="#test2".*?>.*?bar.*?</a>!'
			),
			array(
				array(
					array( 'args' => array( 'id' => 'test1' ), 'content' => 'foo' ),
					array( 'args' => array( 'id' => 'test2', 'class' => 'neglected' ), 'content' => 'bar', 'humpty' => 'dumpty' )
				),
				'!<a href="#test1".*?>.*?foo.*?</a>.*?<a href="#test2".*?>.*?bar.*?</a>!'
			),
		);
	}

	/**
	 * @dataProvider provideInsertTOC
	 */
	public function testInsertTOC( $body, $toc, $expected ) {
		$mock = $this->getMockForAbstractClass('\Wikibase\EntityView');
		$this->assertRegExp( $expected, $mock->insertTOC( $body, $toc ) );
	}

	public static function provideInsertTOC() {
		return array(
			array(
				'somestuff<h2>title</h2>morestuff',
				'<table />',
				'!somestuff<table /><h2>title</h2>morestuff!'
			),
			array(
				"somestuff\n<h2>\ntitle\n</h2>\nmorestuff",
				"<table>\ntable\n</table>",
				'!somestuff\s*<table>\s*table\s*</table>\s*<h2>\s*title\s*</h2>\s*morestuff!s'
			),
		);
	}
}
