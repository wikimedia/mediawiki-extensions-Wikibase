import { storiesOf } from '@storybook/vue';
import ErrorUnsupportedSnakType from '@/presentation/components/ErrorUnsupportedSnakType';
import useStore from './useStore';

storiesOf( 'ErrorUnsupportedSnakType', module )
	.addParameters( { component: ErrorUnsupportedSnakType } )
	.addDecorator( useStore( {
		entityTitle: 'Q7186',
		pageTitle: 'Elizabeth_I_of_England',
		originalHref: 'https://repo.wiki.example/wiki/Item:Q7207#P26?uselang=en',
		targetLabel: {
			language: 'en',
			value: 'spouse',
		},
	} ) )
	.add( 'unknown value', () => ( {
		components: { ErrorUnsupportedSnakType },
		template: '<ErrorUnsupportedSnakType snak-type="somevalue" />',
	} ) )
	.add( 'no value', () => ( {
		components: { ErrorUnsupportedSnakType },
		template: '<ErrorUnsupportedSnakType snak-type="novalue" />',
	} ) );
