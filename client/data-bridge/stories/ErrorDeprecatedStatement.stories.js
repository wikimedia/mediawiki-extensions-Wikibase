import { storiesOf } from '@storybook/vue';
import ErrorDeprecatedStatement from '@/presentation/components/ErrorDeprecatedStatement';
import useStore from './useStore';

storiesOf( 'ErrorDeprecatedStatement', module )
	.addParameters( { component: ErrorDeprecatedStatement } )
	.addDecorator( useStore( {
		entityTitle: 'Q219368',
		pageTitle: 'Judith_Butler',
		originalHref: 'https://repo.wiki.example/wiki/Item:Q219368#P18?uselang=en',
		targetLabel: {
			language: 'en',
			value: 'image',
		},
	} ) )
	.add( 'default', () => ( {
		components: { ErrorDeprecatedStatement },
		template: '<ErrorDeprecatedStatement />',
	} ) );
