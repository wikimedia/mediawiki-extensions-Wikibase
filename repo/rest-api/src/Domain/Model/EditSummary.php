<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Model;

/**
 * @license GPL-2.0-or-later
 */
interface EditSummary {

	public const ADD_ACTION = 'add';
	public const PATCH_ACTION = 'patch';
	public const REPLACE_ACTION = 'replace';
	public const REMOVE_ACTION = 'remove';

	public function getEditAction(): string;

	public function getUserComment(): ?string;

}
