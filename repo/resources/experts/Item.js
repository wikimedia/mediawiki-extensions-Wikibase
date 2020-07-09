module.exports = ( function ( wb, vv ) {
	'use strict';

	var MODULE = wb.experts,
		PARENT = wb.experts.Entity;

	/**
	 * `valueview` `Expert` for specifying a reference to a Wikibase `Item`.
	 *
	 * @see jQuery.valueview.expert
	 * @see jQuery.valueview.Expert
	 * @class wikibase.experts.Item
	 * @extends wikibase.experts.Entity
	 * @uses jQuery.valueview
	 * @license GPL-2.0-or-later
	 * @author H. Snater < mediawiki@snater.com >
	 */
	var SELF = MODULE.Item = vv.expert( 'wikibaseitem', PARENT, {
		/**
		 * @inheritdoc
		 */
		_init: function () {
			PARENT.prototype._initEntityExpert.call( this );
		}
	} );

	/**
	 * @inheritdoc
	 */
	SELF.TYPE = 'item';

	return SELF;

}( wikibase, $.valueview ) );
