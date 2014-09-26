<?php

namespace Wikibase\Test;

use Language;
use Title;
use Wikibase\Client\RepoItemLinkGenerator;
use Wikibase\Client\RepoLinker;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\NamespaceChecker;

/**
 * @covers Wikibase\Client\RepoItemLinkGenerator
 *
 * @group WikibaseClient
 * @group RepoItemLinkGenerator
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class RepoItemLinkGeneratorTest extends \MediaWikiTestCase {

	/**
	 * @var NamespaceChecker
	 */
	protected $namespaceChecker;

	protected function setUp() {
		parent::setUp();
		$this->setMwGlobals( array(
			'wgLang' => Language::factory( 'en' )
		) );
	}

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
		return new BasicEntityIdParser();
	}

	public function getLinksProvider() {
		$prefixedId = 'q9000';
		$href = 'http://www.example.com/wiki/Q9000#sitelinks-wikipedia';

		$addLinksLink = array(
			'action' => 'add',
			'text' => '',
			'id' => 'wbc-linkToItem',
			'class' => 'wbc-editpage wbc-nolanglinks'
		);

		$editLinksLink = array(
			'action' => 'edit',
			'href' => $href,
			'text' => 'Edit links',
			'title' => 'Edit interlanguage links',
			'class' => 'wbc-editpage'
		);

		$title = Title::newFromText( 'Tokyo', NS_MAIN );
		$nonExistingTitle = Title::newFromText( 'pfuwdodx2', NS_MAIN );

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

		$repoItemLinkGenerator = new RepoItemLinkGenerator(
			$namespaceChecker,
			$repoLinker,
			$entityIdParser,
			'wikipedia'
		);

		$link = $repoItemLinkGenerator->getLink(
			$title, $action, $isAnon, $noExternalLangLinks, $prefixedId
		);

		$this->assertEquals( $expected, $link );
	}

}
