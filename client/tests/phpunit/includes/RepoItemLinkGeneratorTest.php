<?php

namespace Wikibase\Test;

use \Wikibase\RepoItemLinkGenerator;
use \Wikibase\RepoLinker;
use \Wikibase\NamespaceChecker;
use \Wikibase\Lib\EntityIdParser;
use \ValueParsers\ParserOptions;

/**
 * Tests for the Wikibase\RepoItemLinkGenerator class.
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
 * @since 0.4
 *
 * @ingroup WikibaseClient
 * @ingroup Test
 *
 * @group WikibaseClient
 * @group RepoItemLinkGenerator
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class RepoItemLinkGeneratorTest extends \MediaWikiTestCase {

	/**
	 * @var RepoLinker
	 */
	protected $repoLinker;

	/**
	 * @var NamespaceChecker
	 */
	protected $namespaceChecker;

	protected function getRepoLinker() {
		$baseUrl = 'http://www.example.com';
		$articlePath = '/wiki/$1';
		$scriptPath = '';
		$repoNamespaces = array(
			'wikibase-item' => '',
			'wikibase-property' => 'Property:'
		);

		return new RepoLinker( $baseUrl, $articlePath, $scriptPath, $repoNamespaces );
	}

	protected function getNamespaceChecker() {
		return new NamespaceChecker( array(), array() );
	}

	protected function getEntityIdParser() {
		$entityPrefixes = array(
			'q' => \Wikibase\Item::ENTITY_TYPE,
			'p' => \Wikibase\Property::ENTITY_TYPE,
		);

		$options = new \ValueParsers\ParserOptions( array(
			EntityIdParser::OPT_PREFIX_MAP => $entityPrefixes
		) );

		return new EntityIdParser( $options );
	}

	public function getLinksProvider() {
		$repoLinker = $this->getRepoLinker();

		$prefixedId = 'q9000';
		$href = $repoLinker->repoArticleUrl( strtoupper( $prefixedId ) ) . '#sitelinks';

		$addLinksLink = array(
			'text' => '',
			'id' => 'wbc-linkToItem',
			'class' => 'wbc-editpage wbc-nolanglinks'
		);

		$editLinksLink = array(
			'href' => $href,
			'text' => 'Edit links',
			'title' => 'Edit interlanguage links',
			'class' => 'wbc-editpage'
		);

		$title = \Title::newFromText( 'Tokyo', NS_MAIN );
		$nonExistingTitle = \Title::newFromText( 'pfuwdodx2', NS_MAIN );

		$title->resetArticleID( 9638 );

		$data = array();

		$data[] = array( $editLinksLink, $title, 'view', false, null, $prefixedId );
		$data[] = array( $addLinksLink, $title, 'view', false, null, null );
		$data[] = array( null, $nonExistingTitle, 'view', false, null, null );
		$data[] = array( null, $title, 'view', true, null, null );
		$data[] = array( null, $title, 'history', false, null, $prefixedId );
		$data[] = array( $editLinksLink, $title, 'view', true, null, $prefixedId );
		$data[] = array( null, $title, 'view', false, array( '*' ), $prefixedId );

		return $data;

	}

	/**
	 * @dataProvider getLinksProvider
	 */
	public function testGetLinks( $expected, $title, $action, $isAnon, $noExternalLangLinks, $prefixedId ) {
		$repoLinker = $this->getRepoLinker();
		$namespaceChecker = $this->getNamespaceChecker();
		$entityIdParser = $this->getEntityIdParser();

		$repoItemLinkGenerator = new RepoItemLinkGenerator( $namespaceChecker, $repoLinker, $entityIdParser, true, 'wikipedia' );

		$link = $repoItemLinkGenerator->getLink(
			$title, $action, $isAnon, $noExternalLangLinks, $prefixedId
		);

		$this->assertEquals( $expected, $link );
	}

}
