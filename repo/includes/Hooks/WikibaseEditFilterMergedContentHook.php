<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Hooks;

use MediaWiki\Content\Content;
use MediaWiki\Context\IContextSource;
use MediaWiki\Hook\EditFilterMergedContentHook;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Status\Status;
use MediaWiki\User\User;

/**
 * This is a hook handler interface, see docs/Hooks.md in MediaWiki core.
 * Use the hook name "EditFilterMergedContent" to register
 * handlers implementing this interface.
 *
 * This interface represents how Wikibase calls the MediaWiki core hook.
 * Hook handlers should usually implement the core interface instead.
 * (If you need the extra `$slotRole` parameter,
 * you can still declare it even without implementing this interface.)
 *
 * @see EditFilterMergedContentHook
 * @license GPL-2.0-or-later
 */
interface WikibaseEditFilterMergedContentHook {

	/**
	 * @see EditFilterMergedContentHook::onEditFilterMergedContent()
	 * @param IContextSource $context
	 * @param Content $content Content of the edit box
	 * @param Status $status Status object to represent errors, etc.
	 * @param string $summary Edit summary for page
	 * @param User $user User whois performing the edit
	 * @param bool $minoredit Whether the edit was marked as minor by the user.
	 * @param string $slotRole This extra parameter is not part of the core hook interface,
	 * but is passed in by Wikibase and read by AbuseFilter; see T288885.
	 * @return bool|void False or no return value with not $status->isOK() to abort the edit
	 *    and show the edit form, true to continue. But because multiple triggers of this hook
	 *    may have different behavior in different version (T273354), you'd better return false
	 *    and set $status->value to EditPage::AS_HOOK_ERROR_EXPECTED or any other customized value.
	 */
	public function onEditFilterMergedContent(
		IContextSource $context,
		Content $content,
		Status $status,
		$summary,
		User $user,
		$minoredit,
		string $slotRole = SlotRecord::MAIN
	);

}
