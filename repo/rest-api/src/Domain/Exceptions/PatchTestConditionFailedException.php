<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Exceptions;

use Exception;

/**
 * @license GPL-2.0-or-later
 */
class PatchTestConditionFailedException extends Exception {

	private array $operation;
	/** @var mixed */
	private $actualValue;

	/**
	 * @param mixed $actualValue
	 */
	public function __construct( string $message, array $operation, $actualValue ) {
		parent::__construct( $message );
		$this->operation = $operation;
		$this->actualValue = $actualValue;
	}

	public function getOperation(): array {
		return $this->operation;
	}

	/**
	 * @return mixed
	 */
	public function getActualValue() {
		return $this->actualValue;
	}

}
