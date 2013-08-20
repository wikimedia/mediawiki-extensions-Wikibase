<?php

namespace Wikibase;

use MWException;
use Linker;
use InvalidArgumentException;
use UnexpectedValueException;
use \Wikibase\Client\WikibaseClient;

/**
 * Unserializes external changes
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
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ExternalChangeUnserializer {

	protected $repoLinker;

	public function __construct( RepoLinker $repoLinker ) {
		$this->repoLinker = $repoLinker;
	}

	public function unserialize( $recentChange ) {
		$params = unserialize( $recentChange->getAttribute( 'rc_params' ) );

		if ( !$params || !is_array( $params ) ) {
			throw new MWException( 'Failed to unserialize rc_params in recent change.' );
		}

		if ( ! array_key_exists( 'wikibase-repo-change', $params ) ) {
			throw new UnexpectedValueException( 'Not a Wikibase change' );
		}

		$changeData = $params['wikibase-repo-change'];

		if ( ! is_array( $changeData ) ) {
			throw new InvalidArgumentException( 'Invalid Wikibase change' );
		}

		// @todo page_id, rev_id and parent_id might only be needed for add / update types
		$keys = array( 'type', 'page_id', 'rev_id', 'parent_id' );

		foreach( $keys as $key ) {
			if ( !in_array( $key, $changeData ) ) {
				throw new InvalidArgumentException( "$key key missing in change data" );
			}
		}

		$changeType = $this->getChangeType( $changeData );

		$entityId = $this->titleTextFromEntityData( $changeData, false );
		$entityTitle = $this->titleTextFromEntityData( $changeData );

		$userName = $recentChange->getAttribute( 'rc_user_text' );
		$title = $recentChange->getTitle();

		$pageId = $changeData['page_id'];
		$revId = $changeData['rev_id'];
		$parentId = $changeData['parent_id'];

		$comment = $this->getComment( $changeData );

		return new ExternalChange(
			$changeData,
			$changeType,
			$entityId,
			$entityTitle,
			$userName,
			$title,
			$pageId,
			$revId,
			$parentId,
			$comment
		);
	}

	protected function getChangeType( $changeData ) {
		$validTypes = array( 'remove', 'restore', 'add', 'update' );

		$parts = explode( '~', $changeData['type'] );
		$changeType = $parts[1];

		if ( !in_array( $changeType, $validTypes ) ) {
			throw new InvalidArgumentException( 'invalid change type' );
		}

		return $changeType;
	}

	/**
	 * @since 0.2
	 *
	 * @param array $entityData
	 * @param bool $includeNamespace include namespace in title, such as Item:Q1
	 *
	 * @return string|bool
	 */
	protected function titleTextFromEntityData( $entityData, $includeNamespace = true ) {
		if ( ! isset( $entityData['object_id'] ) ) {
			throw new InvalidArgumentException( 'object_id missing from change data.' );
		}

		$id = $entityData['object_id'];

		if ( ctype_digit( $id ) || is_numeric( $id ) ) {
			// @deprecated
			// FIXME: this is evil; we seem to have lost all encapsulation at this point,
			// so some refactoring is needed to have sane access to the info here.
			$entityType = explode( '-', $entityData['entity_type'], 2 );

			$entityId = new EntityId( $entityType, (int)$id );
		} else {
			$idParser = WikibaseClient::getDefaultInstance()->getEntityIdParser();
			$entityId = $idParser->parse( $id );
		}

		$idFormatter = WikibaseClient::getDefaultInstance()->getEntityIdFormatter();
		$titleText = strtoupper( $idFormatter->format( $entityId ) );

		if ( $includeNamespace ) {
			$ns = $this->repoLinker->getNamespace( $entityId->getEntityType() );
			if ( !empty( $ns ) ) {
				$titleText = $ns . ':' . $titleText;
			}
		}

		return $titleText;
	}

	public function parseComment( $entityData ) {
		$comment = $entityData['comment'];
		$message = null;
		$param = null;

		if ( is_array( $comment ) ) {
			if ( $entityData['type'] === 'wikibase-item~add' ) {
				// @todo: provide a link to the entity
				$message = wfMessage( 'wikibase-comment-linked' )->text();
			} elseif ( array_key_exists( 'sitelink', $comment ) ) {
				// @fixme site link change message
				$message = wfMessage( 'wikibase-comment-update' )->text();
			} else {
				$message = wfMessage( $comment['message'] )->text();
			}
		} else {
			$msg = wfMessage( $comment );
			$message = $msg->exists() ? $msg->text() : $comment;
		}

		return $message;
	}

	/**
	 * @since 0.2
	 *
	 * @param array $entityData
	 *
	 * @return string
	 */
	public function getComment( $entityData ) {
		//TODO: If $entityData['changes'] is set, this is a coalesced change.
		//	  Combine all the comments! Up to some max length?
		if ( array_key_exists( 'composite-comment', $entityData ) ) {
			$commentText = wfMessage( 'wikibase-comment-multi' )->numParams( count( $entityData['composite-comment'] ) )->text();
		} elseif ( array_key_exists( 'comment', $entityData  ) ) {
			$commentText = $this->parseComment( $entityData );
		} else {
			$commentText = '';
		}

		return Linker::commentBlock( $commentText );
	}

}
