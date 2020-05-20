<?php
declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Hooks;

use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Linker\LinkTarget;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\EntityUrlLookup;
use Wikibase\Lib\Store\LinkTargetEntityIdLookup;
use Wikibase\Repo\Hooks\HtmlPageLinkRendererBeginHookHandler;
use Wikibase\Repo\Hooks\HtmlPageLinkRendererEndHookHandler;

/**
 * @covers \Wikibase\Repo\Hooks\HtmlPageLinkRendererEndHookHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class HtmlPageLinkRendererEndHookHandlerTest extends TestCase {

	private $linkTargetEntityIdLookup;
	private $entityUrlLookup;

	protected function setUp(): void {
		parent::setUp();

		$this->linkTargetEntityIdLookup = $this->createMock( LinkTargetEntityIdLookup::class );
		$this->entityUrlLookup = $this->createMock( EntityUrlLookup::class );
	}

	public function testGivenNoFormattedEntityLinkAttribute_doesNotModifyHref() {
		$linkTarget = $this->createMock( LinkTarget::class );
		$expectedHref = 'will-not-be-modified';
		$attrs = [ 'href' => $expectedHref ];

		$this->linkTargetEntityIdLookup->expects( $this->never() )->method( $this->anything() );
		$this->entityUrlLookup->expects( $this->never() )->method( $this->anything() );

		$this->newHookHandler()->doHtmlPageLinkRendererEnd(
			...$this->stubHookArgs( $linkTarget, $attrs )
		);

		$this->assertSame( $expectedHref, $attrs['href'] );
	}

	public function testGivenCannotGetEntityId_doesNotModifyHref() {
		$linkTarget = $this->createMock( LinkTarget::class );
		$expectedHref = 'will-not-be-modified';
		$attrs = $this->makeEntityLinkAttrsWithHref( $expectedHref );

		$this->linkTargetEntityIdLookup->expects( $this->once() )
			->method( 'getEntityId' )
			->with( $linkTarget )
			->willReturn( null );
		$this->entityUrlLookup->expects( $this->never() )->method( $this->anything() );

		$this->newHookHandler()->doHtmlPageLinkRendererEnd(
			...$this->stubHookArgs( $linkTarget, $attrs )
		);

		$this->assertSame( $expectedHref, $attrs['href'] );
	}

	public function testGivenAnEntityLink_usesUrlLookupForHref() {
		$linkTarget = $this->createMock( LinkTarget::class );
		$expectedHref = 'http://some-wiki/wiki/P123';

		$propertyId = new PropertyId( 'P123' );
		$this->linkTargetEntityIdLookup->expects( $this->once() )
			->method( 'getEntityId' )
			->with( $linkTarget )
			->willReturn( $propertyId );
		$this->entityUrlLookup->expects( $this->once() )
			->method( 'getLinkUrl' )
			->with( $propertyId )
			->willReturn( $expectedHref );

		$attrs = $this->makeEntityLinkAttrsWithHref( 'will-be-modified' );
		$this->newHookHandler()->doHtmlPageLinkRendererEnd(
			...$this->stubHookArgs( $linkTarget, $attrs )
		);

		$this->assertEquals( $expectedHref, $attrs['href'] );
	}

	private function newHookHandler(): HtmlPageLinkRendererEndHookHandler {
		return new HtmlPageLinkRendererEndHookHandler(
			$this->linkTargetEntityIdLookup,
			$this->entityUrlLookup
		);
	}

	private function makeEntityLinkAttrsWithHref( string $href ): array {
		return [
			HtmlPageLinkRendererBeginHookHandler::FORMATTED_ENTITY_LINK_ATTR => true,
			'href' => $href,
		];
	}

	/**
	 * @return array of arguments the hook expects, all stubbed except link target and $attrs
	 */
	private function stubHookArgs( LinkTarget $linkTarget, array &$attrs ): array {
		return [
			$this->createMock( LinkRenderer::class ),
			$linkTarget,
			true,
			'',
			&$attrs,
			null
		];
	}

}
