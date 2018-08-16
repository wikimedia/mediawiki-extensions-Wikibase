<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @license GPL-2.0-or-later
 */
class LatestRevisionIdResult {

	//TODO php7

	/*private*/ const REDIRECT = 'redirect';
	/*private*/ const NONEXISTENT = 'nonexistent';
	/*private*/ const CONCRETE_REVISION = 'concrete revision';

	/**
	 * @return self
	 */
	public static function redirect($revisionId, EntityId $redirectsTo) {

	}

	/**
	 * @return self
	 */
	public static function doesNotExist() {

	}

	/**
	 * @param int $id
	 * @return self
	 */
	public static function concreteRevision( $id ) {

	}

	private function __construct() {
	}

	/**
	 * @param callable $handler
	 * @return self
	 */
	public function onConcreteRevision( callable $handler ) {

	}

	/**
	 * @param callable $mapFunction
	 * @return self
	 */
	public function onRedirect( callable $handler ) {

	}

	/**
	 * @param callable $mapFunction
	 * @return self
	 */
	public function onNonexistentRevision( callable $handler ) {

	}

	/**
	 * @return mixed Returns value returned by one of the map functions
	 * @throws \Exception If target handler throws an exception
	 * @throws \LogicException If not all the handlers are specified
	 */
	public function map() {

	}
}



$revIdResult = LatestRevisionIdResult::doesNotExist();
$revIdResult = LatestRevisionIdResult::redirect( 123, new ItemId( 'Q3'));
$revIdResult = LatestRevisionIdResult::concreteRevision( 123);

$result = $revIdResult->onConcreteRevision(function ($revId) {return "Revision {$revId} exists"; })
	->onNonexistentRevision(function () { return 'Does not exist'; })
	->onRedirect(function ( $revId, EntityId $redirectsTo ) { return "Revision {$revId} redirects to {$redirectsTo->getSerialization()}"; })
	->map();

assert( $result === 'Does not exist' );
