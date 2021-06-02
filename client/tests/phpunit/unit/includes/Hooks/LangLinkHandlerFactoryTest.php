<?php

namespace Wikibase\Client\Tests\Integration\Hooks;

use MediaWiki\HookContainer\HookContainer;
use Psr\Log\LoggerInterface;
use SiteStore;
use Wikibase\Client\Hooks\LangLinkHandler;
use Wikibase\Client\Hooks\LangLinkHandlerFactory;
use Wikibase\Client\Hooks\LanguageLinkBadgeDisplay;
use Wikibase\Client\NamespaceChecker;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * @covers \Wikibase\Client\Hooks\LangLinkHandlerFactory
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 */
class LangLinkHandlerFactoryTest extends \MediaWikiUnitTestCase {

	public function testGetLangLinkHandler() {
		$factory = new LangLinkHandlerFactory(
			$this->createMock( LanguageLinkBadgeDisplay::class ),
			$this->createMock( NamespaceChecker::class ),
			$this->createMock( SiteLinkLookup::class ),
			$this->createMock( EntityLookup::class ),
			$this->createMock( SiteStore::class ),
			$this->createMock( HookContainer::class ),
			$this->createMock( LoggerInterface::class ),
			'srwiki',
			[ 'wikipedia' ]
		);

		$langLinkHandler = $factory->getLangLinkHandler( $this->createMock( UsageAccumulator::class ) );

		$this->assertInstanceOf(
			LangLinkHandler::class,
			$langLinkHandler
		);
	}

}
