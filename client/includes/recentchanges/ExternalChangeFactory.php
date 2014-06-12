<?php

namespace Wikibase;

use InvalidArgumentException;
use RecentChange;
use UnexpectedValueException;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ExternalChangeFactory {

	/**
	 * @var string
	 */
	private $repoSiteId;

	/**
	 * @param string $repoSiteId
	 */
	public function __construct( $repoSiteId ) {
		$this->repoSiteId = $repoSiteId;
	}

	/**
	 * @since 0.5
	 *
	 * @param RecentChange $recentChange
	 *
	 * @throws UnexpectedValueException
	 * @return ExternalChange
	 */
	public function newFromRecentChange( RecentChange $recentChange ) {
		$changeParams = $this->extractChangeData( $recentChange );

		$itemId = $this->extractItemId( $changeParams['object_id'] );
		$changeType = $this->extractChangeType( $changeParams['type'] );
		$rev = $this->newRevisionData( $recentChange, $changeParams );

		return new ExternalChange( $itemId, $rev, $changeType );
	}

	/**
	 * @param RecentChange $recentChange
	 * @param array $changeParams
	 *
	 * @return RevisionData
	 */
	private function newRevisionData( RecentChange $recentChange, array $changeParams ) {
		$repoId = isset( $changeParams['site_id'] )
			? $changeParams['site_id'] : $this->repoSiteId;

		return new RevisionData(
			$recentChange->getAttribute( 'rc_user_text' ),
			$changeParams['page_id'],
			$changeParams['rev_id'],
			$changeParams['parent_id'],
			$recentChange->getAttribute( 'rc_timestamp' ),
			$this->extractComment( $changeParams ),
			$repoId
		);
	}

	/**
	 * @param RecentChange $recentChange
	 *
	 * @throws UnexpectedValueException
	 * @return array
	 */
	private function extractChangeData( RecentChange $recentChange ) {
		$params = unserialize( $recentChange->getAttribute( 'rc_params' ) );

		if ( !is_array( $params ) || !array_key_exists( 'wikibase-repo-change', $params ) ) {
			throw new UnexpectedValueException( 'Not a Wikibase change' );
		}

		$changeParams = $params['wikibase-repo-change'];

		$this->validateChangeData( $changeParams );

		return $changeParams;
	}

	/**
	 * @param array $changeParams
	 *
	 * @throws UnexpectedValueException
	 * @return bool
	 */
	private function validateChangeData( $changeParams ) {
		if ( !is_array( $changeParams ) ) {
			throw new UnexpectedValueException( 'Invalid Wikibase change' );
		}

		$keys = array( 'type', 'page_id', 'rev_id', 'parent_id', 'object_id' );

		foreach ( $keys as $key ) {
			if ( !array_key_exists( $key, $changeParams ) ) {
				throw new UnexpectedValueException( "$key key missing in change data" );
			}
		}

		return true;
	}

	/**
	 * @see EntityChange::getAction
	 *
	 * @param string $type
	 *
	 * @throws UnexpectedValueException
	 * @return string
	 */
	private function extractChangeType( $type ) {
		if ( !is_string( $type ) ) {
			throw new UnexpectedValueException( '$type must be a string.' );
		}

		list( , $changeType ) = explode( '~', $type, 2 );

		return $changeType;
	}

	/**
	 * @param string $prefixedId
	 *
	 * @throws UnexpectedValueException
	 * @return ItemId
	 */
	private function extractItemId( $prefixedId ) {
		try {
			return new ItemId( $prefixedId );
		} catch ( InvalidArgumentException $ex ) {
			throw new UnexpectedValueException( 'Invalid $itemId found for change.' );
		}
	}

	/**
	 * @fixme refactor comments handling!
	 *
	 * @param array|string $comment
	 * @param string $type
	 *
	 * @return string
	 */
	private function parseComment( $comment, $type ) {
		$newComment = array(
			'key' => 'wikibase-comment-update'
		);

		if ( is_array( $comment ) ) {
			if ( $type === 'wikibase-item~add' ) {
				// @todo: provide a link to the entity
				$newComment['key'] = 'wikibase-comment-linked';
			} elseif ( array_key_exists( 'sitelink', $comment ) ) {
				// @fixme site link change message
				$newComment['key'] = 'wikibase-comment-update';
			} else {
				$newComment['key'] = $comment['message'];
			}
		} elseif ( is_string( $comment ) ) {
			$newComment['key'] = $comment;
		}

		return $newComment;
	}

	/**
	 * @fixme refactor comments handling!
	 *
	 * @param array $changeParams
	 *
	 * @return string
	 */
	private function extractComment( $changeParams ) {
		$comment = array(
			'key' => 'wikibase-comment-update'
		);

		//TODO: If $changeParams['changes'] is set, this is a coalesced change.
		//	  Combine all the comments! Up to some max length?
		if ( array_key_exists( 'composite-comment', $changeParams ) ) {
			$comment['key'] = 'wikibase-comment-multi';
			$comment['numparams'] = $this->countCompositeComments( $changeParams['composite-comment'] );
		} elseif ( array_key_exists( 'comment', $changeParams  ) ) {
			$comment = $this->parseComment( $changeParams['comment'], $changeParams['type'] );
		}

		return $comment;
	}

	/**
	 * normalizes for extra empty comment in rc_params (see bug 45812)
	 * @fixme: can remove at some point in the future
	 *
	 * @param array $comments
	 *
	 * @return int
	 */
	private function countCompositeComments( $comments ) {
		$compositeComments = array_filter( $comments );

		return count( $compositeComments );
	}

}
