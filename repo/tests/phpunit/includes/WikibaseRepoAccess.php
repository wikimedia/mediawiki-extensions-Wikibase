<?php

namespace Wikibase\Repo\Tests;

use Wikibase\Repo\WikibaseRepo;
use Wikibase\SqlStore;
use Wikimedia\TestingAccessWrapper;

/**
 * @license GPL-2.0-or-later
 */
trait WikibaseRepoAccess {

	/**
	 * @var WikibaseRepo
	 */
	private $wikibaseRepo;

	public function getWikibaseRepo() {
		if ( !$this->wikibaseRepo ) {
			$this->wikibaseRepo = WikibaseRepo::getDefaultInstance();
		}

		return $this->wikibaseRepo;
	}

	/**
	 * @after
	 */
	protected function tearDownWikibaseRepo() {
		if ( $this->wikibaseRepo ) {
			/**
			 * @var SqlStore $store
			 */
			$store = TestingAccessWrapper::newFromObject( $this->wikibaseRepo->getStore() );
			$store->inMemoryCache->clear();
		}
	}

}
