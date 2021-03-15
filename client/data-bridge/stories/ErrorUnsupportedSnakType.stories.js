import ErrorUnsupportedSnakType from '@/presentation/components/ErrorUnsupportedSnakType';
import useStore from './useStore';

export default {
	title: 'ErrorUnsupportedSnakType',
	component: ErrorUnsupportedSnakType,
	decorators: [
		useStore( {
			entityTitle: 'Q7186',
			pageTitle: 'Elizabeth_I_of_England',
			originalHref: 'https://repo.wiki.example/wiki/Item:Q7207#P26?uselang=en',
			targetLabel: {
				language: 'en',
				value: 'spouse',
			},
		} ),
	],
};

export function unknownValue() {
	return {
		components: { ErrorUnsupportedSnakType },
		template: '<ErrorUnsupportedSnakType snak-type="somevalue" />',
	};
}

export function noValue() {
	return {
		components: { ErrorUnsupportedSnakType },
		template: '<ErrorUnsupportedSnakType snak-type="novalue" />',
	};
}
