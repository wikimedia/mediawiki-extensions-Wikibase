import { storiesOf } from '@storybook/vue';
import ErrorUnsupportedSnakType from '../src/presentation/components/ErrorUnsupportedSnakType';
import useStore from './useStore';

storiesOf( 'ErrorUnsupportedSnakType', module )
	.addParameters( { component: ErrorUnsupportedSnakType } )
	.addDecorator( useStore( {
		entityTitle: 'Q7186',
		pageTitle: 'Marie_Curie',
		originalHref: 'https://repo.wiki.example/wiki/Item:Q7186?uselang=en',
	} ) )
	.add( 'unknown value', () => ( {
		components: { ErrorUnsupportedSnakType },
		template: '<ErrorUnsupportedSnakType snak-type="somevalue" />',
	} ) )
	.add( 'no value', () => ( {
		components: { ErrorUnsupportedSnakType },
		template: '<ErrorUnsupportedSnakType snak-type="novalue" />',
	} ) );
