<?php

/**
 * Entry point for the Query component of Wikibase.
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
 * @ingroup WikibaseQueryEngine
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

define( 'WIKIBASE_QUERYENGINE_VERSION', '0.1 alpha' );

spl_autoload_register( function ( $className ) {
	$className = ltrim( $className, '\\' );
	$fileName = '';
	$namespace = '';

	if ( $lastNsPos = strripos( $className, '\\') ) {
		$namespace = substr( $className, 0, $lastNsPos );
		$className = substr( $className, $lastNsPos + 1 );
		$fileName  = str_replace( '\\', '/', $namespace ) . '/';
	}

	$fileName .= str_replace( '_', '/', $className ) . '.php';

	$namespaceSegments = explode( '\\', $namespace );

	$inQueryEngineNamespace = count( $namespaceSegments ) > 1
		&& $namespaceSegments[0] === 'Wikibase'
		&& $namespaceSegments[1] === 'QueryEngine';

	if ( $inQueryEngineNamespace ) {
		$inTestNamespace = count( $namespaceSegments ) > 2 && $namespaceSegments[2] === 'Tests';

		if ( !$inTestNamespace ) {
			$pathParts = explode( '/', $fileName );
			array_shift( $pathParts );
			array_shift( $pathParts );
			$fileName = implode( '/', $pathParts );

			require_once __DIR__ . '/includes/' . $fileName;
		}
	}
} );

// @codeCoverageIgnoreStart
if ( defined( 'MEDIAWIKI' ) ) {
	call_user_func( function() {
		require_once __DIR__ . '/QueryEngine.mw.php';
	} );
}
// @codeCoverageIgnoreEnd