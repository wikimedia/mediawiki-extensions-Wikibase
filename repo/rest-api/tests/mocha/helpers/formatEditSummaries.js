'use strict';

module.exports = {
	formatTermEditSummary( module, action, languageCode, labelText, userComment ) {
		const commentArgs = `1|${languageCode}`;
		const autoSummary = `/* ${module}-${action}:${commentArgs} */ ${labelText}`;

		return userComment ? `${autoSummary}, ${userComment}` : autoSummary;
	},

	formatStatementEditSummary( module, action, property, value, userComment ) {
		const commentArgs = module === 'wbsetclaim' ? '1||1' : '1|';
		const autoSummary = `/* ${module}-${action}:${commentArgs} */ [[Property:${property}]]: ${value}`;

		return userComment ? `${autoSummary}, ${userComment}` : autoSummary;
	}
};
