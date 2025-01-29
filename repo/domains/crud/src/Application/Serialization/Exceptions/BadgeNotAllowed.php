<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Serialization\Exceptions;

use Throwable;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @license GPL-2.0-or-later
 */
class BadgeNotAllowed extends SerializationException {

	private ItemId $badge;

	public function __construct( ItemId $badge, string $message = '', ?Throwable $previous = null ) {
		$this->badge = $badge;
		parent::__construct( $message, 0, $previous );
	}

	public function getBadge(): ItemId {
		return $this->badge;
	}

}
