<?php
namespace Wikibase;

use Site;
use SiteList;
use Diff\Diff;
use Wikibase\Client\WikibaseClient;

/**
 * @since 0.5
 *
 * @file
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SiteLinkCommentSerializer {

	/**
	 * @var Site
	 */
	protected $site;

	/**
	 * @var SiteList
	 */
	protected $sites;

	/**
	 * @param Site $site
	 */
	public function __construct( Site $site, SiteList $sites ) {
		$this->site = $site;
		$this->sites = $sites;
	}

	/**
	 * Returns the comment to use in RC and history entries for this change.
	 * This may be a complex structure. It will be interpreted by
	 *
	 * @since 0.4
	 *
	 * @param Change $change the change to get a comment for
	 *
	 * @return array|null|string
	 */
	public function getEditComment( Change $change ) {
		if ( $change instanceof EntityChange ) {
			$siteLinkDiff = ( $change instanceof ItemChange ) ? $change->getSiteLinkDiff() : null;

			if ( $siteLinkDiff !== null && !$siteLinkDiff->isEmpty() ) {
				$comment = $this->getSiteLinkComment( $change->getAction(), $siteLinkDiff );
			} else {
				$comment = $change->getComment();
			}
		} else {
			$comment = null; //TODO: some nice default comment?
		}

		return $comment;
	}

	/**
	 * Returns an array structure suitable for building an edit summary for the respective
	 * change to site links.
	 *
	 * @since 0.4
	 *
	 * @param string      $action Change action
	 * @param \Diff\Diff $siteLinkDiff The change's site link diff
	 *
	 * @return array|null
	 */
	protected function getSiteLinkComment( $action, Diff $siteLinkDiff ) {
		$params = null;

		if ( $siteLinkDiff->isEmpty() ) {
			return null;
		}

		wfProfileIn( __METHOD__ );

		//TODO: Implement comments specific to the affected page.
		//       Different pages may be affected in different ways by the same change.
		//       Also, merged changes may affect the same page in multiple ways.

		$params = array();
		$siteGlobalId = $this->site->getGlobalId();

		// change involved site link to client wiki
		if ( array_key_exists( $siteGlobalId, $siteLinkDiff ) ) {

			$diffOp = $siteLinkDiff[$siteGlobalId]['name'];

			if ( $action === 'remove' ) {
				$params['message'] = 'wikibase-comment-remove';
			} elseif ( $action === 'restore' ) {
				$params['message'] = 'wikibase-comment-restore';
			} elseif ( $diffOp instanceof \Diff\DiffOpAdd ) {
				$params['message'] = 'wikibase-comment-linked';
			} elseif ( $diffOp instanceof \Diff\DiffOpRemove ) {
				$params['message'] = 'wikibase-comment-unlink';
			} elseif ( $diffOp instanceof \Diff\DiffOpChange ) {
				$params['message'] = 'wikibase-comment-sitelink-change';

				// FIXME: this code appears to be doing something incorrect as "best effort"
				// rather than allowing for proper error handling

				$params['sitelink'] = array(
					'oldlink' => array(
						'site' => $siteGlobalId,
						'page' => $diffOp->getOldValue()
					),
					'newlink' => array(
						'site' => $siteGlobalId,
						'page' => $diffOp->getNewValue()
					)
				);
			}
		} else {
			$messagePrefix = 'wikibase-comment-sitelink-';
			/* Messages used:
				wikibase-comment-sitelink-add wikibase-comment-sitelink-change wikibase-comment-sitelink-remove
			*/
			$params['message'] = $messagePrefix . 'change';

			foreach( $siteLinkDiff as $siteKey => $diff ) {
				$site = $this->sites->getSite( $siteKey );

				if( !$site ) {
					trigger_error( "Could not get site with globalId $siteKey.", E_USER_WARNING );
					continue;
				}

				if ( $diff->isEmpty() ) {
					continue;
				}

				$diffOps = $diff->getOperations();

				// @fixme this only reports the first change
				foreach( $diffOps as $diffOp ) {
					if ( $diffOp instanceof \Diff\DiffOpAdd ) {
						$params['message'] = $messagePrefix . 'add';
						$params['sitelink'] = array(
							'newlink' => array(
								'site' => $siteKey,
								'page' => $diffOp->getNewValue()
							)
						);
					} elseif ( $diffOp instanceof \Diff\DiffOpRemove ) {
						$params['message'] = $messagePrefix . 'remove';
						$params['sitelink'] = array(
							'oldlink' => array(
								'site' => $siteKey,
								'page' => $diffOp->getOldValue()
							)
						);
					} elseif ( $diffOp instanceof \Diff\DiffOpChange ) {
						$params['sitelink'] = array(
							'oldlink' => array(
								'site' => $siteKey,
								'page' => $diffOp->getOldValue()
							),
							'newlink' => array(
								'site' => $siteKey,
								'page' => $diffOp->getNewValue()
							)
						);
					}
					// @todo: because of edit conflict bug in repo
					// sometimes we get multiple stuff in diffOps
					break;
				}

				break;
			}
		}

		wfProfileOut( __METHOD__ );
		return $params;
	}
}
