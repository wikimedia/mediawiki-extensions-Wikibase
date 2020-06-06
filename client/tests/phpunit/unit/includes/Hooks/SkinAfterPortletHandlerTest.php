<?php

namespace Wikibase\Client\Tests\Unit\Hooks;

use ConfigFactory;
use ContentHandler;
use FauxRequest;
use IContextSource;
use Language;
use OutputPage;
use PHPUnit\Framework\TestCase;
use Skin;
use Title;
use Wikibase\Client\Hooks\SkinAfterPortletHandler;
use Wikibase\Client\NamespaceChecker;
use Wikibase\Client\RepoItemLinkGenerator;
use Wikibase\Client\RepoLinker;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\Lib\EntityTypeDefinitions;
use WikiPage;

/**
 * @covers \Wikibase\Client\Hooks\SkinAfterPortletHandler
 *
 * @group WikibaseClient
 * @group WikibaseHooks
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 */
class SkinAfterPortletHandlerTest extends TestCase {

	public function testDoSkinAfterPortlet_editLink() {
		$handler = $this->getHookHandler();

		$languageUrls = [ 'en' ];
		$noExternalLangLinks = null;

		$result = $handler->doSkinAfterPortlet(
			$this->getSkin( $noExternalLangLinks, $languageUrls )
		);

		$this->assertStringContainsString( 'Edit links', $result );
	}

	public function testDoSkinAfterPortlet_addLink() {
		$handler = $this->getHookHandler();

		$languageUrls = [];
		$noExternalLangLinks = null;

		$result = $handler->doSkinAfterPortlet(
			$this->getSkin( $noExternalLangLinks, $languageUrls )
		);

		$this->assertStringContainsString( 'Add links', $result );
	}

	public function testDoSkinAfterPortlet_nonViewAction() {
		$handler = $this->getHookHandler();

		$languageUrls = [ 'en' ];
		$noExternalLangLinks = null;
		$action = 'edit';

		$result = $handler->doSkinAfterPortlet(
			$this->getSkin( $noExternalLangLinks, $languageUrls, $action )
		);

		$this->assertNull( $result );
	}

	public function testDoSkinAfterPortlet_actionLinkSuppressed() {
		$handler = $this->getHookHandler();

		$languageUrls = [ 'en' ];
		$noExternalLangLinks = [ '*' ];

		$result = $handler->doSkinAfterPortlet(
			$this->getSkin( $noExternalLangLinks, $languageUrls )
		);

		$this->assertNull( $result );
	}

	private function getRepoLinker() {
		$baseUrl = 'http://www.example.com';
		$articlePath = '/wiki/$1';
		$scriptPath = '';

		return new RepoLinker(
			new EntitySourceDefinitions( [], new EntityTypeDefinitions( [] ) ),
			$baseUrl,
			$articlePath,
			$scriptPath
		);
	}

	private function getHookHandler() {
		$linkGenerator = new RepoItemLinkGenerator(
			new NamespaceChecker( [] ),
			$this->getRepoLinker(),
			new ItemIdParser(),
			'wikipedia',
			'enwiki'
		);

		return new SkinAfterPortletHandler( $linkGenerator );
	}

	/**
	 * @param string[]|null $noExternalLangLinks
	 * @param string[] $languageUrls
	 * @param string $action
	 * @return Skin
	 */
	private function getSkin( $noExternalLangLinks, $languageUrls, $action = 'view' ) {
		$skin = $this->createMock( Skin::class );

		$output = new OutputPage( $this->getContext( $action ) );
		$output->setProperty( 'wikibase_item', 'Q2013' );
		$output->setProperty( 'noexternallanglinks', $noExternalLangLinks );
		$title = $output->getTitle();

		$skin->expects( $this->any() )
			->method( 'getOutput' )
			->will( $this->returnValue( $output ) );
		$skin->expects( $this->any() )
			->method( 'getContext' )
			->will( $this->returnValue( $output ) );
		$skin->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );
		$skin->expects( $this->any() )
			->method( 'getLanguages' )
			->will( $this->returnValue( $languageUrls ) );

		return $skin;
	}

	/**
	 * @param string $action
	 * @return IContextSource
	 */
	private function getContext( $action ) {
		$request = new FauxRequest( [ 'action' => $action ] );

		$title = $this->createMock( Title::class );
		$title->expects( $this->any() )
			->method( 'exists' )
			->will( $this->returnValue( true ) );
		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( 0 ) );

		$contentHandler = ContentHandler::getForModelID( CONTENT_MODEL_WIKITEXT );

		$wikiPage = $this->getMockBuilder( WikiPage::class )
			->disableOriginalConstructor()
			->getMock();
		$wikiPage->expects( $this->any() )
			->method( 'getActionOverrides' )
			->will( $this->returnValue( [] ) );
		$wikiPage->expects( $this->any() )
			->method( 'getContentHandler' )
			->will( $this->returnValue( $contentHandler ) );
		$wikiPage->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$context = $this->createMock( IContextSource::class );
		$context->expects( $this->any() )
			->method( 'canUseWikiPage' )
			->will( $this->returnValue( true ) );
		$context->expects( $this->any() )
			->method( 'getWikiPage' )
			->will( $this->returnValue( $wikiPage ) );
		$context->expects( $this->any() )
			->method( 'getRequest' )
			->will( $this->returnValue( $request ) );
		$context->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );
		$context->method( 'getLanguage' )
			->willReturn( Language::factory( 'qqx' ) );
		$context->expects( $this->any() )
			->method( 'getConfig' )
			->will( $this->returnValue(
				ConfigFactory::getDefaultInstance()->makeConfig( 'main' )
			) );
		return $context;
	}
}
