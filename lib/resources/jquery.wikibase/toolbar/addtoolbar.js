/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb, $ ) {
	'use strict';

	var PARENT = $.wikibase.toolbarbase;

	/**
	 * "Add" toolbar widget
	 * @since 0.4
	 * @extends jQuery.wikibase.toolbarbase
	 *
	 * This widget offers an "add" button which will allow interaction with a given widget.
	 * The widget the toolbar shall interact with has to have implemented certain methods listed in
	 * the _requiredMethods attribute.
	 *
	 * @option interactionWidgetName {string} Name of the widget the toolbar shall interact with.
	 *         (That widget needs to be initialized on the same DOM node this toolbar is initialized
	 *         on.) If the interactionWidgetName option is omitted, the toolbar will be initialized
	 *         as well but proper interaction is not ensured (see _create() method).
	 *         When omitting the interactionWidgetName option, the "action" option should be set.
	 *         Default value: null (no interaction widget)
	 *
	 * @option customAction {function} Custom action the add button shall trigger. The action
	 *         will be triggered only when no interaction widget via the interactionWidgetName
	 *         option is set.
	 *         The function receives the following parameters:
	 *         (1) {jQuery.Event} "Add" button's action event
	 *         (2) {jQuery} The action toolbar's DOM node
	 *         Default value: null (no custom action)
	 *
	 * @option addButtonLabel {string} The add button's label
	 *         Default value: mw.msg( 'wikibase-add' )
	 */
	$.widget( 'wikibase.addtoolbar', PARENT, {
		widgetBaseClass: 'wb-addtoolbar',

		/**
		 * Options
		 * @type {Object}
		 */
		options: {
			interactionWidgetName: null,
			customAction: null,
			addButtonLabel: mw.msg( 'wikibase-add' )
		},

		/**
		 * Names of methods that are required in the interaction widget to ensure proper toolbar
		 * interaction.
		 * @type {string[]}
		 */
		_requiredMethods: [
			'enterNewItem'
		],

		/**
		 * The widget the toolbar interacts with.
		 * @type {Object}
		 */
		_interactionWidget: null,

		/**
		 * @see jQuery.Widget._create
		 */
		_create: function() {
			var self = this;

			PARENT.prototype._create.call( this );

			var $toolbar = mw.template( 'wikibase-toolbar', '', '' ).toolbar( {
				renderItemSeparators: true
			} );
			this.toolbar = $toolbar.data( 'toolbar' );
			this.toolbar.$innerGroup = mw.template( 'wikibase-toolbar', '', '' ).toolbar();
			this.toolbar.$btnAdd = mw.template(
				'wikibase-toolbarbutton',
				this.options.addButtonLabel,
				'javascript:void(0);'
			).toolbarbutton();
			this.toolbar.$btnAdd.addClass( this.widgetBaseClass + '-addbutton' );
			this.toolbar.$innerGroup.data( 'toolbar' ).addElement( this.toolbar.$btnAdd );
			this.toolbar.addElement( this.toolbar.$innerGroup );

			if ( this.options.interactionWidgetName ) {
				this._interactionWidget = this.element.data( this.options.interactionWidgetName );

				var missingMethods = this.checkRequiredMethods();
				if ( missingMethods.length > 0 ) {
					var m = missingMethods.join( ', ' );
					throw new Error( 'jquery.wikibase.addtoolbar: Missing required method(s) ' + m );
				}
			}

			$( this.toolbar.$btnAdd ).on( 'toolbarbuttonaction', function( event ) {
				if ( self._interactionWidget ) {
					self._interactionWidget.enterNewItem();
				} else if ( self.options.customAction ) {
					self.options.customAction( event, self.element );
				}
			} );

			$toolbar.appendTo(
				$( '<div/>' ).addClass( 'wb-editsection' ).appendTo( this.$toolbarParent )
			);
		},

		/**
		 * Checks whether all methods required in the interaction widget are defined and will return
		 * the names of any missing methods.
		 * @since 0.4
		 *
		 * @return {string[]}
		 */
		checkRequiredMethods: function() {
			var self = this,
				missingMethods = [];
			$.each( this._requiredMethods, function( i, methodName ) {
				if ( !$.isFunction( self._interactionWidget[methodName] ) ) {
					missingMethods.push( methodName );
				}
			} );
			return missingMethods;
		}

	} );

}( mediaWiki, wikibase, jQuery ) );
