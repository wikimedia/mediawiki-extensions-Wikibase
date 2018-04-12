<?php

namespace Wikibase\Client\Tests\Hooks;

use ConfigFactory;
use ContentHandler;
use FauxRequest;
use IContextSource;
use OutputPage;
use PHPUnit4And6Compat;
use PHPUnit\Framework\TestCase;
use Skin;
use SkinFallbackTemplate;
use SkinTemplate;
use Title;
use Wikibase\Client\Hooks\SkinTemplateOutputPageBeforeExecHandler;
use Wikibase\Client\RepoItemLinkGenerator;
use WikiPage;

/**
 * @covers Wikibase\Client\Hooks\SkinTemplateOutputPageBeforeExecHandler
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class SkinTemplateOutputPageBeforeExecHandlerTest extends TestCase {
	use PHPUnit4And6Compat;

	public function testDoSkinTemplateOutputPageBeforeExec_setEditLink() {
		$expected = 'I am a Link!';
		$handler = $this->newSkinTemplateOutputPageBeforeExecHandler( $expected );

		$actualWbeditlanglinks = null;
		$foo = null;
		$handler->doSkinTemplateOutputPageBeforeExec(
			$this->getSkin(),
			$this->getTemplate( [], $foo, $actualWbeditlanglinks )
		);

		$this->assertSame( $expected, $actualWbeditlanglinks );
	}

	public function testDoSkinTemplateOutputPageBeforeExec_editLinkIsNull() {
		$handler = $this->newSkinTemplateOutputPageBeforeExecHandler();

		$actualWbeditlanglinks = null;
		$foo = null;
		$handler->doSkinTemplateOutputPageBeforeExec(
			$this->getSkin(),
			$this->getTemplate( [], $foo, $actualWbeditlanglinks )
		);

		$this->assertNull( $actualWbeditlanglinks );
	}

	public function testDoSkinTemplateOutputPageBeforeExec_languageUrls() {
		$handler = $this->newSkinTemplateOutputPageBeforeExecHandler();

		$actualLanguageUrls = null;
		$handler->doSkinTemplateOutputPageBeforeExec(
			$this->getSkin(),
			$this->getTemplate( false, $actualLanguageUrls )
		);

		$this->assertSame( [], $actualLanguageUrls );
	}

	public function testDoSkinTemplateOutputPageBeforeExec_noExternalLangLinks() {
		$handler = $this->newSkinTemplateOutputPageBeforeExecHandler();

		$actualLanguageUrls = null;
		$handler->doSkinTemplateOutputPageBeforeExec(
			$this->getSkin( [ '*' ] ),
			$this->getTemplate( [], $actualLanguageUrls )
		);

		$this->assertNull( $actualLanguageUrls );
	}

	private function newSkinTemplateOutputPageBeforeExecHandler( $link = null ) {
		$repoItemLinkGenerator = $this->getMockBuilder( RepoItemLinkGenerator::class )
			->disableOriginalConstructor()
			->getMock();

		$repoItemLinkGenerator->expects( $this->any() )
			->method( 'getLink' )
			->with(
				$this->isInstanceOf( Title::class ),
				$this->isType( 'string' ),
				$this->isType( 'bool' ),
				$this->logicalOr( $this->isType( 'array' ), $this->isNull() ),
				$this->isType( 'string' )
			)
			->will( $this->returnValue( $link ) );

		return new SkinTemplateOutputPageBeforeExecHandler( $repoItemLinkGenerator );
	}

	/**
	 * Changes $actualLanguageUrls and $actualWbeditlanglinks when SkinFallbackTemplate::set is called.
	 *
	 * @param mixed $languageUrls
	 * @param mixed &$actualLanguageUrls
	 * @param mixed &$actualWbeditlanglinks
	 *
	 * @return SkinFallbackTemplate
	 */
	private function getTemplate( $languageUrls, &$actualLanguageUrls, &$actualWbeditlanglinks = null ) {
		$template = $this->getMock( SkinFallbackTemplate::class );

		$template->expects( $this->any() )
			->method( 'get' )
			->with( 'language_urls' )
			->will( $this->returnValue( $languageUrls ) );

		$template->expects( $this->any() )
			->method( 'set' )
			->will( $this->returnCallback( function( $name, $val ) use ( &$actualLanguageUrls, &$actualWbeditlanglinks ) {
				if ( $name === 'language_urls' ) {
					$actualLanguageUrls = $val;
				} elseif ( $name === 'wbeditlanglinks' ) {
					$actualWbeditlanglinks = $val;
				} else {
					TestCase::fail( 'Unexpected option ' .  $name . ' set.' );
				}
			} ) );

		return $template;
	}

	/**
	 * @param array|null $noexternallanglinks
	 *
	 * @return Skin
	 */
	private function getSkin( array $noexternallanglinks = null ) {
		$skin = $this->getMock( SkinTemplate::class );

		$output = new OutputPage( $this->getContext() );
		$output->setProperty( 'noexternallanglinks', $noexternallanglinks );
		$output->setProperty( 'wikibase_item', 'Q2013' );
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

		return $skin;
	}

	/**
	 * @return IContextSource
	 */
	private function getContext() {
		$request = new FauxRequest( [ 'action' => 'view' ] );

		$title = $this->getMock( Title::class );
		$title->expects( $this->any() )
			->method( 'exists' )
			->will( $this->returnValue( true ) );

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

		$context = $this->getMock( IContextSource::class );
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
		$context->expects( $this->any() )
			->method( 'getConfig' )
			->will( $this->returnValue(
				ConfigFactory::getDefaultInstance()->makeConfig( 'main' )
			) );

		return $context;
	}

}
