'use strict';

module.exports = {
	formatStatementEditSummary( module, action, property, value, userComment ) {
		const commentArgs = module === 'wbsetclaim' ? '1||1' : '1|';
		const autoSummary = `/* ${module}-${action}:${commentArgs} */ [[Property:${property}]]: ${value}`;

		return userComment ? `${autoSummary}, ${userComment}` : autoSummary;
	}
};
