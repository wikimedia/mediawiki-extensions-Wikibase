<?php

/**
 * Class registration file for the Database component of Wikibase.
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
 * @ingroup WikibaseDatabase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
return call_user_func( function() {

	$classes = array(
		'Wikibase\Database\MWDB\ExtendedAbstraction',
		'Wikibase\Database\MWDB\ExtendedMySQLAbstraction',

		'Wikibase\Database\FieldDefinition',
		'Wikibase\Database\MediaWikiQueryInterface',
		'Wikibase\Database\MessageReporter',
		'Wikibase\Database\QueryInterface',
		'Wikibase\Database\QueryInterfaceException',
		'Wikibase\Database\ResultIterator',
		'Wikibase\Database\TableBuilder',
		'Wikibase\Database\TableCreationFailedException',
		'Wikibase\Database\TableDefinition',
	);

	$paths = array();

	foreach ( $classes as $class ) {
		$path = str_replace( '\\', '/', substr( $class, 9 ) ) . '.php';

		$paths[$class] = $path;
	}

	$paths['Wikibase\Repo\DBConnectionProvider'] = 'Database/DBConnectionProvider.php';
	$paths['Wikibase\Repo\LazyDBConnectionProvider'] = 'Database/LazyDBConnectionProvider.php';

	return $paths;

} );
