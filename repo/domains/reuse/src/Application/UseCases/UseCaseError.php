<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Reuse\Application\UseCases;

use Exception;

/**
 * @license GPL-2.0-or-later
 */
class UseCaseError extends Exception {
	public function __construct( public readonly UseCaseErrorType $type, string $message ) {
		parent::__construct( $message );
	}
}
