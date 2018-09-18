<?php

namespace Wikibase\Repo\Tests;

use MediaWikiTestCase;
use PHPUnit\Framework\RiskyTestError;
use PHPUnit\Framework\Test;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityStoreWatcher;

/**
 * @license GPL-2.0-or-later
 */
class TestEntityStoreWatcher implements EntityStoreWatcher {

	/**
	 * @var Test
	 */
	private $test;

	public function __construct( $test ) {
		$this->test = $test;
	}

	public function entityUpdated( EntityRevision $entityRevision ) {
		if ( !( $this->test instanceof MediaWikiTestCase ) ) {
			throw new RiskyTestError(
				'Test is interacting with the wikibase entity store does not extend MediaWikiTestCase. ' .
				'Please fix this to ensure database cleanup (see tablesUsed).'
			);
		}

		if ( !in_array( 'page', $this->test->getTablesUsed() ) ) {
			throw new RiskyTestError(
				'Test performed update on wikibase entity store but does not set tablesUsed accordingly.'
			);
		}
	}

	public function redirectUpdated( EntityRedirect $entityRedirect, $revisionId ) {
	}

	public function entityDeleted( EntityId $entityId ) {
	}

};
