<?php

namespace Wikibase\Repo\Tests;

use MediaWikiTestCase;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityStoreWatcher;

/**
 * @license GPL-2.0-or-later
 */
class TestEntityStoreWatcher implements EntityStoreWatcher {

	/**
	 * @var TestCase
	 */
	private $test;

	public function __construct( /* TestCase or PHPUnit_Framework_TestCase */ $test ) {
		$this->test = $test;
	}

	public function entityUpdated( EntityRevision $entityRevision ) {
		if ( !( $this->test instanceof MediaWikiTestCase ) ) {
			throw new RuntimeException(
				$this->test->getName( true ) .
				' interacting with the entity store does not extend MediaWikiTestCase.' .
				' Please fix this to ensure database cleanup (see tablesUsed).'
			);
		}

		$this->test->addTablesUsed( [ 'page' ] );
	}

	public function redirectUpdated( EntityRedirect $entityRedirect, $revisionId ) {
	}

	public function entityDeleted( EntityId $entityId ) {
	}

};
