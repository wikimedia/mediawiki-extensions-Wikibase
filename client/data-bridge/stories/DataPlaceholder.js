import { storiesOf } from '@storybook/vue';

import DataPlaceholder from '@/presentation/components/DataPlaceholder';

storiesOf( 'DataPlaceholder', module )
	.add( 'with example data', () => ( {
		components: { DataPlaceholder },
		template: '<DataPlaceholder target-value="example string"/>',
	} ) );
