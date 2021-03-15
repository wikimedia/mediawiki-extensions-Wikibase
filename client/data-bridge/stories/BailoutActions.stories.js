import BailoutActions from '@/presentation/components/BailoutActions';

export default {
	title: 'BailoutActions',
	component: BailoutActions,
};

export function normal() {
	return {
		components: { BailoutActions },
		template:
			`<BailoutActions
				original-href="https://repo.wiki.example/wiki/Item:Q42?uselang=en"
				page-title="Client page"
			/>`,
	};
}
