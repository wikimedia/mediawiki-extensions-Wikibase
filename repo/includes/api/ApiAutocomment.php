<?php

namespace Wikibase;

/**
 * ApiAutocomment interface. All Api modules that need autocomments
 * should either extend this directly or by subclassing. Note that
 * the generated content must still be properly delivered to
 * EditEntity::attemptedSave or any similar call.
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
 * @author John Erling Blad < jeblad@gmail.com >
 */
interface ApiAutocomment {

	/**
	 * Make a string for an autocomment, that can be replaced through system messages.
	 *
	 * The autocomment is the initial part of the total summary. It is used to
	 * explain the overall purpose with the change. If its later replaced by a
	 * system message then it should not use any user supplied text as arg.
	 *
	 * The method is not really part of a public interface but is used to enforce
	 * similarity in the code inside the Api modules.
	 *
	 * @since 0.2
	 *
	 * @param $params array with parameters from the call to the module
	 * @param $plural integer|string the number used for plural forms
	 * @return string that can be used as an autocomment
	 */
	public function getTextForComment( array $params, $plural = 'none' );

	/**
	 * Make a string for an autosummary, that can be replaced through system messages.
	 *
	 * The autosummary is the final part of the total summary. This call is used if there
	 * is no ordinary summary. If this call fails an autosummary from the entity itself will
	 * be used.
	 *
	 * The returned array has a count that can be used for plural forms in the messages,
	 * but exact interpretation is somewhat undefined.
	 *
	 * The language and direction is not passed on and the string should be wrapped
	 * in dir="auto". It is possible to create hints about the string, but it seems to
	 * create more problems than it solves.
	 *
	 * The method is not really part of a public interface but is used to enforce
	 * similarity in the code inside the Api modules.
	 *
	 * @since 0.2
	 *
	 * @param $params array with parameters from the call to the module
	 * @return array where the array( int, false|string ) is a count and a string that can be used as an autosummary
	 */
	public function getTextForSummary( array $params );

}
