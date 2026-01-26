const { ref, watch } = require( 'vue' );
const { storeToRefs } = require( 'pinia' );
const { snakValueStrategyFactory } = require( '../store/snakValueStrategyFactory.js' );

class LookupSource {
	constructor( allowEmpty ) {
		this.allowEmpty = allowEmpty;
		this.lookupMenuItems = ref( [] );
		this.lookupInputValue = ref( '' );
		this.lookupSelection = ref( null );
		this.setupWatches();
	}

	setupWatches() {
		watch(
			this.lookupInputValue,
			( newVal ) => this.updateInputValue( newVal )
		);
		watch(
			this.lookupSelection,
			( newVal ) => this.updateSelectedValue( newVal )
		);
	}

	async search( searchTerm, offset ) {
		return [];
	}

	async searchDebounced( searchTerm, offset ) {
		return [];
	}

	async fetchLookupResults( searchTerm, offset = 0 ) {
		if ( offset > 0 ) {
			return this.search( searchTerm, offset );
		} else {
			return this.searchDebounced( searchTerm, offset );
		}
	}

	async updateInputValue( newInputValue ) {
		if ( !newInputValue ) {
			this.lookupMenuItems.value = [];
			return;
		}
		this.lookupMenuItems.value = await this.fetchLookupResults( newInputValue );
	}

	async updateSelectedValue( newSelectedValue ) {
		if ( newSelectedValue !== this.lookupInputValue.value ) {
			const lookupItem = this.lookupMenuItems.value.find( ( item ) => item.value === newSelectedValue );
			if ( !lookupItem ) {
				return;
			}
			this.lookupInputValue.value = lookupItem.label;
		}
	}

	isIncomplete() {
		if ( this.allowEmpty && this.lookupInputValue.value === '' ) {
			return false;
		}
		return this.lookupInputValue.value === '' || this.lookupSelection.value === null;
	}

	async onLoadMore() {
		if ( !this.lookupInputValue.value ) {
			return;
		}

		const newResults = await this.fetchLookupResults( this.lookupInputValue.value, this.lookupMenuItems.value.length );
		this.lookupMenuItems.value.push( ...newResults );
	}
}

class ApiLookupSource extends LookupSource {
	constructor( initialInputValue, initialSelectionValue, datatype, transformSearchResults, allowEmpty ) {
		super( allowEmpty );
		this.lookupSelection.value = initialSelectionValue;
		this.lookupInputValue.value = initialInputValue;
		this.datatype = datatype;
		this.transformSearchResults = transformSearchResults;
	}

	async search( searchTerm, offset ) {
		const searchResults = await snakValueStrategyFactory.searchByDatatype( this.datatype, searchTerm, offset );
		return this.transformSearchResults( searchResults );
	}

	async searchDebounced( searchTerm, offset ) {
		const searchResults = await snakValueStrategyFactory.searchByDatatypeDebounced( this.datatype, searchTerm, offset );
		return this.transformSearchResults( searchResults );
	}
}

class SnakLookupSource extends ApiLookupSource {
	constructor( snakStore ) {
		super(
			snakStore.textvalue,
			snakStore.selectionvalue,
			snakStore.datatype,
			snakStore.valueStrategy.transformSearchResults,
			false
		);
		const { textvalue, selectionvalue } = storeToRefs( snakStore );
		this.lookupSelection = selectionvalue;
		this.lookupInputValue = textvalue;
		this.valueStrategy = snakStore.valueStrategy;
		this.setupWatches();
	}

	async updateSelectedValue( newSelectedValue ) {
		await super.updateSelectedValue( newSelectedValue );
		await this.valueStrategy.triggerParse();
	}
}

module.exports = {
	ApiLookupSource,
	SnakLookupSource
};
