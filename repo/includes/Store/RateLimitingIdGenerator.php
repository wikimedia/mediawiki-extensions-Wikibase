<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Store;

use IContextSource;
use Status;
use Wikibase\Lib\Store\StorageException;

/**
 * @license GPL-2.0-or-later
 */
class RateLimitingIdGenerator implements IdGenerator {

	public const RATELIMIT_NAME = 'wikibase-idgenerator';

	/** @var IdGenerator */
	private $idGenerator;

	/** @var IContextSource */
	private $contextSource;

	public function __construct(
		IdGenerator $idGenerator,
		IContextSource $contextSource
	) {
		$this->idGenerator = $idGenerator;
		$this->contextSource = $contextSource;
	}

	public function getNewId( $type ) {
		if ( $this->contextSource->getUser()->pingLimiter( self::RATELIMIT_NAME ) ) {
			throw new StorageException( Status::newFatal( 'actionthrottledtext' ) );
		}

		return $this->idGenerator->getNewId( $type );
	}

}
