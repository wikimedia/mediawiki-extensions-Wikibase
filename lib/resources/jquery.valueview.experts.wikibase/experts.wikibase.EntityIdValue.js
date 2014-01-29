/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( mw, wb, vv ) {
	'use strict';

	// temporarily define a hard coded prefix map until we get this from the server
	var WB_ENTITIES_PREFIXMAP = {
		'Q': 'item',
		'P': 'property'
	};

	var PARENT = vv.BifidExpert,
		editableExpert = vv.experts.wikibase.EntityIdInput;

	/**
	 * Valueview expert for handling Wikibase Entity references.
	 *
	 * @since 0.4
	 *
	 * @constructor
	 * @extends jQuery.valueview.experts.StringValue
	 */
	vv.experts.wikibase.EntityIdValue = vv.expert( 'entityidvalue', PARENT, {
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

	// Make the above expert available for wb.EntityId data value handling.
	// TODO: Move this in some kind of higher initialization file once we have more like this:
	mw.ext.valueView.expertProvider.registerExpert(
		wb.EntityId,
		vv.experts.wikibase.EntityIdValue
	);

}( mediaWiki, wikibase, jQuery.valueview ) );
