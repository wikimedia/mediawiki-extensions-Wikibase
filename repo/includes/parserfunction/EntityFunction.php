<?php

namespace Wikibase\ParserFunction;
use Language;
use Wikibase\Entity;
use Wikibase\EntityId;
use Wikibase\EntityContent;
use Wikibase\EntityContentFactory;
use Wikibase\Utils;

/**
 * Parser function for requesting information from the entity
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
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Jeblad < jeblad@gmail.com >
 */
abstract class EntityFunction {

	/**
	 * Find entity
	 *
	 * @since 0.5
	 *
	 * @param string $prefixedId
	 *
	 * @trows MWException
	 *
	 * @return string
	 */
	protected static function findEntity( $prefixedId ) {
		$entityId = EntityId::newFromPrefixedId( $prefixedId );

		if ( !$entityId ) {
			static::dieUsage( 'wikibase-parserfunction-unknown-entity', $prefixedId );
		}

		$entityContentFactory = EntityContentFactory::singleton();
		$entityContent = $entityContentFactory->getFromId( $entityId );
		
		if ( !( $entityContent instanceof EntityContent ) ) {
			static::dieUsage( 'wikibase-parserfunction-unkown-content', $prefixedId );
		}
		
		$entity = $entityContent->getEntity();
		if ( is_null( $entity ) ) {
			static::dieUsage( 'wikibase-parserfunction-unkown-serialization', $prefixedId );
		}
		
		return $entity;
	}

	/**
	 * Find text by using global fallback chain
	 *
	 * @since 0.5
	 *
	 * @param string $prefixedId
	 * @param array|null $textList
	 *
	 * @return string
	 */
	protected static function findTextByGlobalChain( $prefixedId, array $textList = null ) {
		global $wgLang;

		if ( !isset( $textList ) ) {
			$textList = array();
		}

		static $langStore = array();
		$langCode = $wgLang->getCode();
		$languages = array_merge( array( $langCode ), Language::getFallbacksFor( $langCode ) );
		$textList = array_intersect_key( $textList, array_flip( $languages ) );

		list( $newCode, $newText, $newLang ) = Utils::lookupMultilangText(
			$textList,
			$languages,
			array( $langCode, null, $wgLang )
		);

		if ( is_string( $newText ) ) {
			return array( $prefixedId, $newCode, $newText );
		}

		return array( $prefixedId );
	}

	/**
	 * Find text by using a locally supplied fallback chain
	 *
	 * @since 0.5
	 *
	 * @param string $prefixedId
	 * @param array|null $textList
	 * @param array|null $langCodes
	 *
	 * @return string
	 */
	protected static function findTextByLocalChain( $prefixedId, array $textList = null, array $langCodes = null ) {
		if ( !isset( $textList ) ) {
			$textList = array();
		}

		if ( !isset( $langCodes ) ) {
			$langCodes = array();
		}

		foreach ( $langCodes as $langCode) {
			if ( isset( $textList[$langCode] ) && $textList[$langCode] !== false ) {
				return array( $prefixedId, $langCode, $textList[$langCode] );
			}
		}

		return array( $prefixedId );
	}
	

	/**
	 * Format a result string
	 *
	 * @since 0.5
	 *
	 * @param string $key
	 * @param string $id
	 * @param string $langCode
	 * @param string $text
	 *
	 * @return string
	 */
	protected static function makeHtml( $key, array $args ) {
		list( $id, $langCode, $text ) = $args;
		if ( !isset( $langCode ) ) {
			$langCode = 'int';
		}
		if ( !isset( $langCode ) ) {
			$text = $id;
		}
		$message = wfMessage(
			$key,
			htmlspecialchars( $id ),
			htmlspecialchars( $langCode ),
			htmlspecialchars( $text )
		);
		$html = \Html::rawElement(
			'span',
			array(
				'rel' => htmlspecialchars( $id ),
				'lang' => $langCode
			),
			$message->text()
		);
		return $html;
	}

	/**
	 * Format an error message and die
	 *
	 * @since 0.5
	 *
	 * @param unknown_type $key
	 * @param unknown_type $id
	 *
	 * @trows MWException
	 *
	 * @return string
	 */
	protected static function dieUsage( $msg, $id ) {
		$message = wfMessage(
			$key,
			htmlspecialchars( $id )
		);
		$html = \Html::rawElement(
			'strong',
			array(
				'class' => 'error'
			),
			$message->text()
		);
		throw new MWException( $html );
	}

}
