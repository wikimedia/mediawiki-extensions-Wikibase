<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Model;

/**
 * @license GPL-2.0-or-later
 */
class LabelsEditSummary implements EditSummary {

	public function getEditAction(): string {
		return '';
	}

	public function getUserComment(): ?string {
		return '';
	}
}
