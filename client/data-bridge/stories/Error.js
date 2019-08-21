import { storiesOf } from '@storybook/vue';

import ErrorWrapper from '@/presentation/components/ErrorWrapper';

storiesOf( 'ErrorWrapper', module )
	.add( 'base view', () => ( {
		components: { ErrorWrapper },
		template: '<ErrorWrapper />',
	} ), { info: true } );
