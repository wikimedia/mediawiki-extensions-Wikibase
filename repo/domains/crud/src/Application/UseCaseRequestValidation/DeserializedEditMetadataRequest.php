<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation;

use Wikibase\Repo\Domains\Crud\Domain\Model\UserProvidedEditMetadata;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedEditMetadataRequest {
	public function getEditMetadata(): UserProvidedEditMetadata;
}
