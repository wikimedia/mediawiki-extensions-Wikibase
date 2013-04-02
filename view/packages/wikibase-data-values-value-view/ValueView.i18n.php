<?php

/**
 * Internationalization file for the "ValueView" extension.
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
 * @ingroup ValueView
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */

$messages = array();

/** English
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
$messages['en'] = array(
	'valueview-desc' => 'UI components for displaying and editing data values',

	// UnsupportedValue expert:
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => 'Handling of "$1" values is not yet supported.',
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'Handling of values for "$1" data type is not yet supported.',

	// EmptyValue expert:
	'valueview-expert-emptyvalue-empty' => 'empty'
);

/** Message documentation (Message documentation)
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
$messages['qqq'] = array(
	'valueview-desc' => '{{desc|name=ValueView|url=http://www.mediawiki.org/wiki/Extension:ValueView}}',

	// UnsupportedValue expert:
	'valueview-expert-unsupportedvalue-unsupporteddatavalue' => 'Error shown if a data value of a certain data value type (see [[d:Wikidata:Glossary]]) should be displayed or a form for creating one should be offered while this is not yet possible from a technical point of view (e.g. because a valueview widget expert handling data values of that type has not yet been implemented). $1 is the name of the data value type which lacks support.',
	'valueview-expert-unsupportedvalue-unsupporteddatatype' => 'Error shown if a data value for a certain data type (see [[d:Wikidata:Glossary]]) should be displayed or a form for creating one should be offered while this is not yet possible from a technical point of view (e.g. because a valueview widget expert handling data values for that data type has not yet been implemented). Parameter $1 is the name of the data type which lacks support',

	// EmptyValue expert:
	'valueview-expert-emptyvalue-empty' => 'Message expressing that there is currently no value set in a jQuery valueview.'
);
