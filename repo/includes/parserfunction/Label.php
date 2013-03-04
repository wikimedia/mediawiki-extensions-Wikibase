<?php

namespace Wikibase\ParserFunction;
use Language;
use Wikibase\Entity;
use Wikibase\EntityId;
use Wikibase\EntityContent;
use Wikibase\EntityContentFactory;
use Wikibase\Utils;

/**
 * Parser function for requesting label information.
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
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeblad < jeblad@gmail.com >
 */
class Label {

	/**
	 * Parser function
	 *
	 * @since 0.5
	 *
	 * @param \Parser &$parser
	 *
	 * @return string
	 */
	public static function handle( &$parser ) {
			
		$langCodes = func_get_args();

		// Remove the parser and the id
		array_shift( $langCodes );
		$id = array_shift( $langCodes );

		$entityId = EntityId::newFromPrefixedId( $id );
		
		if ( !$entityId ) {
			return static::error( 'wikibase-parserfunction-label-unknown-entity', $id );
		}

		$entityContentFactory = EntityContentFactory::singleton();
		$entityContent = $entityContentFactory->getFromId( $entityId );

		if ( !( $entityContent instanceof EntityContent ) ) {
			return static::error( 'wikibase-parserfunction-label-unkown-content', $id );
		}

		$entity = $entityContent->getEntity();
		if ( is_null( $entity ) ) {
			return static::error( 'wikibase-parserfunction-label-unkown-serialization', $id );
		}
		
		if ( empty( $langCodes ) ) {
			global $wgLang;
			static $langStore = array();
			$langCode = $wgLang->getCode();
			$langSequence = array_merge( array( $langCode ), Language::getFallbacksFor( $langCode ) );
			list( $labelCode, $labelText, $labelLang ) =
				Utils::lookupMultilangText(
					$entity->getLabels( $langSequence ),
					$langSequence,
					array( $langCode, '', $wgLang )
				);
			return static::format( 'wikibase-parserfunction-label-found', $id, $langCode, $labelText);
		}
		else {
			foreach ( $langCodes as $langCode) {
				$labelText = $entity->getLabel( $langCode );
				if ( $labelText !== false ) {
					return static::format( 'wikibase-parserfunction-label-found', $id, $langCode, $labelText);
				}
			}
		}
		return static::error( 'wikibase-parserfunction-label-not-found', $id );
	}
	
	protected static function format( $msg, $id, $langCode, $labelText ) {
		$message = wfMessage(
			$msg,
			htmlspecialchars( $id ),
			htmlspecialchars( $langCode ),
			htmlspecialchars( $labelText )
		);
		$html = \Html::rawElement(
			'span',
			array(
				'class' => 'wb-item-label',
				'rel' => htmlspecialchars( $id ),
				'lang' => $langCode
			),
			$message->text()
		);
		return $html;
	}

	protected static function error( $msg, $id ) {
		$message = wfMessage(
			$msg,
			htmlspecialchars( $id )
		);
		$html = \Html::rawElement(
			'strong',
			array(
				'class' => 'error'
			),
			$message->text()
		);
		return $html;
	}

}
