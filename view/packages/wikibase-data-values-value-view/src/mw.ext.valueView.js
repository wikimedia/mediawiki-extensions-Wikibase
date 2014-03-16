/**
 * Entrypoint for MediaWiki "ValueView" extension JavaScript code.
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, dv, vv ) {
	'use strict';

	mw.ext = mw.ext || {};

	var expertStore = new vv.ExpertStore( vv.experts.UnsupportedValue );

	expertStore.registerDataValueExpert(
		vv.experts.StringValue,
		dv.StringValue.TYPE
	);

	expertStore.registerDataValueExpert(
		vv.experts.GlobeCoordinateInput,
		dv.GlobeCoordinateValue.TYPE
	);

	expertStore.registerDataValueExpert(
		vv.experts.StringValue,
		dv.QuantityValue.TYPE
	);

	expertStore.registerDataValueExpert(
		vv.experts.TimeInput,
		dv.TimeValue.TYPE
	);

	/**
	 * Object representing the MediaWiki "ValueView" extension.
	 * @since 0.1
	 */
	mw.ext.valueView = new ( function MwExtValueView() {
		/**
		 * Expert store containing all jQuery.valueview experts available in MediaWiki context.
		 * @since 0.1
		 *
		 * @type {jQuery.valueview.ExpertStore}
		 */
		this.expertStore = expertStore;
	} )();

	vv.prototype.options.expertStore = expertStore;

}( mediaWiki, dataValues, jQuery.valueview ) );
