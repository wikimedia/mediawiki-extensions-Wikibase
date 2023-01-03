<?php
declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use MediaWiki\Revision\SlotRecord;
use Wikibase\Client\RepoLinker;
use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\SubEntityTypesMapper;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class RepoLinkerTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseClient.Settings',
			new SettingsArray( [
				'repoUrl' => 'https://repo.test',
				'repoArticlePath' => '/wiki/$1',
				'repoScriptPath' => '/w',
			] ) );
		$this->mockService( 'WikibaseClient.EntitySourceDefinitions',
			new EntitySourceDefinitions(
				[ new DatabaseEntitySource(
					'item',
					'itemdb',
					[ 'item' => [ 'namespaceId' => 0, 'slot' => SlotRecord::MAIN ] ],
					'https://item.test/entity/',
					'',
					'',
					'item'
				) ],
				new SubEntityTypesMapper( [] )
			) );

		/** @var RepoLinker $repoLinker */
		$repoLinker = $this->getService( 'WikibaseClient.RepoLinker' );

		$this->assertInstanceOf( RepoLinker::class, $repoLinker );
		$this->assertSame( 'https://repo.test/wiki/A_page',
			$repoLinker->getPageUrl( 'A page' ) );
		$this->assertSame( 'https://repo.test/w/api.php',
			$repoLinker->getApiUrl() );
		$this->assertSame( 'https://item.test/entity/Q1',
			$repoLinker->getEntityConceptUri( new ItemId( 'Q1' ) ) );
	}

}
