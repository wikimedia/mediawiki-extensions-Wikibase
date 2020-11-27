<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Store;

use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

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

		$requestPostValues = $wgRequest->getPostValues();
		$exception = new RuntimeException();

		$this->logger->debug( __METHOD__ . ': generating {idType} ID', [
			'idType' => $type,
			'requestPostValues' => $requestPostValues,
			'exception' => $exception,
		] );

		try {
			$id = $this->idGenerator->getNewId( $type );
		} catch ( Throwable $throwable ) {
			$this->logger->debug( __METHOD__ . ': failed to generate {idType} ID', [
				'idType' => $type,
				'requestPostValues' => $requestPostValues,
				'exception' => $throwable,
			] );
			throw $throwable;
		}

		$this->logger->info( __METHOD__ . ': generated {idType} ID {id}', [
			'idType' => $type,
			'id' => $id,
			'requestPostValues' => $requestPostValues,
			'exception' => $exception,
		] );

		return $id;
	}
}
