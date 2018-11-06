<?php

namespace Wikibase\DataModel\Services\Lookup;

use Exception;
use InvalidArgumentException;

/**
 * @since 3.7
 *
 * @license GPL-2.0-or-later
 */
class UnknownForeignRepositoryException extends InvalidArgumentException {

	/**
	 * @var string
	 */
	private $repositoryName;

	/**
	 * @param string $repositoryName
	 * @param string|null $message
	 * @param Exception|null $previous
	 */
	public function __construct( $repositoryName, $message = null, Exception $previous = null ) {
		$this->repositoryName = $repositoryName;

		parent::__construct(
			$message ?: 'Unknown repository name: ' . $repositoryName,
			0,
			$previous
		);
	}

	/**
	 * @return string
	 */
	public function getRepositoryName() {
		return $this->repositoryName;
	}

}
