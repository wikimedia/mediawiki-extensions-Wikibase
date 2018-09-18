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
			$this->raise(
				'Test is interacting with the wikibase entity store does not extend MediaWikiTestCase. ' .
				'Please fix this to ensure database cleanup (see tablesUsed).'
			);
			return;
		}

		if ( !in_array( 'page', $this->test->getTablesUsed() ) ) {
			$this->raise(
				'Test performed update on wikibase entity store but does not set tablesUsed accordingly.'
			);
		}
	}

	public function redirectUpdated( EntityRedirect $entityRedirect, $revisionId ) {
	}

	public function entityDeleted( EntityId $entityId ) {
	}

	private function raise( $message ) {
		if ( $this->isModernPhpunit() ) {
			throw new RiskyTestError( $message );
		}

		// throwing phpunit 4 PHPUnit_Framework_RiskyTestError from a listener results in test stop
		$this->test->markTestIncomplete( $message );
	}

	private function isModernPhpunit() {
		return class_exists( RiskyTestError::class );
	}

};
