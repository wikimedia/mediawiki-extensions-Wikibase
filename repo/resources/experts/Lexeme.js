( function( wb, vv ) {
	'use strict';

	var MODULE = wb.experts,
		PARENT = wb.experts.Entity;

	/**
	 * `valueview` `Expert` for specifying a reference to a Wikibase `Lexeme`.
	 * @see jQuery.valueview.expert
	 * @see jQuery.valueview.Expert
	 * @class wikibase.experts.Lexeme
	 * @extends wikibase.experts.Entity
	 * @uses jQuery.valueview
	 * @license GPL-2.0+
	 */
	var SELF = MODULE.Lexeme = vv.expert( 'wikibaselexeme', PARENT, {
		/**
		 * @inheritdoc
		 */
		_init: function() {
			PARENT.prototype._initEntityExpert.call( this );
		}
	} );

	/**
	 * @inheritdoc
	 */
	SELF.TYPE = 'lexeme';

}( wikibase, jQuery.valueview ) );
