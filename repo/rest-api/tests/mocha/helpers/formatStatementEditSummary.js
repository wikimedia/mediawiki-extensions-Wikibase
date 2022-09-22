'use strict';

module.exports = function formatStatementEditSummary( module, action, mainsnak, userComment ) {
	const property = mainsnak.property;
	const value = mainsnak.datavalue.value;
	const commentArgs = module === 'wbsetclaim' ? '1||1' : '1|';
	const autoSummary = `/* ${module}-${action}:${commentArgs} */ [[Property:${property}]]: ${value}`;

	return userComment ? `${autoSummary}, ${userComment}` : autoSummary;
};
