jQuery.util = jQuery.util || {};

/**
 * Utility function retrieving the width of the browser's scrollbar.
 *
 * @member jQuery.util
 * @method getscrollbarwidth
 * @license GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @return {number} Scrollbar width in pixel.
 */
jQuery.util.getscrollbarwidth = ( function () {
	'use strict';

	var scrollbarWidth;

	return function() {
		if ( scrollbarWidth ) {
			return scrollbarWidth;
		}

		var $inner = $( '<p/>', { style: 'width:100px;height:100px' } ),
			$outer = $( '<div/>', {
				style: 'position:absolute;top:-1000px;left:-1000px;visibility:hidden;'
					+ 'width:50px;height:50px;overflow:hidden;'
			} ).append( $inner ).appendTo( $( 'body' ) ),
			widthWithoutScrollbar = $outer.get( 0 ).clientWidth,
			widthWithScrollbar;

		$outer.css( 'overflow', 'scroll' );

		widthWithScrollbar = $outer.get( 0 ).clientWidth;

		$outer.remove();

		scrollbarWidth = widthWithoutScrollbar - widthWithScrollbar;

		return scrollbarWidth;
	};

}() );
