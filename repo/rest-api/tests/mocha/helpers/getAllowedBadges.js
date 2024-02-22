'use strict';

const { utils, action } = require( 'api-testing' );

let allowedBadges;
async function getAllowedBadges() {
	allowedBadges = allowedBadges || [
		( await newBadgeItem( 'badge1-' ) ).entity.id,
		( await newBadgeItem( 'badge2-' ) ).entity.id
	];

	return allowedBadges;
}

function newBadgeItem( labelPrefix ) {
	return action.getAnon().action( 'wbeditentity', {
		token: '+\\',
		new: 'item',
		data: JSON.stringify( {
			labels: [ { language: 'en', value: utils.title( labelPrefix ) } ]
		} )
	}, 'POST' );
}

module.exports = { getAllowedBadges };
