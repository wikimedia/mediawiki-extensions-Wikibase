/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, vv ) {
	'use strict';

	var PARENT = vv.BifidExpert,
		editableExpert = vv.experts.GlobeCoordinateInput;

	/**
	 * Valueview expert for handling coordinate values.
	 *
	 * @since 0.1
	 *
	 * @constructor
	 * @extends jQuery.valueview.experts.BifidExpert
	 */
	vv.experts.GlobeCoordinateValue = vv.expert( 'GlobeCoordinateValue', PARENT, {
		/**
		 * @see jQuery.valueview.BifidExpert._editableExpert
		 */
		_editableExpert: editableExpert,

		/**
		 * @see jQuery.valueview.BifidExpert._editableExpertOptions
		 */
		_editableExpertOptions: {},

		/**
		 * @see jQuery.valueview.BifidExpert._staticExpert
		 */
		_staticExpert: vv.experts.StaticDom,

		/**
		 * @see jQuery.valueview.BifidExpert._staticExpertOptions
		 */
		_staticExpertOptions: {
			/**
			 * @param {globeCoordinate.GlobeCoordinate|string|null} currentRawValue
			 * @param {jQuery.valueview.ViewState} viewState
			 * @param {jQuery.valueview.MessageProvider} messageProvider
			 */
			domBuilder: function( currentRawValue, viewState, messageProvider ) {
				return viewState.getFormattedValue();
			},
			baseExpert: editableExpert
		}
	} );

}( jQuery, jQuery.valueview ) );
