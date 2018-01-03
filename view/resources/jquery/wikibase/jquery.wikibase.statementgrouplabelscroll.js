/**
 * @license GPL-2.0+
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 * @author Thiemo Kreuz
 */
( function ( $ ) {
	'use strict';

	var WIDGET_NAME = 'statementgrouplabelscroll';

	/**
	 * For keeping track of currently active statementgrouplabelscroll widgets which need updates on
	 * certain browser window events.
	 *
	 * NOTE: In this performance critical case this makes more sense than jQuery's widget selector.
	 *
	 * @type {jQuery.wikibase.statementgrouplabelscroll[]}
	 */
	var activeInstances = [];

	function updateActiveInstances() {
		for ( var i in activeInstances ) {
			activeInstances[ i ].update();
		}
	}

	function registerWidgetInstance( instance ) {
		if ( activeInstances.length === 0 ) {
			$( window ).on(
				'scroll resize'.replace( /(\w+)/g, '$1.' + WIDGET_NAME ),
				updateActiveInstances
			);
		}
		activeInstances.push( instance );
	}

	function unregisterWidgetInstance( instance ) {
		var index = $.inArray( instance );
		if ( index ) {
			activeInstances.splice( index, 1 );
		}
		if ( activeInstances.length === 0 ) {
			$( window ).off( '.' + WIDGET_NAME );
		}
	}

	function elementPartlyVerticallyInViewport( elem ) {
		var top = $( elem ).offset().top;
		return (
			top < ( window.pageYOffset + window.innerHeight )
			&& ( top + elem.offsetHeight ) > window.pageYOffset
		);
	}

	function elementFullyVerticallyInViewport( elem ) {
		var top = $( elem ).offset().top;
		return (
			top >= window.pageYOffset
			&& ( top + elem.offsetHeight ) <= ( window.pageYOffset + window.innerHeight )
		);
	}

	/**
	 * Checks an element for Main Snak elements and returns the first one visible in the browser's
	 * viewport.
	 *
	 * @param {jQuery} $searchRange
	 * @return {null|jQuery}
	 */
	function findFirstVisibleMainSnakElement( $searchRange ) {
		var result = null;

		// Caring about the visibility of ".wikibase-snakview-value-container" is better than about
		// ".wikibase-statementview" or ".wikibase-statementview-mainsnak" since the label will
		// align with the .wikibase-snakview-value-container.
		var $mainSnaks = $searchRange.find(
			'.wikibase-statementview-mainsnak .wikibase-snakview-value-container'
		);

		if ( $mainSnaks.length < 2 ) {
			return null;
		}

		$mainSnaks.each( function ( i, mainSnakNode ) {
			// Take first Main Snak value in viewport. If value is not fully visible in viewport,
			// check whether the next one is fully visible, if so, take that one.
			if ( elementPartlyVerticallyInViewport( mainSnakNode ) ) {
				result = mainSnakNode;

				if ( !elementFullyVerticallyInViewport( mainSnakNode ) ) {
					// Current element would be ok, but maybe the next one is even better
					var nextMainSnakNode = $mainSnaks.get( i + 1 );
					if ( nextMainSnakNode && elementFullyVerticallyInViewport( nextMainSnakNode ) ) {
						result = nextMainSnakNode;
					}
				}
			}

			// Stop iterating as soon as we have a result
			return !result;
		} );

		if ( result ) {
			// Don't forget to get the actual Snak node rather than the value container.
			result = $( result ).closest( '.wikibase-statementview-mainsnak' );
		}
		return result;
	}

	/**
	 * Checks an Claim Group's element for Main Snak elements and returns all that are visible in
	 * the browser's viewport.
	 * This is an optimized version of "findFirstVisibleMainSnakElement" in case Claim groups
	 * are expected within the DOM that should be searched for Main Snaks.
	 *
	 * @param {jQuery} $searchRange
	 * @return {jQuery}
	 */
	function findFirstVisibleMainSnakElementsWithinStatementlistview( $searchRange ) {
		var $statementGroups = $searchRange.find( '.wikibase-statementlistview' ),
			$visibleStatementGroups = $();

		// TODO: Optimize! E.g.:
		//  (1) don't walk them top to bottom, instead, take the one in the middle, check whether
		//      it is within/above/below viewport and exclude following/preceding ones which are
		//      obviously not within the viewport.
		//  (2) remember last visible node, start checking there and depending on scroll movement
		//      (up/down) on its neighbouring nodes.
		$statementGroups.each( function ( i, statementGroupNode ) {
			if ( elementPartlyVerticallyInViewport( statementGroupNode ) ) {
				var $mainSnakElement = findFirstVisibleMainSnakElement( $( statementGroupNode ) );
				$visibleStatementGroups = $visibleStatementGroups.add( $mainSnakElement );
			}
		} );

		return $visibleStatementGroups;
	}

	/**
	 * Takes an element and positions it to be vertically at the same position as another given
	 * element. Animates the element to move towards that position.
	 *
	 * @param {jQuery} $element
	 * @param {jQuery} $target
	 * @param {jQuery} $within
	 */
	function positionElementInOneLineWithAnother( $element, $target, $within ) {
		var side = $( 'html' ).prop( 'dir' ) === 'ltr' ? 'left' : 'right';
		$element
		.position( {
			my: side + ' top',
			at: side + ' top',
			of: $target,
			within: $within
		} );
	}

	/**
	 * Widget which will reposition labels of `Statement` groups while scrolling through the page.
	 * This ensures that the labels are always displayed on the same line with the first Main Snak
	 * visible within the viewport. When the label gets moved, the movement is animated for a smooth
	 * transition.
	 *
	 * TODO: Consider the rare case where window.scrollTo() is used. In that case we should move all
	 *  labels below the top of the new viewport position to the first `Statement` and all labels
	 *  above the viewport position to the last `Statement` in their group.
	 *
	 * @class jQuery.wikibase.statementgrouplabelscroll
	 * @extends jQuery.Widget
	 */
	$.widget( 'wikibase.' + WIDGET_NAME, {
		/**
		 * @see jQuery.Widget._create
		 */
		_create: function () {
			registerWidgetInstance( this );

			// Assume that all labels are in the proper place if no scrolling has happened yet.
			if ( window.pageYOffset ) {
				this.update();
			}
		},

		/**
		 * @see jQuery.Widget.destroy
		 */
		destroy: function () {
			unregisterWidgetInstance( this );
		},

		/**
		 * Will update the position of the statementgroup labels the widget is controlling.
		 */
		update: function () {
			var $visibleStatementviews
				= findFirstVisibleMainSnakElementsWithinStatementlistview( this.element )
					.closest( '.wikibase-statementview' );

			for ( var i = 0; i < $visibleStatementviews.length; i++ ) {
				var $visibleClaim = $visibleStatementviews.eq( i ),
					$statementGroup = $visibleClaim.closest( '.wikibase-statementgroupview' ),
					$statementGroupLabel = $statementGroup.find(
						'.wikibase-statementgroupview-property-label'
					);

				if ( $statementGroupLabel.length !== 1 ) {
					continue;
				}

				positionElementInOneLineWithAnother(
					$statementGroupLabel,
					$visibleClaim,
					$statementGroup
				);
			}
		}
	} );

	/**
	 * Returns an array with the active instances of the widget. A widget instance is considered
	 * active after its first initialization and inactive after its "destroy" function got called.
	 *
	 * @return {jQuery.wikibase.statementgrouplabelscroll[]}
	 */
	$.wikibase[ WIDGET_NAME ].activeInstances = function () {
		return activeInstances.slice();
	};

}( jQuery ) );
