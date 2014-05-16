<?php
namespace Wikibase\Content;

use Exception;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\StorageException;

/**
 * Exception indicating that an attempt was made to access a redirected EntityId
 * without resolving the redirect first.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class UnresolvedRedirectException extends StorageException {

	/**
	 * @var EntityId
	 */
	private $redirectTargetId;

	/**
	 * @param EntityId $redirectTargetId The ID of the target Entity of the redirect
	 * @param string|null $message
	 * @param int $code
	 * @param Exception $previous
	 */
	public function __construct( EntityId $redirectTargetId, $message = null, $code = 0, Exception $previous = null ) {
		if ( $message === null ) {
			$message = "Unresolved redirect to " . $redirectTargetId->getSerialization();
		}

		parent::__construct( $message, $code, $previous );

		$this->redirectTargetId = $redirectTargetId;
	}

	/**
	 * Returns the ID of the entity referenced by the redirect.
	 *
	 * @return EntityId
	 */
	public function getRedirectTargetId() {
		return $this->redirectTargetId;
	}
}
