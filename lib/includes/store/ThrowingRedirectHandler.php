<?php
namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Redirect handler that throws UnresolvedRedirectException
 */
class ThrowingRedirectHandler implements RedirectHandler {
	/**
	 * (non-PHPdoc)
	 * @see \Wikibase\Lib\Store\RedirectHandler::handleRedirect()
	 */
	public function handleRedirect( EntityId $source, EntityId $target ) {
		throw new UnresolvedRedirectException( $target );
	}
}
