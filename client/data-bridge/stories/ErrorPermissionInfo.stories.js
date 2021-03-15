import ErrorPermissionInfo from '@/presentation/components/ErrorPermissionInfo';

export default {
	title: 'ErrorPermissionInfo',
	component: ErrorPermissionInfo,
};

export function baseView() {
	return {
		components: { ErrorPermissionInfo },
		template: `<ErrorPermissionInfo
				:message-header="'<strong>This value is currently fully protected on Wikidata and can be edited only by administrators.</strong>'"
				:message-body="'<p><strong>Why is this value protected?</strong></p>\\n<ul>\\n<li>Some templates and Items (and their respective values) are permanently protected due to visibility. Occasionally, they are protected due to editing disputes. Most Items can be edited by anyone.</li>\\n<li>The reason for protection can be found in the protection log.</li>\\n</ul>\\n<p><strong>What can I do?</strong></p>\\n<ul><li>Discuss this Item with others.</li></ul>'"
			/>`,
	};
}

export function initiallyExpanded() {
	return {
		components: { ErrorPermissionInfo },
		template: `<ErrorPermissionInfo
				:message-header="'<strong>This value is currently fully protected on Wikidata and can be edited only by administrators.</strong>'"
				:message-body="'<p><strong>Why is this value protected?</strong></p>\\n<ul>\\n<li>Some templates and Items (and their respective values) are permanently protected due to visibility. Occasionally, they are protected due to editing disputes. Most Items can be edited by anyone.</li>\\n<li>The reason for protection can be found in the protection log.</li>\\n</ul>\\n<p><strong>What can I do?</strong></p>\\n<ul><li>Discuss this Item with others.</li></ul>'"
				:expanded-by-default="true"
			/>`,
	};
}
