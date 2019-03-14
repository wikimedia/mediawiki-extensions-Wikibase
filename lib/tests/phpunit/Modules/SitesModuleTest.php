<?php

namespace Wikibase\Lib\Tests\Modules;

use HashBagOStuff;
use HashSiteStore;
use MediaWikiSite;
use PHPUnit4And6Compat;
use ResourceLoaderContext;
use Wikibase\SitesModule;
use Wikibase\SettingsArray;
use Wikibase\Lib\SitesModuleWorker;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikibase\SitesModule
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class SitesModuleTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	/**
	 * @return ResourceLoaderContext
	 */
	private function getContext() {
		$context = $this->getMockBuilder( ResourceLoaderContext::class )
			->disableOriginalConstructor()
			->getMock();

		$context->expects( $this->any() )
			->method( 'getLanguage' )
			->willReturn( 'en' );

		return $context;
	}

	public function testGetScript() {
		$module = new SitesModule();
		$script = $module->getScript( $this->getContext() );
		$this->assertStringStartsWith( 'mw.config.set({"wbSiteDetails":', $script );
		$this->assertStringEndsWith( '});', $script );
	}

	public function testGetVersionHash() {
		$workerLists = $this->getWorkersForVersionHash();
		$hashesByName = [];

		/** @var SitesModuleWorker[] $workers */
		foreach ( $workerLists as $name => $workers ) {
			$hashes = [];
			foreach ( $workers as $worker ) {
				$module = new SitesModule();
				$moduleAccess = TestingAccessWrapper::newFromObject( $module );
				$moduleAccess->worker = $worker;

				$hashes[] = $module->getVersionHash( $this->getContext() );
			}
			$this->assertSame(
				array_unique( $hashes ),
				[ $hashes[0] ],
				'the same version hash for equivalent settings'
			);

			$hashesByName[ $name ] = $hashes;
		}

		$this->assertSame(
			array_keys( $workerLists ),
			array_keys( $hashesByName ),
			'different settings lead to same hash'
		);
	}

	private function getWorkersForVersionHash() {
		$site = new MediaWikiSite();
		$site->setGlobalId( 'siteid' );
		$site->setGroup( 'allowedgroup' );

		$site2 = new MediaWikiSite();
		$site2->setGlobalId( 'site2id' );
		$site2->setGroup( 'allowedgroup' );

		return [
			'empty workers' => [
				$this->newSitesModuleWorker( [], [] ),
				$this->newSitesModuleWorker( [], [] ),
			],
			'single site' => [
				$this->newSitesModuleWorker( [ $site ], [] ),
				$this->newSitesModuleWorker( [ $site ], [] ),
			],
			'single site with configured group' => [
				$this->newSitesModuleWorker( [ $site ], [ 'allowedgroup' ] ),
				$this->newSitesModuleWorker( [ $site ], [ 'allowedgroup' ] )
			],
		];
	}

	private function newSitesModuleWorker( array $sites, array $groups ) {
		return new SitesModuleWorker(
			new SettingsArray( [
				'siteLinkGroups' => $groups,
				'specialSiteLinkGroups' => []
			] ),
			new HashSiteStore( $sites ),
			new HashBagOStuff()
		);
	}

}
