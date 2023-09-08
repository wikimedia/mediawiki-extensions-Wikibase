<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases;

use Wikibase\Repo\RestApi\Domain\Model\UserProvidedEditMetadata;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedEditMetadataRequest {
	public function getEditMetadata(): UserProvidedEditMetadata;
}
