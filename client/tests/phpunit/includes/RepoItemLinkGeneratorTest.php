<?php

namespace Wikibase\Test;

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
 * @author Marius Hoch < hoo@online.de >
 */
class RepoItemLinkGeneratorTest extends \PHPUnit_Framework_TestCase {

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
		return new BasicEntityIdParser();
	}

	public function getLinksProvider() {
		$prefixedId = 'q9000';

		$href = preg_quote( 'http://www.example.com/wiki/Q9000#sitelinks-wikipedia', '/' );
		$editLinks = preg_quote( wfMessage( 'wikibase-editlinks' )->text(), '/' );
		$addlinks = preg_quote( wfMessage( 'wikibase-linkitem-addlinks' )->text(), '/' );

		$editLinksLinkRegex = '/<span.*wb-langlinks-edit.*<a.*href="'
				. $href . '".*>' . $editLinks . '<\/a><\/span>/';

		$addLinksLinkRegex = '/<span.*wb-langlinks-add.*<a.*href="#".*>'
				. $addlinks . '<\/a><\/span>/';

		$title = Title::newFromText( 'Tokyo', NS_MAIN );
		$nonExistingTitle = Title::newFromText( 'pfuwdodx2', NS_MAIN );

		$title->resetArticleID( 9638 ); // Needed so that Title::exists() -> true

		$data = array();

		$data['has edit link'] = array(
			'expected' => $editLinksLinkRegex,
			'title' => $title,
			'action' => 'view',
			'isAnon' => false,
			'noExternalLangLinks' => null,
			'prefixedId' => $prefixedId,
			'hasLangLinks' => true
		);

		$data['add link: not linked to an entity'] = array(
			'expected' => $addLinksLinkRegex,
			'title' => $title,
			'action' => 'view',
			'isAnon' => false,
			'noExternalLangLinks' => null,
			'prefixedId' => null,
			'hasLangLinks' => true
		);

		$data['add link: no language links'] = array(
			'expected' => $addLinksLinkRegex,
			'title' => $title,
			'action' => 'view',
			'isAnon' => false,
			'noExternalLangLinks' => null,
			'prefixedId' => $prefixedId,
			'hasLangLinks' => false
		);

		$data['no add links link if not logged in'] = array(
			'expected' => null,
			'title' => $title,
			'action' => 'view',
			'isAnon' => true,
			'noExternalLangLinks' => null,
			'prefixedId' => $prefixedId,
			'hasLangLinks' => false
		);

		$data['no edit link on action=history'] = array(
			'expected' => null,
			'title' => $title,
			'action' => 'history',
			'isAnon' => false,
			'noExternalLangLinks' => null,
			'prefixedId' => $prefixedId,
			'hasLangLinks' => true
		);

		$data['no edit link if noExternalLangLinks'] = array(
			'expected' => null,
			'title' => $title,
			'action' => 'view',
			'isAnon' => false,
			'noExternalLangLinks' => array( '*' ),
			'prefixedId' => $prefixedId,
			'hasLangLinks' => true
		);

		$data['title does not exist'] = array(
			'expected' => null,
			'title' => $nonExistingTitle,
			'action' => 'view',
			'isAnon' => false,
			'noExternalLangLinks' => null,
			'prefixedId' => null,
			'hasLangLinks' => null
		);

		return $data;

	}

	/**
	 * @dataProvider getLinksProvider
	 */
	public function testGetLinks(
			$expected,
			$title,
			$action,
			$isAnon,
			$noExternalLangLinks,
			$prefixedId,
			$hasLangLinks
		) {
		$repoLinker = $this->getRepoLinker();
		$namespaceChecker = $this->getNamespaceChecker();
		$entityIdParser = $this->getEntityIdParser();

		$repoItemLinkGenerator = new RepoItemLinkGenerator(
			$namespaceChecker,
			$repoLinker,
			$entityIdParser,
			'wikipedia',
			$hasLangLinks
		);

		$link = $repoItemLinkGenerator->getLink(
			$title, $action, $isAnon, $noExternalLangLinks, $prefixedId
		);

		if ( $expected === null ) {
			$this->assertNull( $expected );
		} else {
			$this->assertRegexp( $expected, $link );
		}
	}

}
