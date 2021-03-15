import ErrorUnsupportedDatatype from '@/presentation/components/ErrorUnsupportedDatatype';
import useStore from './useStore';

export default {
	title: 'ErrorUnsupportedDatatype',
	component: ErrorUnsupportedDatatype,
	decorators: [
		useStore( {
			entityTitle: 'Q7186',
			pageTitle: 'Marie_Curie',
			originalHref: 'https://repo.wiki.example/wiki/Item:Q7186#P569?uselang=en',
			targetLabel: {
				language: 'en',
				value: 'date of birth',
			},
		} ),
	],
};

export function normal() {
	return {
		components: { ErrorUnsupportedDatatype },
		template: '<ErrorUnsupportedDatatype data-type="time" />',
	};
}
