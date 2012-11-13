<?php

namespace Wikibase;

/**
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
 * @since 0.3
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ChangeRevisionObject implements ChangeRevision {

	/**
	 * @since 0.3
	 * @var array
	 */
	protected $data;

	/**
	 * @since 0.3
	 *
	 * @return array|null
	 */
	public function getMetadata() {
		if ( $this->data !== null ) {
			return $this->data;
		}

		return false;
	}

	/**
	 * @since 0.3
	 *
	 * @param array $metadata
	 *
	 * @return bool
	 */
	public function setMetadata( array $metadata ) {
		$validKeys = array(
			'comment',
			'page_id',
			'bot',
			'rev_id',
			'parent_id',
			'user_text'
		);

		if ( is_array( $metadata ) ) {
			foreach ( array_keys( $metadata ) as $key ) {
				if ( !in_array( $key, $validKeys ) ) {
					unset( $metadata[$key] );
				}
			}
			$this->data = $metadata;

			return true;
		}

		return false;
	}

	public static function newFromRevision( \Revision $revision ) {
		$instance = new self();

		$user = \User::newFromId( $revision->getUser() );

		$instance->setMetadata( array(
			'user_text' => $revision->getUserText(),
			'bot' => in_array( 'bot', $user->getRights() ),
			'page_id' => $revision->getPage(),
			'rev_id' => $revision->getId(),
			'parent_id' => $revision->getParentId(),
			'comment' => $revision->getComment(),
		) );

		return $instance;
	}

	public static function newFromUser( \User $user ) {
		$instance = new self();

		$instance->setMetadata( array(
			'user_text' => $user->getName(),
			'page_id' => 0,
			'rev_id' => 0,
			'parent_id' => 0,
			'comment' => '',
		) );

		return $instance;
	}
}
