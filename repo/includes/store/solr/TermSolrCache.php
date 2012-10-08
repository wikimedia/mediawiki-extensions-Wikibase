<?php

namespace Wikibase;

/**
 * Term lookup cache.
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
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Denny Vrandecic < vrandecic@gmail.com >
 */
class TermSolrCache extends TermSqlCache {

	/**
	 * @since 0.2
	 *
	 * @var Solarium_Client $solr
	 */
	protected $solr;

	/**
	 * Constructor.
	 *
	 * @since 0.2
	 *
	 * @param string $tableName
	 * @param integer $readDb
	 */
	public function __construct( $tableName, $readDb = DB_SLAVE ) {
		parent::__construct( $tableName, $readDb );
		global $wgWBSolarium;
		if ( $wgWBSolarium == '' ) {
			// TODO do something. This should not happen!
			$this->solr = null;
		} else {
			require_once( $wgWBSolarium );
			\Solarium_Autoloader::register();
			$this->solr = new \Solarium_Client();
		}
	}

	/**
	 * @see TermCache::saveTermsOfEntity
	 *
	 * @since 0.2
	 *
	 * @param Entity $entity
	 *
	 * @return boolean Success indicator
	 */
	public function saveTermsOfEntity( Entity $entity ) {
		$success = parent::saveTermsOfEntity( $entity );
		if  ( $success and $this->solr ) {
			$doc = new \Solarium_Document_ReadWrite();
			$doc['id'] = $entity->getPrefixedId();
			$doc['entityType'] = $entity->getType();

			foreach ( $entity->getDescriptions() as $languageCode => $description ) {
				$doc['description_' . $languageCode] = $description;
			}

			foreach ( $entity->getLabels() as $languageCode => $label ) {
				$doc['label_' . $languageCode] = $label;
			}

			foreach ( $entity->getAllAliases() as $languageCode => $aliases ) {
				$doc['alias_' . $languageCode] = $aliases;
			}

			$update = $this->solr->createUpdate();

			$update->addDocuments(array($doc));

			$update->addCommit();
			$result = $this->solr->update($update);

			return $result->getStatus();
		}  else return false;

	}

	/**
	 * @see TermCache::deleteTermsOfEntity
	 *
	 * @since 0.2
	 *
	 * @param Entity $entity
	 *
	 * @return boolean Success indicator
	 */
	public function deleteTermsOfEntity( Entity $entity ) {
		$success = parent::saveTermsOfEntity( $entity );
		if  ( $success and $this->solr ) {
			$update = $this->solr->createUpdate();
			$update->addDeleteById($entity->getPrefixedId());

			$update->addCommit();
			$result = $this->solr->update($update);

			return $result->getStatus();
		} else return false;
	}

	/**
	 * @see TermCache::clear
	 *
	 * @since 0.2
	 *
	 * @return boolean Success indicator
	 */
	public function clear() {
		$success = parent::clear();
		if  ( $success and $this->solr ) {
			$update = $this->solr->createUpdate();
			$update->addDeleteQuery('*:*');

			$update->addCommit();
			$result = $this->solr->update($update);

			return $result->getStatus();
		} else return false;
	}

	/**
	 * @see TermCache::getEntityIdsForLabel
	 *
	 * @since 0.2
	 *
	 * @param string $label
	 * @param string|null $languageCode
	 * @param string|null $description
	 * @param string|null $entityType
	 * @param bool $fuzzySearch if false, only exact matches are returned, otherwise more relaxed search . Defaults to false.
	 *
	 * @return array of array( entity type, entity id )
	 */
	public function getEntityIdsForLabel( $label, $languageCode = null, $description = null, $entityType = null, $fuzzySearch = false ) {
		if (!$fuzzySearch) {
			return parent::getEntityIdsForLabel( $label, $languageCode, $description, $entityType, false );
		}

		$query = $this->solr->createSelect();
		$query->setFields( 'id' );
		$query->setQuery( 'label_' . $languageCode . ':' . $label );

		$response = array();
		$result = $this->solr->select($query);
		foreach ($result as $document) {
			$response[] = array(
				EntityFactory::singleton()->getEntityTypeFromPrefixedId($document['id']),
				EntityFactory::singleton()->getUnprefixedId($document['id'])
			);
		}

		return $response;
	}

}
