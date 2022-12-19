<?php

namespace Wikibase\Client\Tests\Unit;

use MediaWikiTestCaseTrait;
use Title;
use Wikibase\Client\NamespaceChecker;
use Wikibase\Client\RepoItemLinkGenerator;
use Wikibase\Client\RepoLinker;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\Lib\SubEntityTypesMapper;

/**
 * @covers \Wikibase\Client\RepoItemLinkGenerator
 *
 * @group WikibaseClient
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 */
class RepoItemLinkGeneratorTest extends \PHPUnit\Framework\TestCase {
	use MediaWikiTestCaseTrait;

	private function getRepoLinker() {
		$baseUrl = 'http://www.example.com';
		$articlePath = '/wiki/$1';
		$scriptPath = '';

		return new RepoLinker(
			new EntitySourceDefinitions( [], new SubEntityTypesMapper( [] ) ),
			$baseUrl,
			$articlePath,
			$scriptPath
		);
	}

	public function getLinksProvider() {
		$prefixedId = 'q9000';

		$href = preg_quote(
			'http://www.example.com/wiki/Special:EntityPage/Q9000#sitelinks-wikipedia',
			'/'
		);
		$editLinks = preg_quote( wfMessage( 'wikibase-editlinks' )->text(), '/' );
		$addLinks = preg_quote( wfMessage( 'wikibase-linkitem-addlinks' )->text(), '/' );

		$editLinksLinkRegex = '/<span.*wb-langlinks-edit.*<a.*href="'
				. $href . '".*>' . $editLinks . '<\/a><\/span>/';

		// Special:NewItem wont get localized, so it's ok to check against that.
		$addLinksRegexNewItem = '/<span.*wb-langlinks-add.*<a.*href=".*Special:NewItem.*".*>'
				. $addLinks . '<\/a><\/span>/';

		$addLinksRegexItemExists = '/<span.*wb-langlinks-add.*<a.*href="' . $href . '".*>'
				. $addLinks . '<\/a><\/span>/';

		$title = Title::makeTitle( NS_MAIN, 'Tokyo' );
		$nonExistingTitle = Title::makeTitle( NS_MAIN, 'Pfuwdodx2' );

		$title->resetArticleID( 9638 ); // Needed so that Title::exists() -> true

		$data = [];

		$data['has edit link'] = [
			'expected' => $editLinksLinkRegex,
			'title' => $title,
			'action' => 'view',
			'noExternalLangLinks' => null,
			'prefixedId' => $prefixedId,
			'hasLangLinks' => true,
		];

		$data['add link: not linked to an entity'] = [
			'expected' => $addLinksRegexNewItem,
			'title' => $title,
			'action' => 'view',
			'noExternalLangLinks' => null,
			'prefixedId' => null,
			'hasLangLinks' => false,
		];

		$data['add link: no language links'] = [
			'expected' => $addLinksRegexItemExists,
			'title' => $title,
			'action' => 'view',
			'noExternalLangLinks' => null,
			'prefixedId' => $prefixedId,
			'hasLangLinks' => false,
		];

		$data['no edit link on action=history'] = [
			'expected' => null,
			'title' => $title,
			'action' => 'history',
			'noExternalLangLinks' => null,
			'prefixedId' => $prefixedId,
			'hasLangLinks' => true,
		];

		$data['no edit link if noExternalLangLinks'] = [
			'expected' => null,
			'title' => $title,
			'action' => 'view',
			'noExternalLangLinks' => [ '*' ],
			'prefixedId' => $prefixedId,
			'hasLangLinks' => true,
		];

		$data['edit link when had links and suppressing one link'] = [
			'expected' => $editLinksLinkRegex,
			'title' => $title,
			'action' => 'view',
			'noExternalLangLinks' => [ 'fr' ],
			'prefixedId' => $prefixedId,
			'hasLangLinks' => true,
		];

		$data['title does not exist'] = [
			'expected' => null,
			'title' => $nonExistingTitle,
			'action' => 'view',
			'noExternalLangLinks' => null,
			'prefixedId' => null,
			'hasLangLinks' => null,
		];

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
		$repoItemLinkGenerator = new RepoItemLinkGenerator(
			new NamespaceChecker( [] ),
			$this->getRepoLinker(),
			new ItemIdParser(),
			'wikipedia',
			'enwiki'
		);

		$link = $repoItemLinkGenerator->getLink(
			$title, $action, $hasLangLinks, $noExternalLangLinks, $prefixedId
		);

		if ( $expected === null ) {
			$this->assertNull( $link );
		} else {
			$this->assertMatchesRegularExpression( $expected, $link );
		}
	}

}
