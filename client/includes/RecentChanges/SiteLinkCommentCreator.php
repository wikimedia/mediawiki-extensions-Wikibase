<?php

namespace Wikibase\Client\RecentChanges;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOp;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use Language;
use Message;
use MWException;
use SiteLookup;
use Title;

/**
 * Creates an array structure with comment information for storing
 * in the rc_params column of the RecentChange table, for use in
 * generating recent change comments for wikibase changes.
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class SiteLinkCommentCreator {

	/**
	 * @var Language
	 */
	private $language;

	/**
	 * @var SiteLookup
	 */
	private $siteLookup;

	/**
	 * @var string
	 */
	private $siteId;

	/**
	 * @param Language $language
	 * @param SiteLookup $siteLookup
	 * @param string $siteId
	 */
	public function __construct( Language $language, SiteLookup $siteLookup, $siteId ) {
		$this->siteId = $siteId;
		$this->siteLookup = $siteLookup;
		$this->language = $language;
	}

	/**
	 * Returns the comment to use in RC and history entries for this change.
	 * This may be a complex structure. It will be interpreted by
	 *
	 * @param Diff|null $siteLinkDiff
	 * @param string $action e.g. 'remove', see the constants in EntityChange
	 * @param Title|null $title The page we create an edit summary for. Taken into account
	 *         when creating an article specific edit summary on site link changes. Ignored otherwise.
	 *
	 * @return string|null A human readable edit summary (limited wikitext),
	 *         or null if no summary could be created for the sitelink change.
	 */
	public function getEditComment( ?Diff $siteLinkDiff, $action, Title $title = null ) {
		if ( $siteLinkDiff !== null && !$siteLinkDiff->isEmpty() ) {
			$siteLinkMessage = $this->getSiteLinkMessage( $action, $siteLinkDiff, $title );

			if ( !empty( $siteLinkMessage ) ) {
				return $this->generateComment( $siteLinkMessage );
			}
		}

		return null;
	}

	/**
	 * Whether we need a target specific edit summary for the given diff on the given
	 * page.
	 *
	 * @param Diff $siteLinkDiff
	 * @param Title $title
	 *
	 * @return bool
	 */
	public function needsTargetSpecificSummary( Diff $siteLinkDiff, Title $title ) {
		if ( $siteLinkDiff->isEmpty() ) {
			return false;
		}

		$diffOps = $siteLinkDiff->getOperations();

		// Change involved site link to client wiki
		if ( !array_key_exists( $this->siteId, $diffOps ) ) {
			return false;
		}

		// $siteLinkDiff changed from containing atomic diffs to
		// containing map diffs. For B/C, handle both cases.
		$diffOp = $diffOps[$this->siteId];

		if ( $diffOp instanceof Diff ) {
			if ( $diffOp->offsetExists( 'name' ) ) {
				$diffOp = $diffOp['name'];
			} else {
				// Change to badges only, use original message
				return false;
			}
		}

		if ( !( $diffOp instanceof DiffOpChange ) ) {
			// We only handle sitelink changes specifically for now
			return false;
		}

		return $title->getPrefixedText() === $diffOp->getOldValue() ||
			$title->getPrefixedText() === $diffOp->getNewValue();
	}

	/**
	 * Returns an array structure suitable for building an edit summary for the respective
	 * change to site links.
	 *
	 * @param string $action e.g. 'remove', see the constants in EntityChange
	 * @param Diff $siteLinkDiff The change's site link diff
	 * @param Title|null $title The page we create an edit summary for
	 *
	 * @return array|null
	 */
	private function getSiteLinkMessage( $action, Diff $siteLinkDiff, Title $title = null ) {
		if ( $siteLinkDiff->isEmpty() ) {
			return null;
		}

		//TODO: Implement comments specific to the affected page.
		//       Different pages may be affected in different ways by the same change.
		//       Also, merged changes may affect the same page in multiple ways.
		$diffOps = $siteLinkDiff->getOperations();
		$siteId = $this->siteId;

		// change involved site link to client wiki
		if ( array_key_exists( $siteId, $diffOps ) ) {
			// $siteLinkDiff changed from containing atomic diffs to
			// containing map diffs. For B/C, handle both cases.
			$diffOp = $diffOps[$siteId];

			if ( $diffOp instanceof Diff ) {
				if ( $diffOp->offsetExists( 'name' ) ) {
					$diffOp = $diffOp['name'];
				} else {
					// change to badges only, use original message
					return null;
				}
			}

			$params = $this->getSiteLinkAddRemoveParams( $diffOp, $action, $siteId, $title );
		} else {
			$diffOpCount = count( $diffOps );
			if ( $diffOpCount === 1 ) {
				$params = $this->getSiteLinkChangeParams( $diffOps );
			} else {
				// multiple changes, use original message
				return null;
			}
		}

		return $params;
	}

	/**
	 * @param DiffOp[] $diffs
	 *
	 * @return array|null
	 */
	private function getSiteLinkChangeParams( array $diffs ) {
		$messagePrefix = 'wikibase-comment-sitelink-';
		/* Messages used:
			wikibase-comment-sitelink-add wikibase-comment-sitelink-change wikibase-comment-sitelink-remove
		*/
		$params = [ 'message' => $messagePrefix . 'change' ];

		foreach ( $diffs as $siteId => $diff ) {
			// backwards compatibility in case of old, pre-badges changes in the queue
			$diffOp = ( ( $diff instanceof Diff ) && $diff->offsetExists( 'name' ) ) ? $diff['name'] : $diff;
			$args = $this->getChangeParamsForDiffOp( $diffOp, $siteId, $messagePrefix );

			if ( empty( $args ) ) {
				return null;
			}

			$params = array_merge(
				$params,
				$args
			);

			// TODO: Handle if there are multiple DiffOp's here.
			break;
		}

		return $params;
	}

	/**
	 * @param DiffOp $diffOp
	 * @param string $siteId
	 * @param string $messagePrefix
	 *
	 * @return array|null
	 */
	private function getChangeParamsForDiffOp( DiffOp $diffOp, $siteId, $messagePrefix ) {
		$params = [];

		if ( $diffOp instanceof DiffOpAdd ) {
			$params['message'] = $messagePrefix . 'add';
			$params['sitelink'] = [
				'newlink' => [
					'site' => $siteId,
					'page' => $diffOp->getNewValue(),
				],
			];
		} elseif ( $diffOp instanceof DiffOpRemove ) {
			$params['message'] = $messagePrefix . 'remove';
			$params['sitelink'] = [
				'oldlink' => [
					'site' => $siteId,
					'page' => $diffOp->getOldValue(),
				],
			];
		} elseif ( $diffOp instanceof DiffOpChange ) {
			$params['sitelink'] = [
				'oldlink' => [
					'site' => $siteId,
					'page' => $diffOp->getOldValue(),
				],
				'newlink' => [
					'site' => $siteId,
					'page' => $diffOp->getNewValue(),
				],
			];
		} else {
			// whatever
			$params = null;
		}

		return $params;
	}

	/**
	 * @param DiffOp $diffOp
	 * @param string $action e.g. 'remove', see the constants in EntityChange
	 * @param string $siteId
	 * @param Title|null $title The page we create an edit summary for
	 *
	 * @return array|null
	 */
	private function getSiteLinkAddRemoveParams( DiffOp $diffOp, $action, $siteId, Title $title = null ) {
		$params = [];

		if ( in_array( $action, [ 'remove', 'restore' ] ) ) {
			// Messages: wikibase-comment-remove, wikibase-comment-restore
			$params['message'] = 'wikibase-comment-' . $action;
		} elseif ( $diffOp instanceof DiffOpAdd ) {
			$params['message'] = 'wikibase-comment-linked';
		} elseif ( $diffOp instanceof DiffOpRemove ) {
			$params['message'] = 'wikibase-comment-unlink';
		} elseif ( $diffOp instanceof DiffOpChange ) {
			if ( $title && $title->getPrefixedText() === $diffOp->getOldValue() ) {
				$params['message'] = 'wikibase-comment-unlink';
			} elseif ( $title && $title->getPrefixedText() === $diffOp->getNewValue() ) {
				$params['message'] = 'wikibase-comment-linked';
			} else {
				$params['message'] = 'wikibase-comment-sitelink-change';

				$params['sitelink'] = [
					'oldlink' => [
						'site' => $siteId,
						'page' => $diffOp->getOldValue(),
					],
					'newlink' => [
						'site' => $siteId,
						'page' => $diffOp->getNewValue(),
					],
				];
			}
		} else {
			// whatever
			$params = null;
		}

		return $params;
	}

	/**
	 * @param string $siteId
	 * @param string $pageTitle
	 *
	 * @return string wikitext interwiki link
	 */
	private function getSitelinkWikitext( $siteId, $pageTitle ) {
		// Try getting the interwiki id from the Site object of the link target
		$site = $this->siteLookup->getSite( $siteId );

		if ( $site ) {
			$interwikiIds = $site->getInterwikiIds();

			if ( isset( $interwikiIds[0] ) ) {
				$interwikiId = $interwikiIds[0];

				return "[[:$interwikiId:$pageTitle]]";
			}
		}

		// @todo: provide a mechanism to get the interwiki id for a sister project wiki.
		// e.g. get "voy:it" for Italian Wikivoyage from English Wikipedia. (see T117738)
		return "$siteId:$pageTitle";
	}

	/**
	 * @param string $key
	 *
	 * @return Message
	 * @throws MWException
	 */
	private function msg( $key, ...$params ) {
		if ( isset( $params[0] ) && is_array( $params[0] ) ) {
			$params = $params[0];
		}

		return wfMessage( $key, $params )->inLanguage( $this->language );
	}

	/**
	 * @param array $messageSpec
	 *
	 * @return string An edit summary (as limited wikitext).
	 */
	private function generateComment( array $messageSpec ) {
		$key = $messageSpec['message'];
		$args = [];

		if ( isset( $messageSpec['sitelink']['oldlink'] ) ) {
			$link = $messageSpec['sitelink']['oldlink'];
			$args[] = $this->getSitelinkWikitext( $link['site'], $link['page'] );
		}

		if ( isset( $messageSpec['sitelink']['newlink'] ) ) {
			$link = $messageSpec['sitelink']['newlink'];
			$args[] = $this->getSitelinkWikitext( $link['site'], $link['page'] );
		}

		$msg = $this->msg( $key, $args );
		return $msg->text();
	}

}
