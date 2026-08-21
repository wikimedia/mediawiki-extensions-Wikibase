<?php

namespace Wikibase\Repo\Hooks\Helpers;

use MediaWiki\Output\OutputPage;

/**
 * Determined (likely) editability of an OutputPage by inspecting this god object's properties.
 * Most things feel like they should be preconfigured properties but are only known on call
 * time as this is used in a hook.
 *
 * @license GPL-2.0-or-later
 */
class OutputPageEditability {

	public function validate( OutputPage $out ): bool {
		return $out->getAuthority()->probablyCan( 'edit', $out->getTitle() )
			&& $this->isEditView( $out );
	}

	/**
	 * This is mostly a duplicate of
	 * @see \Wikibase\Repo\Actions\ViewEntityAction::isEditable()
	 */
	private function isEditView( OutputPage $out ): bool {
		return $out->isRevisionCurrent()
			&& !$this->isDiff( $out )
			&& !$out->isPrintable();
	}

	private function isDiff( OutputPage $out ): bool {
		return $out->getRequest()->getCheck( 'diff' );
	}

}
