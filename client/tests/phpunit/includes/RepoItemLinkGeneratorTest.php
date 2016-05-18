<?php

namespace Wikibase\Client\Tests;

use PHPUnit_Framework_TestCase;
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
 * @group Database
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 */
class RepoItemLinkGeneratorTest extends PHPUnit_Framework_TestCase {

	protected function getRepoLinker() {
		$baseUrl = 'http://www.example.com';
		$articlePath = '/wiki/$1';
		$scriptPath = '';
		$repoNamespaces = array(
			'item' => '',
			'property' => 'Property:'
		);

		return new RepoLinker( $baseUrl, $articlePath, $scriptPath, $repoNamespaces );
	}

	protected function getNamespaceChecker() {
		return new NamespaceChecker( array() );
	}

	protected function getEntityIdParser() {
		return new BasicEntityIdParser();
	}

	public function getLinksProvider() {
		$prefixedId = 'q9000';

		$href = preg_quote( 'http://www.example.com/wiki/Q9000#sitelinks-wikipedia', '/' );
		$editLinks = preg_quote( wfMessage( 'wikibase-editlinks' )->text(), '/' );
		$addLinks = preg_quote( wfMessage( 'wikibase-linkitem-addlinks' )->text(), '/' );

		$editLinksLinkRegex = '/<span.*wb-langlinks-edit.*<a.*href="'
				. $href . '".*>' . $editLinks . '<\/a><\/span>/';

		// Special:NewItem wont get localized, so it's ok to check against that.
		$addLinksRegexNewItem = '/<span.*wb-langlinks-add.*<a.*href=".*Special:NewItem.*".*>'
				. $addLinks . '<\/a><\/span>/';

		$addLinksRegexItemExists = '/<span.*wb-langlinks-add.*<a.*href="' . $href . '".*>'
				. $addLinks . '<\/a><\/span>/';

		$title = Title::newFromText( 'Tokyo', NS_MAIN );
		$nonExistingTitle = Title::newFromText( 'pfuwdodx2', NS_MAIN );

		$title->resetArticleID( 9638 ); // Needed so that Title::exists() -> true

		$data = array();

		$data['has edit link'] = array(
			'expected' => $editLinksLinkRegex,
			'title' => $title,
			'action' => 'view',
			'noExternalLangLinks' => null,
			'prefixedId' => $prefixedId,
			'hasLangLinks' => true
		);

		$data['add link: not linked to an entity'] = array(
			'expected' => $addLinksRegexNewItem,
			'title' => $title,
			'action' => 'view',
			'noExternalLangLinks' => null,
			'prefixedId' => null,
			'hasLangLinks' => false
		);

		$data['add link: no language links'] = array(
			'expected' => $addLinksRegexItemExists,
			'title' => $title,
			'action' => 'view',
			'noExternalLangLinks' => null,
			'prefixedId' => $prefixedId,
			'hasLangLinks' => false
		);

		$data['no edit link on action=history'] = array(
			'expected' => null,
			'title' => $title,
			'action' => 'history',
			'noExternalLangLinks' => null,
			'prefixedId' => $prefixedId,
			'hasLangLinks' => true
		);

		$data['no edit link if noExternalLangLinks'] = array(
			'expected' => null,
			'title' => $title,
			'action' => 'view',
			'noExternalLangLinks' => array( '*' ),
			'prefixedId' => $prefixedId,
			'hasLangLinks' => true
		);

		$data['edit link when had links and suppressing one link'] = array(
			'expected' => $editLinksLinkRegex,
			'title' => $title,
			'action' => 'view',
			'noExternalLangLinks' => array( 'fr' ),
			'prefixedId' => $prefixedId,
			'hasLangLinks' => true
		);

		$data['title does not exist'] = array(
			'expected' => null,
			'title' => $nonExistingTitle,
			'action' => 'view',
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
			'enwiki'
		);

		$link = $repoItemLinkGenerator->getLink(
			$title, $action, $hasLangLinks, $noExternalLangLinks, $prefixedId
		);

		if ( $expected === null ) {
			$this->assertNull( $link );
		} else {
			$this->assertRegexp( $expected, $link );
		}
	}

}
