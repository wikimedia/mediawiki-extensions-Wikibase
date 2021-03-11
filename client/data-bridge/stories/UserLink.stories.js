import UserLink from '@/presentation/components/UserLink';

const router = {
	getPageUrl( title ) {
		return `https://www.wikidata.org/wiki/${title}`;
	},
};

export default {
	title: 'UserLink',
	component: UserLink,
};

export function withLinkToUserPage() {
	return {
		data: () => ( {
			userId: 2799899,
			userName: 'MediaWiki default',
			router,
		} ),
		components: { UserLink },
		template:
			`<p>This action was performed by
				<UserLink
					:userId="userId"
					:userName="userName"
					:router="router"
				/>.
			</p>`,
	};
}

export function withoutLinkToUserPage() {
	return {
		data: () => ( {
			userId: 0,
			userName: 'Meta-Wiki Welcome',
			router,
		} ),
		components: { UserLink },
		template:
			`<p>This action was performed by
				<UserLink
					:userId="userId"
					:userName="userName"
					:router="router"
				/>.
			</p>`,
	};
}

export function bidirectionalityBehavior() {
	return {
		data: () => ( {
			userId: 1536453,
			userName: 'علاء',
			router,
		} ),
		components: { UserLink },
		template:
			`<dl>
				<dt>With <code>UserLink</code></dt>
				<dd>
					<UserLink
						:userId="userId"
						:userName="userName"
						:router="router"
					/>: 1st place.
				</dd>
				<dt>Without <code>UserLink</code></dt>
				<dd>
					{{ userName }}: 1st place.
				</dd>
				<!-- undo some reset.css (<component> needed because Vue strips out plain <style>) -->
				<component is="style">
				code {
					font-family: monospace;
				}

				dd {
					margin-inline-start: 40px;
					margin-block-end: 1em;
				}
				</component>
			</dl>`,
	};
}
