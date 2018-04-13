<?php

namespace Wikibase\Lib\Formatters;

use PackedImageGallery;

/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *s
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
 * @file
 */

/**
 * Custom image gallery for displaying commons media values in Wikibase entity views.
 */
class CommonsMediaImageGallery extends PackedImageGallery {

	/**
	 * Length to truncate filename to in caption when using "showfilename" (if int).
	 * A value of 'true' will truncate the filename to one line using CSS, while
	 * 'false' will disable truncating.
	 *
	 * @return int|bool
	 */
	protected function getCaptionLength() {
		// Don't truncate the caption at all.
		return false;
	}

	/**
	 * Allows overwriting the computed width of the gallerybox <li> with a string,
	 * like '100%'.
	 *
	 * Generally is the width of the image, plus padding on image
	 * plus padding on gallerybox.
	 *
	 * @note Important: parameter will be false if no thumb used.
	 * @param MediaTransformOutput|bool $thumb MediaTransformObject object or false.
	 * @return bool|string Ignored if false.
	 */
	protected function getGBWidthOverwrite( $thumb ) {
		return 'auto';
	}

}
