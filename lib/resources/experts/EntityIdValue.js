/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
wikibase.experts = wikibase.experts || {};

( function( wb, vv ) {
	'use strict';

	// temporarily define a hard coded prefix map until we get this from the server
	var WB_ENTITIES_PREFIXMAP = {
		'Q': 'item',
		'P': 'property'
	};

	var PARENT = vv.BifidExpert,
		editableExpert = wb.experts.EntityIdInput;

	/**
	 * Valueview expert for handling Wikibase Entity references.
	 *
	 * @since 0.4
	 *
	 * @constructor
	 * @extends jQuery.valueview.experts.StringValue
	 */
	wb.experts.EntityIdValue = vv.expert( 'entityidvalue', PARENT, {
		/**
		 * @see jQuery.valueview.BifidExpert._editableExpert
		 */
		_editableExpert: editableExpert,

		/**
		 * @see jQuery.valueview.BifidExpert._staticExpert
		 */
		_staticExpert: vv.experts.StaticDom,

		/**
		 * @see jQuery.valueview.BifidExpert._staticExpertOptions
		 */
		_staticExpertOptions: {
			domBuilder: function( currentRawValue, viewState ) {
				return viewState.getFormattedValue();
			},
			baseExpert: editableExpert
		},

		/**
		 * @see jQuery.valueview.Expert.valueCharacteristics
		 *
		 * TODO: remove this once the parsing is done via API
		 */
		valueCharacteristics: function() {
			return { prefixmap: WB_ENTITIES_PREFIXMAP };
		}
	} );

}( wikibase, jQuery.valueview ) );
