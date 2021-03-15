import ErrorAmbiguousStatement from '@/presentation/components/ErrorAmbiguousStatement';
import useStore from './useStore';

export default {
	title: 'ErrorAmbiguousStatement',
	component: ErrorAmbiguousStatement,
	decorators: [
		useStore( {
			entityTitle: 'Q7186',
			pageTitle: 'Marie_Curie',
			originalHref: 'https://repo.wiki.example/wiki/Item:Q7186#P166?uselang=en',
			targetLabel: {
				language: 'en',
				value: 'award received',
			},
		} ),
	],
};

export function normal() {
	return {
		components: { ErrorAmbiguousStatement },
		template: '<ErrorAmbiguousStatement />',
	};
}
