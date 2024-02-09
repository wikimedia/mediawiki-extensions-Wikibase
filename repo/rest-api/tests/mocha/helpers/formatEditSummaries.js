'use strict';

module.exports = {
	formatTermEditSummary( module, action, languageCode, termText, userComment ) {
		const commentArgs = `1|${languageCode}`;
		const autoSummary = `/* ${module}-${action}:${commentArgs} */ ${termText}`;

		return userComment ? `${autoSummary}, ${userComment}` : autoSummary;
	},

	formatStatementEditSummary( module, action, property, value, userComment ) {
		const commentArgs = module === 'wbsetclaim' ? '1||1' : '1|';
		const autoSummary = `/* ${module}-${action}:${commentArgs} */ [[Property:${property}]]: ${value}`;

		return userComment ? `${autoSummary}, ${userComment}` : autoSummary;
	},

	formatTermsEditSummary( action, autoCommentArgs, userComment ) {
		return `/* wbeditentity-${action}:0||${autoCommentArgs} */ ${userComment}`;
	},

	formatSitelinkEditSummary( action, siteId, title, badges, userComment ) {
		const commentArgs = action.endsWith( '-both' ) ? `2|${siteId}` : `1|${siteId}`;
		const summaryText = [];
		if ( title ) {
			summaryText.push( title );
		}
		if ( badges ) {
			summaryText.push( badges.join( ', ' ) );
		}
		const autoSummary = `/* wbsetsitelink-${action}:${commentArgs} */ ${summaryText.join( ', ' )}`;

		return userComment ? `${autoSummary}, ${userComment}` : autoSummary;
	}
};
