<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\RedirectEntityRevision;

/**
 * Redirect handler that passes the redirect lookup down the chain
 * trying to look up the redirected entity
 */
class ChainRedirectHandler implements RedirectHandler {

	/**
	 * @var EntityRevisionLookup
	 */
	private $lookup;

	/**
	 * @var int The maximum number of redirects to follow
	 */
	private $maxResolutionDepth;

	public function __construct( EntityRevisionLookup $lookup, $maxResolutionDepth = 1 ) {
		$this->lookup = $lookup;
		$this->maxResolutionDepth = $maxResolutionDepth;
	}

	/**
	 * Handle redirect
	 * Returns RedirectEntityRevision containing information about the source
	 * and the target data.
	 * @param EntityId $source
	 * @param EntityId $target
	 * @return RedirectEntityRevision|null
	 */
	public function handleRedirect( EntityId $source, EntityId $target ) {
		if( $this->maxResolutionDepth > 0 ) {
			$this->maxResolutionDepth--;
			$result = $this->lookup->getEntityRevision( $target );
			if( $result ) {
				if( $result instanceof RedirectEntityRevision ) {
					$source = $result->getSource();
				}
				$result = new RedirectEntityRevision( $result, $source );
			}
			return $result;
		}

		$thandler = new ThrowingRedirectHandler();
		return $thandler->handleRedirect( $source, $target );
	}
}
