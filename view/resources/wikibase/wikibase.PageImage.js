/**
 * @licence GNU GPL v2+
 * @author Jonas Kress
 */

(function(wb, mw, $) {
	'use strict';

	/**
	 * Offers access to the page image
	 * @constructor
	 *
	 * @param {int} width
	 * @param {int} height
	 */
	var SELF = wb.PageImage = function PageImage(width, height) {
		if (width) {
			this._width = width;
		}
		if (height) {
			this._height = height;
		}
	};

	SELF.prototype._width = 200;
	SELF.prototype._height = 200;

	/**
	 * Returns the page image as DOM element
	 * @return {Object} jQuery.Promise Resolved after loading and cropping of image is done
	 *         returning a DOM element.
	 */
	SELF.prototype.getPageImage = function() {
		var deferred = $.Deferred();

		var self = this;

		self._getImageUrl().done( function( url ){
			self._loadImage( url ).done(
				function(image) {
					self._getSmartCrop(image).done(function(crop) {
						deferred.resolve(self._getMaskedImage(image, crop));
					});
				});
		} );

		return deferred.promise();
	};

	SELF.prototype._loadImage = function(url) {
		var deferred = $.Deferred();

		var image = new Image();
		image.onload = function() {
			deferred.resolve(image);
		};
		image.crossOrigin = 'https://upload.wikimedia.org/crossdomain.xml';
		image.src = url;

		return deferred.promise();
	}

	SELF.prototype._getSmartCrop = function(image) {
		var deferred = $.Deferred();

		SmartCrop.crop(image, {
			width : this._width,
			height : this._height,
			minScale : 1,
		}, function(result) {
			deferred.resolve(result.topCrop);
		});

		return deferred.promise();
	};

	SELF.prototype._getMaskedImage = function(image, crop) {

		var canvas = $('<canvas/>')[0], ctx = canvas.getContext('2d');

		canvas.width = this._width;
		canvas.height = this._height;
		ctx.drawImage(image, crop.x, crop.y, crop.width, crop.height, 0, 0,
				canvas.width, canvas.height);

		return canvas;
	};

	SELF.prototype._getImageUrl = function() {
		var deferred = $.Deferred();

		mw.loader.using('mediawiki.api', function() {
			(new mw.Api()).get({
				action : 'query',
				prop : 'pageimages',
				piprop: 'thumbnail',
				pithumbsize: '900',
				titles: mw.config.get('wgPageName')
			}).done(function(data) {
				var thumb = data.query.pages[Object.keys(data.query.pages)[0]].thumbnail;
				console.log(data);
				if( thumb ){
					deferred.resolve(thumb.source);
				}
			});
		});

		return deferred.promise();
	};

}(wikibase, mw, jQuery));