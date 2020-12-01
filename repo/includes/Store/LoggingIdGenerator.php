<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Store;

use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Wraps another {@link IdGenerator} and logs its activity.
 *
 * @license GPL-2.0-or-later
 */
class LoggingIdGenerator implements IdGenerator {

	/** @var IdGenerator */
	private $idGenerator;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(
		IdGenerator $idGenerator,
		LoggerInterface $logger
	) {
		$this->idGenerator = $idGenerator;
		$this->logger = $logger;
	}

	public function getNewId( $type ) {
		global $wgRequest;

		$id = $this->idGenerator->getNewId( $type );

		$this->logger->info( __METHOD__ . ': generated {idType} ID {id}', [
			'idType' => $type,
			'id' => $id,
			'requestPostValues' => $wgRequest->getPostValues(),
			'exception' => new RuntimeException(),
		] );

		return $id;
	}
}
