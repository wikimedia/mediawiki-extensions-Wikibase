import { storiesOf } from '@storybook/vue';

import ErrorWrapper from '@/presentation/components/ErrorWrapper';

storiesOf( 'ErrorWrapper', module )
	.addParameters( { component: ErrorWrapper } )
	.add( 'base view', () => ( {
		components: { ErrorWrapper },
		template: '<ErrorWrapper />',
	} ) );
