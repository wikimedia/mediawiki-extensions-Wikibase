<?php

namespace Wikibase\ParserFunction;

use Wikibase\Entity;
use Wikibase\EntityId;
use Wikibase\EntityContent;
use Wikibase\EntityContentFactory;

/**
 * Parser function for requesting an item link with texts
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
class LinkFunction extends EntityFunction {

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
		global $wgLang;

		$langCodes = func_get_args();
		array_shift( $langCodes );
		$prefixedId = array_shift( $langCodes );

		try {
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

			$labels = $entity->getLabels();
			list( , $labelCode, $labelText ) = empty( $langCodes )
				? static::findTextByGlobalChain( $prefixedId, $labels )
				: static::findTextByLocalChain( $prefixedId, $labels, $langCodes );
			$labelLang = isset( $labelCode ) ? \Language::factory( $labelCode ) : $wgLang;

			$descriptions = $entity->getDescriptions();
			list( , $descriptionCode, $descriptionText ) = empty( $langCodes )
				? static::findTextByGlobalChain( $prefixedId, $descriptions )
				: static::findTextByLocalChain( $prefixedId, $descriptions, $langCodes );
			$descriptionLang = isset( $descriptionCode ) ? \Language::factory( $descriptionCode ) : $wgLang;

			$idHtml = \Html::element(
				'span',
				array( 'class' => 'wb-itemlink-id' ),
				wfMessage( 'wikibase-itemlink-id-wrapper', $prefixedId )->inContentLanguage()->escaped()
			);

			$labelHtml = \Html::element(
				'span',
				array( 'class' => 'wb-itemlink-label', 'lang' => $labelCode, 'dir' => $labelLang->getDir() ),
				htmlspecialchars( $labelText )
			);

			$html = \Html::rawElement(
				'span',
				array( 'class' => 'wb-itemlink' ),
				wfMessage( 'wikibase-itemlink' )->rawParams( $labelHtml, $idHtml )->inContentLanguage()->escaped()
			);

			$titleText = ( $labelText !== null )
				? $labelLang->getDirMark() . $labelText . $wgLang->getDirMark()
				: $entityContent->getTitle()->getPrefixedText();

			$customAttribs[ 'title' ] = ( $descriptionText !== null )
				? wfMessage(
					'wikibase-itemlink-title',
					$titleText,
					$descriptionLang->getDirMark() . $descriptionText . $wgLang->getDirMark()
				)->inContentLanguage()->text()
				: $titleText;

			return array(
				\Linker::link( $entityContent->getTitle(), $html, $customAttribs ),
				'noparse' => true,
				'isHTML' => true
			);
		}
		catch ( \MWException $ex ) {
			return $ex->getMessage();
		}
	}
}
