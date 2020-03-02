import { storiesOf } from '@storybook/vue';
import EventEmittingButton from '@/presentation/components/EventEmittingButton.vue';

storiesOf( 'EventEmittingButton', module )
	.addParameters( { component: EventEmittingButton } )
	.add( 'primaryProgressive L', () => ( {
		components: { EventEmittingButton },
		template:
			`<EventEmittingButton
				type="primaryProgressive"
				size="L"
				message="primaryProgressive"
			/>`,
	} ) )
	.add( 'primaryProgressive as link', () => ( {
		components: { EventEmittingButton },
		template:
			`<EventEmittingButton
				type="primaryProgressive"
				size="L"
				message="primaryProgressive"
				href="https://www.mediawiki.org/wiki/Wikidata_Bridge"
				:preventDefault="false"
			/>`,
	} ) )
	.add( 'primaryProgressive as link opening in new tab', () => ( {
		components: { EventEmittingButton },
		template:
			`<EventEmittingButton
				type="primaryProgressive"
				size="L"
				message="primaryProgressive"
				href="https://www.mediawiki.org/wiki/Wikidata_Bridge"
				:newTab="true"
				:preventDefault="false"
			/>`,
	} ) )
	.add( 'squary primaryProgressive', () => ( {
		components: { EventEmittingButton },
		template:
			`<EventEmittingButton
				type="primaryProgressive"
				size="L"
				:squary="true"
				message="squary primaryProgressive"
			/>`,
	} ) )
	.add( 'primaryProgressive M', () => ( {
		components: { EventEmittingButton },
		template:
			`<EventEmittingButton
				type="primaryProgressive"
				size="M"
				message="primaryProgressive M"
			/>`,
	} ) )
	.add( 'cancel', () => ( {
		components: { EventEmittingButton },
		template:
			`<EventEmittingButton
				type="cancel"
				size="L"
				message="cancel"
			/>`,
	} ) )
	.add( 'cancel squary', () => ( {
		components: { EventEmittingButton },
		template:
			`<EventEmittingButton
				type="cancel"
				size="L"
				:squary="true"
				message="cancel"
			/>`,
	} ) )
	.add( 'primaryProgressive disabled', () => ( {
		components: { EventEmittingButton },
		template:
			`<EventEmittingButton
				type="primaryProgressive"
				size="L"
				message="disabled primaryProgressive"
				:disabled="true"
			/>`,
	} ) )
	.add( 'cancel disabled', () => ( {
		components: { EventEmittingButton },
		template:
			`<EventEmittingButton
				type="cancel"
				size="L"
				message="disabled cancel"
				:disabled="true"
			/>`,
	} ) );
