<?php

namespace Wikibase;
use ApiResult, MWException;

/**
 * API serializer for entities.
 *
 * TODO: support entity type specific stuff
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
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 */
class EntitySerializer extends ApiSerializerObject {

	/**
	 * @see ApiSerializerObject::$options
	 *
	 * @since 0.2
	 *
	 * @var EntitySerializationOptions|null
	 */
	protected $options;

	/**
	 * @see ApiSerializer::getSerialized
	 *
	 * @since 0.2
	 *
	 * @param mixed $entity
	 *
	 * @return array
	 * @throws MWException
	 */
	public function getSerialized( $entity ) {
		if ( !( $entity instanceof Entity ) ) {
			throw new MWException( 'EntitySerializer can only serialize Entity objects' );
		}

		$serialization = array();

		$serialization['id'] = $entity->getPrefixedId();
		$serialization['type'] = $entity->getType();

		foreach ( $this->options->getProps() as $key ) {
			switch ( $key ) {
				case 'aliases':
					$serialization['aliases'] = $this->getAliasesSerialization( $entity );
					break;
				case 'sitelinks':
					// TODO
					//$this->addSiteLinksToResult( $entity->getSiteLinks(), $entityPath, 'sitelinks', 'sitelink', $siteLinkOptions );
					break;
				case 'descriptions':
					$serialization['descriptions'] = $this->getDescriptionsSerialization( $entity );
					break;
				case 'labels':
					$serialization['labels'] = $this->getLabelsSerialization( $entity );
					break;
			}
		}

		return $serialization;
	}

	/**
	 * Returns the aliases in an array ready for serialization.
	 *
	 * @since 0.2
	 *
	 * @param Entity $entity
	 *
	 * @return array
	 */
	protected function getAliasesSerialization( Entity $entity ) {
		$value = array();

		$aliases = $entity->getAllAliases( $this->options->getLanguages() );

		if ( $this->options->shouldUseKeys() ) {
			foreach ( $aliases as $languageCode => $alarr ) {
				$arr = array();
				foreach ( $alarr as $alias ) {
					$arr[] = array(
						'language' => $languageCode,
						'value' => $alias,
					);
				}
				$value[$languageCode] = $arr;
			}
		}
		else {
			foreach ( $aliases as $languageCode => $alarr ) {
				foreach ( $alarr as $alias ) {
					$value[] = array(
						'language' => $languageCode,
						'value' => $alias,
					);
				}
			}
		}

		if ( $value !== array() ) {
			if ( !$this->options->shouldUseKeys() ) {
				$this->getResult()->setIndexedTagName( $value, 'alias' );
			}
		}

		return $value;
	}

	/**
	 * Returns the descriptions in an array ready for serialization.
	 *
	 * @since 0.2
	 *
	 * @param Entity $entity
	 *
	 * @return array
	 */
	protected function getDescriptionsSerialization( Entity $entity ) {
		$value = array();
		$idx = 0;

		$descriptions = $entity->getDescriptions( $this->options->getLanguages() );

		foreach ( $descriptions as $languageCode => $description ) {
			if ( $description === '' ) {
				$value[$this->options->shouldUseKeys() ? $languageCode : $idx++] = array(
					'language' => $languageCode,
					'removed' => '',
				);
			}
			else {
				$value[$this->options->shouldUseKeys() ? $languageCode : $idx++] = array(
					'language' => $languageCode,
					'value' => $description,
				);
			}
		}

		if ( $value !== array() ) {
			if ( !$this->options->shouldUseKeys() ) {
				$this->getResult()->setIndexedTagName( $value, 'description' );
			}
		}

		return $value;
	}

	/**
	 * Returns the labels in an array ready for serialization.
	 *
	 * @since 0.2
	 *
	 * @param Entity $entity
	 *
	 * @return array
	 */
	protected function getLabelsSerialization( Entity $entity ) {
		$value = array();
		$idx = 0;

		$labels = $entity->getLabels( $this->options->getLanguages() );

		foreach ( $labels as $languageCode => $label ) {
			if ( $label === '' ) {
				$value[$this->options->shouldUseKeys() ? $languageCode : $idx++] = array(
					'language' => $languageCode,
					'removed' => '',
				);
			}
			else {
				$value[$this->options->shouldUseKeys() ? $languageCode : $idx++] = array(
					'language' => $languageCode,
					'value' => $label,
				);
			}
		}

		if ( $value !== array() ) {
			if ( !$this->options->shouldUseKeys() ) {
				$this->getResult()->setIndexedTagName( $value, 'label' );
			}
		}

		return $value;
	}

}
