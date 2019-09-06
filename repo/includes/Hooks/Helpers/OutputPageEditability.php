<?php

namespace Wikibase\Repo\Hooks\Helpers;

use MediaWiki\MediaWikiServices;
use OutputPage;
use Title;
use User;

/**
 * Determined (likely) editability of an OutputPage by inspecting this god object's properties.
 * Most things feel like they should be preconfigured properties but are only known on call
 * time as this is used in a hook.
 *
 * @license GPL-2.0-or-later
 */
class OutputPageEditability {

	/**
	 * @param OutputPage $out
	 * @return bool
	 */
	public function validate( OutputPage $out ) {
		return $this->isProbablyEditable( $out->getUser(), $out->getTitle() )
			&& $this->isEditView( $out );
	}

	/**
	 * This is duplicated from
	 * @see OutputPage::getJSVars - wgIsProbablyEditable
	 *
	 * @param User $user
	 * @param Title $title
	 *
	 * @return bool
	 */
	private function isProbablyEditable( User $user, Title $title ) {
		$pm = MediaWikiServices::getInstance()->getPermissionManager();
		return $pm->quickUserCan( 'edit', $user, $title )
			&& ( $title->exists() || $pm->quickUserCan( 'create', $user, $title ) );
	}

	/**
	 * This is mostly a duplicate of
	 * @see \Wikibase\ViewEntityAction::isEditable
	 *
	 * @param OutputPage $out
	 *
	 * @return bool
	 */
	private function isEditView( OutputPage $out ) {
		return $this->isLatestRevision( $out )
			&& !$this->isDiff( $out )
			&& !$out->isPrintable();
	}

	private function isDiff( OutputPage $out ) {
		return $out->getRequest()->getCheck( 'diff' );
	}

	private function isLatestRevision( OutputPage $out ) {
		return !$out->getRevisionId() // the revision id can be null on a ParserCache hit, but only for the latest revision
			|| $out->getRevisionId() === $out->getTitle()->getLatestRevID();
	}

}
