<?php

namespace Wikibase;

/**
 * @todo: factor out specific types of handling and delegate tasks
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.2
 *
 * @file
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ClientChangeHandler {

	protected $change;

	/**
	 * @since 0.4
	 *
	 * @param Change $change
	 */
	public function __construct( Change $change ) {
		$this->change = $change;
	}

	/**
	 * @since 0.3
	 *
	 * @return bool
	 */
	public function changeNeedsRendering() {
		if ( $this->change instanceof ItemChange ) {
			if ( !$this->change->getSiteLinkDiff()->isEmpty() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @since 0.3
	 *
	 * @return string|null
	 */
	public function siteLinkComment() {
		$comment = null;
		if ( !$this->change->getSiteLinkDiff()->isEmpty() ) {
			$siteLinkDiff = $this->change->getSiteLinkDiff();
			$changeKey = key( $siteLinkDiff );
			$diffOp = $siteLinkDiff[$changeKey];

			$action = 'change';
			if ( $diffOp instanceof \Diff\DiffOpAdd ) {
				$action = 'add';
			} else if ( $diffOp instanceof \Diff\DiffOpRemove ) {
				$action = 'remove';
			}

			$comment = "wbc-comment-sitelink-$action~" . key( $siteLinkDiff );
		}

		return $this->parseComment( $comment );
	}

	/**
	 * @since 0.3
	 *
	 * @param string $comment
	 *
	 * @throws \MWException
	 *
	 * @return array
	 */
	public function parseComment( $comment ) {
		list( $message, $sitecode ) = explode( '~', $comment );

		// check that $sitecode is valid
		if ( \Sites::singleton()->getSite( $sitecode ) === false ) {
			throw new \MWException( "Site code $sitecode does not exist in the sites table." );
		}

		$params = array(
			'message' => $message,
			'sitecode' => $sitecode,
		);

		if ( $sitecode === Settings::get( 'siteGlobalID' ) ) {
			$action = $this->change->getAction();
			if ( $action === 'remove' ) {
				$params['message'] = 'wbc-comment-remove';
			} else if ( $action === 'restore' ) {
				$params['message'] = 'wbc-comment-restore';
			} else if ( $action === 'add' ) {
				$params['message'] = 'wbc-comment-linked';
			} else {
				$params['message'] = 'wbc-comment-unlink';
			}
		} else {
			if ( $this->change->getSiteLinkDiff() ) {
				$siteLinkDiff = $this->change->getSiteLinkDiff();
				$diffOps = $siteLinkDiff->getOperations();
				foreach( $diffOps as $siteCode => $diffOp ) {
					$site = \SitesTable::singleton()->selectRow(
						null,
						array( 'global_key' => $siteCode )
					);
					// @todo: language might not always work? we need local interwiki id
					$siteLang = $site->getField( 'language' );
					if ( $diffOp instanceof \Diff\DiffOpAdd ) {
						$params['sitelink'] = array(
							'newlink' =>  array(
								'lang' => $siteLang,
								'page' => $diffOp->getNewValue()
							)
						);
					} else if ( $diffOp instanceof \Diff\DiffOpRemove ) {
						$params['sitelink'] = array(
							'oldlink' => array(
								'lang' => $siteLang,
								'page' => $diffOp->getOldValue()
							)
						);
					} else if ( $diffOp instanceof \Diff\DiffOpChange ) {
						$params['sitelink'] = array(
							'oldlink' => array(
								'lang' => $siteLang,
								'page' => $diffOp->getOldValue()
							),
							'newlink' => array(
								'lang' => $siteLang,
								'page' => $diffOp->getNewValue()
							)
						);
					}
					// @todo: because of edit conflict bug in repo
					// sometimes we get multiple stuff in diffOps
					break;
				}
			}
		}

		return $params;
	}
}
