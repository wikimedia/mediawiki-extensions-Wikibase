/**
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( mw, wb, $ ) {
	'use strict';

	/**
	 * Just a space in the user's language.
	 * @type string
	 */
	var SPACE = mw.msg( 'word-separator' );

	/**
	 * Whether page has rtl context.
	 * @type {boolean}
	 */
	var IS_RTL = null;

	$( document ).ready( function() {
		// have to wait for document to be loaded for this, otherwise 'rtl' might not yet be there!
		IS_RTL = $( 'body' ).hasClass( 'rtl' );
	} );

	/**
	 * UI related utilities required by 'Wikibase' extension.
	 * @type {Object}
	 */
	wb.utilities.ui = {};

	/**
	 * Creates a pretty link to an entity's page. If the label is not yet set, then the link will
	 * show the entity's ID and some explanatory text describing that the label hast not been set
	 * yet. Requires a Title object.
	 *
	 * @since 0.4
	 *
	 * @param {wb.datamodel.Entity} entity
	 * @param {Title} title
	 * @return {jQuery} An 'a' element
	 */
	wb.utilities.ui.buildLinkToEntityPage = function( entity, title ) {
		return $( '<a>' )
			.attr( 'href', title.getUrl() )
			.attr( 'title', title.getPrefixedText() )
			.append( wb.utilities.ui.buildPrettyEntityLabel( entity ) );
	};

	/**
	 * Creates a pretty label for an Entity. This means if the Entity doesn't actually have a label,
	 * some alternative information will be shown (the ID + some information that the label is not
	 * set).
	 *
	 * @since 0.4
	 *
	 * @param {wb.datamodel.Entity} entity
	 * @return {jQuery} Construct of one or many HTML elements
	 */
	wb.utilities.ui.buildPrettyEntityLabel = function( entity ) {
		var label = entity.getLabel(),
			text = wb.utilities.ui.buildPrettyEntityLabelText( entity ),
			$label = $( document.createTextNode( text ) );

		if( !label ) {
			if( text ) { // empty if entity without ID
				$label[0].nodeValue += SPACE;
			}
			var $undefinedInfo = $( '<span/>', {
				'class': 'wb-entity-undefinedinfo',
				'dir': IS_RTL ? 'rtl' : 'ltr',
				'text': mw.msg( 'parentheses', mw.msg( 'wikibase-label-empty' ) )
			} );
			$label = $label.add( $undefinedInfo );
		}

		return $label;
	};

	/**
	 * Creates a pretty label text for an Entity that either shows the actual label or the ID.
	 *
	 * @since 0.5
	 *
	 * @param {wb.datamodel.Entity} [entity]
	 * @return {string} Either the label, ID or empty string
	 */
	wb.utilities.ui.buildPrettyEntityLabelText = function( entity ) {
		return entity && ( entity.getLabel() || entity.getId() ) || '';
	};

	/**
	 * Builds a span containing text and some markup for nicely expressing that an Entity is not
	 * in the system even though it is expected to be.
	 *
	 * @since 0.4
	 *
	 * @param {string} entityId ID of the missing Entity
	 * @param {string|Function} entityType Can be a wb.datamodel.Entity constructor or the type of the Entity
	 *        as string.
	 * @return jQuery
	 */
	wb.utilities.ui.buildMissingEntityInfo = function( entityId, entityType ) {
		entityType = typeof entityType === 'string' ? entityType : entityType.TYPE;

		return $( '<span/>' ).text( entityId + SPACE ).append(
			$( '<span>', {
				'class': 'wb-entity-undefinedinfo',
				'dir': IS_RTL ? 'rtl' : 'ltr',
				'text': mw.msg( 'parentheses', mw.msg( 'wikibase-deletedentity-' + entityType ) )
			} )
		);
	};

	/**
	 * Creates a counter suited for displaying a number of a fixed quantity plus a number of a
	 * pending quantity whereas the quantity can be 0 or higher. If the pending quantity is 0, it
	 * will not be shown and only the fixed quantity will be displayed, otherwise it will be
	 * displayed as "fixedQuantity +pendingQuantity kindOfQuantity", e.g. "32 +2"
	 *
	 * @since 0.4
	 *
	 * @param {number} fixedQuantity
	 * @param {number} pendingQuantity
	 * @param {string} kindOfQuantityMessage Message string of a message expressing the nature of
	 *        the quantity, e.g. a message which would return 'items' for displaying something like
	 *        "3 +1 items". The message will receive as parameter $1 the total quantity (fixed +
	 *        pending).
	 * @param {string} pendingQuantityTooltipMessage Message string of a message which will be
	 *        displayed in the tooltip which will be appended to the number of pending quantity.
	 *        Parameter $1 will be the number of pending quantity, $2 will be the fixed quantity
	 *        and $3 the sum of both.
	 * @return {jQuery} The formatted counter output. Does not have a root node, collection of
	 *         multiple DOM elements.
	 */
	wb.utilities.ui.buildPendingCounter = function(
		fixedQuantity, pendingQuantity, kindOfQuantityMessage, pendingQuantityTooltipMessage
	) {
		var fqNumMsg = mw.language.convertNumber( fixedQuantity ),
			pqNumMsg = mw.language.convertNumber( pendingQuantity ),
			tqNumMsg = mw.language.convertNumber( fixedQuantity + pendingQuantity ),
			qTypeLabel = kindOfQuantityMessage ? mw.msg( kindOfQuantityMessage, tqNumMsg ) : '';

		var msg = !pendingQuantity || pendingQuantity === '0'
			? mw.msg( 'wikibase-ui-pendingquantitycounter-nonpending', qTypeLabel, fqNumMsg )
			: mw.msg( 'wikibase-ui-pendingquantitycounter-pending',
				qTypeLabel,
				fqNumMsg,
				'__3__' // can't insert html here since it would be escaped!
			);

		// replace __3__ with a span we can grab next
		var $msg = $( ( '<span>' + msg + '</span>' ).replace( /__3__/g, '<span/>' ) ),
			$msgSpan = $msg.children( 'span' );

		if( $msgSpan.length > 0 ) {
			$msgSpan.attr(
				'title', // the message displayed in the tooltip
				mw.msg( pendingQuantityTooltipMessage, pqNumMsg, fqNumMsg, tqNumMsg )
			);
			$msgSpan.text( // the '+1' part, displaying the pending quantity
				mw.msg( 'wikibase-ui-pendingquantitycounter-pending-pendingsubpart', pqNumMsg )
			);
			$msgSpan.tipsy( {
				'gravity': ( IS_RTL ? 'ne' : 'nw' )
			} );
		}

		$msg.addClass( 'wb-ui-pendingcounter' );
		return $msg;
	};

}( mediaWiki, wikibase, jQuery ) );
