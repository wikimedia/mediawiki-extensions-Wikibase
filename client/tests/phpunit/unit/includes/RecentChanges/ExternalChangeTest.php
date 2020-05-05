<?php

namespace Wikibase\Client\Tests\Unit\RecentChanges;

use Wikibase\Client\RecentChanges\ExternalChange;
use Wikibase\Client\RecentChanges\RevisionData;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers \Wikibase\Client\RecentChanges\ExternalChange
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class ExternalChangeTest extends \PHPUnit\Framework\TestCase {

	public function testValueObject() {
		$entityId = new ItemId( 'Q1' );
		$revisionData = new RevisionData( '', '', '', null, '<SITE>', 0, [] );
		$instance = new ExternalChange( $entityId, $revisionData, '<TYPE>' );
		$this->assertSame( $entityId, $instance->getEntityId() );
		$this->assertSame( $revisionData, $instance->getRev() );
		$this->assertSame( '<TYPE>', $instance->getChangeType() );
		$this->assertSame( '<SITE>', $instance->getSiteId() );
	}

}
