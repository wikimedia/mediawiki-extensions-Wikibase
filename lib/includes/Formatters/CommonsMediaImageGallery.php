<?php

namespace Wikibase\Lib\Formatters;

use File;
use MediaTransformOutput;
use PackedImageGallery;

/**
 * Custom image gallery for displaying commons media values in Wikibase entity views.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class CommonsMediaImageGallery extends PackedImageGallery {

	/**
	 * Get the transform parameters for a thumbnail.
	 *
	 * @param File|bool $img The file in question. May be false for invalid image
	 * @return int[]
	 */
	protected function getThumbParams( $img ) {
		return [
			'width' => 430,
			'height' => 180
		];
	}

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
