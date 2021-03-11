import ErrorDeprecatedStatement from '@/presentation/components/ErrorDeprecatedStatement';
import useStore from './useStore';

export default {
	title: 'ErrorDeprecatedStatement',
	component: ErrorDeprecatedStatement,
	decorators: [
		useStore( {
			entityTitle: 'Q219368',
			pageTitle: 'Judith_Butler',
			originalHref: 'https://repo.wiki.example/wiki/Item:Q219368#P18?uselang=en',
			targetLabel: {
				language: 'en',
				value: 'image',
			},
		} ),
	],
};

export function normal() {
	return {
		components: { ErrorDeprecatedStatement },
		template: '<ErrorDeprecatedStatement />',
	};
}
