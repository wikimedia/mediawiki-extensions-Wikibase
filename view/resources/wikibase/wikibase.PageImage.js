var SmartCrop = SmartCrop || {};
var mw = mw || {};

/**
 * @licence GNU GPL v2+
 * @author Jonas Kress
 */

(function( wb, mw, $, SmartCrop ) {
	'use strict';

	/**
	 * Offers access to the page image
	 *
	 * Uses pageimages and pageprops Api endpoints to retrieve the image URL
	 *
	 * @constructor
	 *
	 * @param {int} width
	 * @param {int} height
	 */
	var SELF = wb.PageImage = function PageImage( width, height ) {
		if ( width ) {
			this._width = width;
		}
		if ( height ) {
			this._height = height;
		}
	};

	SELF.prototype._width = 200;
	SELF.prototype._height = 200;

	SELF.prototype._thumbnailSize = 900;
	SELF.prototype._minScale = 1;
	SELF.prototype._crossOriginXmlUrl = 'https://upload.wikimedia.org/crossdomain.xml';

	/**
	 * Returns the page image as DOM element
	 * @return {Object} jQuery.Promise Resolved after loading and cropping of image is done
	 *         returning a DOM element.
	 */
	SELF.prototype.getPageImage = function() {
		var deferred = $.Deferred(),
			self = this;

		self._getImageUrl().done( function( url, referenceUrl ){
			self._loadImage( url ).done(
				function( image ) {
					self._getSmartCrop( image ).done(function( crop ) {
						deferred.resolve(self._getMaskedImage( image, crop, referenceUrl ));
					});
				});
		} );

		return deferred.promise();
	};

	/**
	 * @private
	 **/
	SELF.prototype._loadImage = function( url ) {
		var deferred = $.Deferred();

		var image = new Image();
		image.onload = function() {
			deferred.resolve( image );
		};
		image.crossOrigin = this._crossOriginXmlUrl;
		image.src = url;

		return deferred.promise();
	};

	/**
	 * @private
	 **/
	SELF.prototype._getSmartCrop = function( image ) {
		var deferred = $.Deferred();

		SmartCrop.crop( image, {
			width : this._width,
			height : this._height,
			minScale: this._minScale,
		}, function( result ) {
			deferred.resolve( result.topCrop );
		});

		return deferred.promise();
	};

	/**
	 * @private
	 **/
	SELF.prototype._getMaskedImage = function( image, crop, ref ) {

		var canvas = $('<canvas/>')[0], ctx = canvas.getContext('2d');

		if( ref ){
			$( canvas ).data( 'ref', ref );
		}

		canvas.width = this._width;
		canvas.height = this._height;
		ctx.drawImage( image, crop.x, crop.y, crop.width, crop.height, 0, 0,
				canvas.width, canvas.height );

		return canvas;
	};

	/**
	 * @private
	 **/
	SELF.prototype._getImageUrl = function() {
		var deferred = $.Deferred(),
			self = this;

		mw.loader.using( 'mediawiki.api', function() {
			( new mw.Api()).get( {
				action : 'query',
				prop : 'pageimages|pageprops',
				piprop: 'thumbnail',
				pithumbsize: self._thumbnailSize,
				titles: mw.config.get( 'wgPageName' )
			} ).done( function( data ) {
				var page = data.query.pages[Object.keys( data.query.pages )[0]];
				if( page.thumbnail ){
					deferred.resolve( page.thumbnail.source,
							page.pageprops['page_image'] );// jshint ignore:line
				}

			});
		});

		return deferred.promise();
	};

}(wikibase, mw, jQuery, SmartCrop));