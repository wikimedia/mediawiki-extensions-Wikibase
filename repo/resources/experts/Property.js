( function( wb, vv ) {
	'use strict';

var MODULE = wb.experts,
	PARENT = wb.experts.Entity;

/**
 * `valueview` `Expert` for specifying a reference to a Wikibase `Property`.
 * @see jQuery.valueview.expert
 * @see jQuery.valueview.Expert
 * @class wikibase.experts.Property
 * @extends wikibase.experts.Entity
 * @uses jQuery.wikibase.entityselector
 * @uses jQuery.valueview
 * @since 0.5
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
MODULE.Property = vv.expert( 'wikibaseproperty', PARENT, {
	/**
	 * @inheritdoc
	 */
	_init: function() {
		PARENT.prototype._initEntityExpert.call( this );
	},

	/**
	 * @inheritdoc
	 * @protected
	 */
	_initEntityselector: function( repoApiUrl ) {
		this.$input.entityselector( {
			url: repoApiUrl,
			type: 'property',
			selectOnAutocomplete: true
		} );
	}
} );

}( wikibase, jQuery.valueview ) );
