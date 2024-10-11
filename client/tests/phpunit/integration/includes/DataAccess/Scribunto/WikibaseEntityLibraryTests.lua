--[[
	Unit and integration tests for the mw.wikibase.entity module

	@license GNU GPL v2+
	@author Marius Hoch < hoo@online.de >
	@author Bene* < benestar.wikimedia@gmail.com >
]]

local testframework = require 'Module:TestFramework'

-- A test item (the structure isn't complete... but good enough for tests)
local testItem = {
	id = "Q123",
	schemaVersion = 2,
	claims = {
		P321 = {},
		P4321 = {}
	},
	labels = {
		de = {
			value = 'LabelDE'
		},
		en = {
			value = 'LabelDE-fallback',
			language = 'de'
		}
	},
	descriptions = {
		de = {
			value = 'DescriptionDE'
		},
		en = {
			value = 'DescriptionDE-fallback',
			language = 'de'
		}
	},
	sitelinks = {
		dewiki = {
			title = 'Deutsch'
		},
		ruwiki = {
			title = 'Русский'
		}
	}
}
-- A legacy test "item"
local testItemLegacy = {
	schemaVersion = 1
}

local getNewTestItem = function()
	return mw.wikibase.entity.create( testItem )
end

-- Unit Tests

local function testExists()
	return type( mw.wikibase.entity )
end

local function testCreate( data )
	return mw.wikibase.entity.create( data )
end

local function testGetId()
	return getNewTestItem():getId()
end

local function testGetLabel( code )
	return getNewTestItem():getLabel( code )
end

local function testGetLabelWithLang( code )
	return getNewTestItem():getLabelWithLang( code )
end

local function testGetDescription( code )
	return getNewTestItem():getDescription( code )
end

local function testGetDescriptionWithLang( code )
	return getNewTestItem():getDescriptionWithLang( code )
end

local function testGetSitelink( globalSiteId )
	return getNewTestItem():getSitelink( globalSiteId )
end

local function testGetBestStatements( propertyId )
	return getNewTestItem():getBestStatements( propertyId )
end

local function testGetAllStatements( propertyId )
	return getNewTestItem():getAllStatements( propertyId )
end

local function testGetProperties()
	return getNewTestItem():getProperties()
end

local function testFormatPropertyValues( propertyId, acceptableRanks )
	return getNewTestItem():formatPropertyValues( propertyId, acceptableRanks )
end

local function testFormatStatements( propertyId, acceptableRanks )
	return getNewTestItem():formatStatements( propertyId, acceptableRanks )
end

local function getClaimRank()
	return mw.wikibase.entity.claimRanks.RANK_PREFERRED
end

-- Integration tests

local function integrationTestGetPropertiesCount()
	return #( mw.wikibase.getEntityObject():getProperties() )
end

local function integrationTestGetLabel( langCode )
	return mw.wikibase.getEntityObject():getLabel( langCode )
end

local function integrationTestGetLabelWithLang( langCode )
	return mw.wikibase.getEntityObject():getLabelWithLang( langCode )
end

local function integrationTestGetDescription( langCode )
	return mw.wikibase.getEntityObject( 'Q32487' ):getDescription( langCode )
end

local function integrationTestGetDescriptionWithLang( langCode )
	return mw.wikibase.getEntityObject( 'Q32487' ):getDescriptionWithLang( langCode )
end

local function integrationTestGetSitelink( globalSiteId )
	return mw.wikibase.getEntityObject():getSitelink( globalSiteId )
end

local function integrationTestGetBestStatements( propertyId )
	local entity = mw.wikibase.getEntityObject()
	local statements = entity:getBestStatements( propertyId )
	local result = {}

	for i, statement in pairs( statements ) do
		result[#result + 1] = statement.mainsnak.datavalue.value
	end

	return result
end

local function integrationTestGetAllStatements( propertyId )
	local entity = mw.wikibase.getEntityObject()
	local statements = entity:getAllStatements( propertyId )
	local result = {}

	for i, statement in pairs( statements ) do
		result[#result + 1] = statement.mainsnak.datavalue.value
	end

	return result
end

local function integrationTestFormatPropertyValues( ranks )
	local entity = mw.wikibase.getEntityObject()
	local propertyId = entity:getProperties()[1]

	return entity:formatPropertyValues( propertyId, ranks )
end

local function integrationTestFormatPropertyValuesByLabel( label )
	local entity = mw.wikibase.getEntityObject()

	return entity:formatPropertyValues( label )
end

local function integrationTestFormatPropertyValuesNoSuchProperty( propertyLabelOrId )
	local entity = mw.wikibase.getEntityObject( 'Q199024' )

	return entity:formatPropertyValues( propertyLabelOrId )
end

local function integrationTestFormatPropertyValuesProperty()
	local entity = mw.wikibase.getEntityObject( 'P342' )

	return entity:formatPropertyValues( 'P342', mw.wikibase.entity.claimRanks )
end

local function integrationTestFormatStatements( ranks )
	local entity = mw.wikibase.getEntityObject()
	local propertyId = entity:getProperties()[1]

	return entity:formatStatements( propertyId, ranks )
end

local function integrationTestFormatStatementsByLabel( label )
	local entity = mw.wikibase.getEntityObject()

	return entity:formatStatements( label )
end

local function integrationTestFormatStatementsGlobeCoordinate()
	local entity = mw.wikibase.getEntityObject( 'Q32489' )

	local formattedStatement = entity:formatStatements( 'P625' ).value
	return formattedStatement:match( "-maplink-" ) and not formattedStatement:match( "<maplink" )
end

local function integrationTestFormatStatementsNoSuchProperty( propertyLabelOrId )
	local entity = mw.wikibase.getEntityObject( 'Q199024' )

	return entity:formatStatements( propertyLabelOrId )
end

local function integrationTestFormatStatementsProperty()
	local entity = mw.wikibase.getEntityObject( 'P342' )

	return entity:formatStatements( 'P342', mw.wikibase.entity.claimRanks )
end

local function testClaimsPairSize()
	local entity = mw.wikibase.getEntityObject( 'Q32487' )
	local count = 0
	for a,b in pairs(entity.claims) do
		count = count + 1
	end

	return count
end

local function testClaimsPairContent()
	local testItem = getNewTestItem()
	local claimsTable = {}
	for a in pairs(testItem.claims) do
		claimsTable[a] = testItem.claims[a]
	end
	return claimsTable
end

local function testClaimsNewIndex()
	local entity = mw.wikibase.getEntityObject( 'Q32487' )
	entity.claims['P321'] = ""
end

local function testClaimsAccessIndex( propertyId )
	local entity = mw.wikibase.getEntityObject( 'Q32487' )

	return entity.claims[propertyId]
end

local function testReassignEntityId_number()
	local entity = mw.wikibase.getEntityObject( 'Q32487' )
	entity.id = 0
	return entity.labels.en.value
end

local function testReassignEntityId_nil()
	local entity = mw.wikibase.getEntityObject( 'Q32487' )
	entity.id = nil
	return entity.labels.en.value
end

local function testReassignEntityId_string()
	local entity = mw.wikibase.getEntityObject( 'Q32487' )
	entity.id = 'not a valid entity ID'
	return entity.labels.en.value
end


local tests = {
	-- Unit Tests

	{ name = 'mw.wikibase.entity exists', func = testExists, type='ToString',
	  expect = { 'table' }
	},
	{ name = 'mw.wikibase.entity.claims pair size', func = testClaimsPairSize,
	  expect = { 1 }
	},
	{ name = 'mw.wikibase.entity.claims pair content', func = testClaimsPairContent,
	  expect = { {P321={}, P4321={}}, }
	},
	{ name = 'mw.wikibase.entity.claims new index', func = testClaimsNewIndex,
	  expect = 'Entity cannot be modified'
	},
	{ name = 'mw.wikibase.entity.claims access invalid index', func = testClaimsAccessIndex,
	  args = { 'something' },
	  expect = { nil }
	},
	{ name = 'mw.wikibase.entity.create with empty table', func = testCreate,
	  args = { {} },
	  expect = 'Expected a non-empty table obtained via mw.wikibase.getEntityObject'
	},
	{ name = 'mw.wikibase.entity.create without id', func = testCreate,
	  args = { { schemaVersion = 2 } },
	  expect = 'data.id must be a string, got nil instead'
	},
	{ name = 'mw.wikibase.entity.create without schemaVersion', func = testCreate,
	  args = { { id = 'Q1' } },
	  expect = 'data.schemaVersion must be a number, got nil instead'
	},
	{ name = 'mw.wikibase.entity.create 2', func = testCreate, type='ToString',
	  args = { testItem },
	  expect = { testItem }
	},
	{ name = 'mw.wikibase.entity.create 3', func = testCreate, type='ToString',
	  args = { testItemLegacy },
	  expect = 'mw.wikibase.entity must not be constructed using legacy data'
	},
	{ name = 'mw.wikibase.entity.create (no table)', func = testCreate, type='ToString',
	  args = { nil },
	  expect = 'Expected a table obtained via mw.wikibase.getEntityObject, got nil instead'
	},
	{ name = 'mw.wikibase.entity.getId', func = testGetId,
	  expect = { 'Q123' }
	},
	{ name = 'mw.wikibase.entity.getLabel 1', func = testGetLabel, type='ToString',
	  args = { 'de' },
	  expect = { 'LabelDE' }
	},
	{ name = 'mw.wikibase.entity.getLabel 2', func = testGetLabel, type='ToString',
	  args = { 'oooOOOOooo' },
	  expect = { nil }
	},
	{ name = 'mw.wikibase.entity.getLabel 3', func = testGetLabel, type='ToString',
	  args = { function() end },
	  expect = "bad argument #1 to 'getLabel' (string, number or nil expected, got function)"
	},
	{ name = 'mw.wikibase.entity.getLabel 4 (actual lang code)', func = testGetLabel, type='ToString',
	  args = { 'en' },
	  expect = { 'LabelDE-fallback' }
	},
	{ name = 'mw.wikibase.entity.getLabelWithLang 1', func = testGetLabelWithLang, type='ToString',
	  args = { 'de' },
	  expect = { 'LabelDE', 'de' }
	},
	{ name = 'mw.wikibase.entity.getLabelWithLang 2', func = testGetLabelWithLang, type='ToString',
	  args = { 'oooOOOOooo' },
	  expect = { nil }
	},
	{ name = 'mw.wikibase.entity.getLabelWithLang 3', func = testGetLabelWithLang, type='ToString',
	  args = { function() end },
	  expect = "bad argument #1 to 'getLabelWithLang' (string, number or nil expected, got function)"
	},
	{ name = 'mw.wikibase.entity.getLabelWithLang 4 (content language)', func = testGetLabelWithLang, type='ToString',
	  expect = { 'LabelDE', 'de' }
	},
	{ name = 'mw.wikibase.entity.getLabelWithLang 5 (actual lang code)', func = testGetLabelWithLang, type='ToString',
	  args = { 'en' },
	  expect = { 'LabelDE-fallback', 'de' }
	},
	{ name = 'mw.wikibase.entity.getDescription 1', func = testGetDescription, type='ToString',
	  args = { 'de' },
	  expect = { 'DescriptionDE' }
	},
	{ name = 'mw.wikibase.entity.getDescription 2', func = testGetDescription, type='ToString',
	  args = { 'oooOOOOooo' },
	  expect = { nil }
	},
	{ name = 'mw.wikibase.entity.getDescription 3', func = testGetDescription, type='ToString',
	  args = { function() end },
	  expect = "bad argument #1 to 'getDescription' (string, number or nil expected, got function)"
	},
	{ name = 'mw.wikibase.entity.getDescription 4 (actual lang code)', func = testGetDescription, type='ToString',
	  args = { 'en' },
	  expect = { 'DescriptionDE-fallback' }
	},
	{ name = 'mw.wikibase.entity.getDescriptionWithLang 1', func = testGetDescriptionWithLang, type='ToString',
	  args = { 'de' },
	  expect = { 'DescriptionDE', 'de' }
	},
	{ name = 'mw.wikibase.entity.getDescriptionWithLang 2', func = testGetDescriptionWithLang, type='ToString',
	  args = { 'oooOOOOooo' },
	  expect = { nil }
	},
	{ name = 'mw.wikibase.entity.getDescriptionWithLang 3', func = testGetDescriptionWithLang, type='ToString',
	  args = { function() end },
	  expect = "bad argument #1 to 'getDescriptionWithLang' (string, number or nil expected, got function)"
	},
	{ name = 'mw.wikibase.entity.getDescriptionWithLang 4 (content language)', func = testGetDescriptionWithLang, type='ToString',
	  expect = { 'DescriptionDE', 'de' }
	},
	{ name = 'mw.wikibase.entity.getDescriptionWithLang 5 (actual lang code)', func = testGetDescriptionWithLang, type='ToString',
	  args = { 'en' },
	  expect = { 'DescriptionDE-fallback', 'de' }
	},
	{ name = 'mw.wikibase.entity.getSitelink 1', func = testGetSitelink, type='ToString',
	  args = { 'ruwiki' },
	  expect = { 'Русский' }
	},
	{ name = 'mw.wikibase.entity.getSitelink 2', func = testGetSitelink, type='ToString',
	  args = { 'nilwiki' },
	  expect = { nil }
	},
	{ name = 'mw.wikibase.entity.getSitelink 3', func = testGetSitelink, type='ToString',
	  args = { {} },
	  expect = "bad argument #1 to 'getSitelink' (string, number or nil expected, got table)"
	},
	{ name = 'mw.wikibase.entity.getBestStatements bad property id 1', func = testGetBestStatements, type='ToString',
	  args = { function() end },
	  expect = "bad argument #1 to 'getBestStatements' (string expected, got function)"
	},
	{ name = 'mw.wikibase.entity.getBestStatements non existing property', func = testGetBestStatements, type='ToString',
	  args = { 'P01' },
	  expect = { {} }
	},
	{ name = 'mw.wikibase.entity.getBestStatements 1', func = testGetBestStatements,
	  args = { 'P321' },
	  expect = { {} }
	},
	{ name = 'mw.wikibase.entity.getBestStatements 2', func = testGetBestStatements,
	  args = { 'P123' },
	  expect = { {} }
	},
	{ name = 'mw.wikibase.entity.getBestStatements 3', func = testGetBestStatements,
	  args = { 'LuaTestStringProperty' },
	  expect = { {} }
	},
	{ name = 'mw.wikibase.entity.getAllStatements bad property id 1', func = testGetAllStatements, type='ToString',
	  args = { function() end },
	  expect = "bad argument #1 to 'getAllStatements' (string expected, got function)"
	},
	{ name = 'mw.wikibase.entity.getAllStatements non existing property', func = testGetAllStatements, type='ToString',
	  args = { 'P01' },
	  expect = { {} }
	},
	{ name = 'mw.wikibase.entity.getAllStatements 1', func = testGetAllStatements,
	  args = { 'P321' },
	  expect = { {} }
	},
	{ name = 'mw.wikibase.entity.getAllStatements 2', func = testGetAllStatements,
	  args = { 'P123' },
	  expect = { {} }
	},
	{ name = 'mw.wikibase.entity.getAllStatements 3', func = testGetAllStatements,
	  args = { 'LuaTestStringProperty' },
	  expect = { {} }
	},
	{ name = 'mw.wikibase.entity.getProperties', func = testGetProperties,
	  expect = { { 'P4321', 'P321' } }
	},
	{ name = 'mw.wikibase.entity.formatPropertyValues bad param 1', func = testFormatPropertyValues,
	  args = { function() end },
	  expect = "bad argument #1 to 'formatPropertyValues' (string expected, got function)"
	},
	{ name = 'mw.wikibase.entity.formatPropertyValues bad param 2', func = testFormatPropertyValues,
	  args = { 'Q123', function() end },
	  expect = "bad argument #2 to 'formatPropertyValues' (table or nil expected, got function)"
	},
	{ name = 'mw.wikibase.entity.formatStatements bad param 1', func = testFormatStatements,
	  args = { function() end },
	  expect = "bad argument #1 to 'formatStatements' (string expected, got function)"
	},
	{ name = 'mw.wikibase.entity.formatStatements bad param 2', func = testFormatStatements,
	  args = { 'Q123', function() end },
	  expect = "bad argument #2 to 'formatStatements' (table or nil expected, got function)"
	},
	{ name = 'mw.wikibase.entity.claimRanks', func = getClaimRank,
	  expect = { 2 }
	},

	-- Integration tests

	{ name = 'mw.wikibase.entity.getLabel integration 1', func = integrationTestGetLabel, type='ToString',
	  expect = { 'Lua-Test-Datenobjekt' }
	},
	{ name = 'mw.wikibase.entity.getLabel integration 2', func = integrationTestGetLabel, type='ToString',
	  args = { 'en' },
	  expect = { 'Lua Test Item' }
	},
	{ name = 'mw.wikibase.entity.getLabelWithLang integration 1', func = integrationTestGetLabelWithLang, type='ToString',
	  expect = { 'Lua-Test-Datenobjekt', 'de' }
	},
	{ name = 'mw.wikibase.entity.getLabelWithLang integration 2', func = integrationTestGetLabelWithLang, type='ToString',
	  args = { 'en' },
	  expect = { 'Lua Test Item', 'en' }
	},
	{ name = 'mw.wikibase.entity.getDescription integration', func = integrationTestGetDescription, type='ToString',
	  expect = { 'Description of Q32487' }
	},
	{ name = 'mw.wikibase.entity.getDescriptionWithLang integration', func = integrationTestGetDescriptionWithLang, type='ToString',
	  expect = { 'Description of Q32487', 'de' }
	},
	{ name = 'mw.wikibase.entity.getSitelink integration 1', func = integrationTestGetSitelink, type='ToString',
	  expect = { 'WikibaseClientDataAccessTest' }
	},
	{ name = 'mw.wikibase.entity.getSitelink integration 2', func = integrationTestGetSitelink, type='ToString',
	  args = { 'fooSiteId' },
	  expect = { 'FooBarFoo' }
	},
	{ name = 'mw.wikibase.entity.getBestStatements integration 1', func = integrationTestGetBestStatements,
	  args = { 'P342' },
	  expect = { { 'Lua :)' } }
	},
	{ name = 'mw.wikibase.entity.getBestStatements integration 2', func = integrationTestGetBestStatements,
	  args = { 'P123' },
	  expect = { {} }
	},
	{ name = 'mw.wikibase.entity.getBestStatements integration 3', func = integrationTestGetBestStatements,
	  args = { 'LuaTestStringProperty' },
	  expect = { { 'Lua :)' } }
	},
	{ name = 'mw.wikibase.entity.getAllStatements integration 1', func = integrationTestGetAllStatements,
	  args = { 'P342' },
	  expect = { { 'Lua :)', 'Lua is clearly superior to the parser function' } }
	},
	{ name = 'mw.wikibase.entity.getAllStatements integration 2', func = integrationTestGetAllStatements,
	  args = { 'P123' },
	  expect = { {} }
	},
	{ name = 'mw.wikibase.entity.getAllStatements integration 3', func = integrationTestGetAllStatements,
	  args = { 'LuaTestStringProperty' },
	  expect = { { 'Lua :)', 'Lua is clearly superior to the parser function' } }
	},
	{ name = 'mw.wikibase.entity.getProperties integration', func = integrationTestGetPropertiesCount,
	  expect = { 1 }
	},
	{ name = 'mw.wikibase.entity.formatPropertyValues integration 1', func = integrationTestFormatPropertyValues,
	  expect = { { label = 'LuaTestStringProperty', value = 'Lua :)' } }
	},
	{ name = 'mw.wikibase.entity.formatPropertyValues integration 2', func = integrationTestFormatPropertyValues,
	  args = { { mw.wikibase.entity.claimRanks.RANK_PREFERRED, mw.wikibase.entity.claimRanks.RANK_NORMAL } },
	  expect = { { label = 'LuaTestStringProperty', value = 'Lua :), Lua is clearly superior to the parser function' } }
	},
	{ name = 'mw.wikibase.entity.formatPropertyValues integration 3', func = integrationTestFormatPropertyValues,
	  args = { { mw.wikibase.entity.claimRanks.RANK_TRUTH } },
	  expect = { { label = 'LuaTestStringProperty', value = '' } }
	},
	{ name = 'mw.wikibase.entity.formatPropertyValues integration (by label)', func = integrationTestFormatPropertyValuesByLabel,
	  args = { 'LuaTestStringProperty' },
	  expect = { { label = 'LuaTestStringProperty', value = 'Lua :)' } }
	},
	{ name = 'mw.wikibase.entity.formatPropertyValues by non-existing label', func = integrationTestFormatPropertyValuesByLabel,
	  args = { 'A label that doesn\'t exist' },
	  expect = { { label = 'A label that doesn\'t exist', value = nil } }
	},
	{ name = 'mw.wikibase.entity.formatPropertyValues by non-existing property', func = integrationTestFormatPropertyValuesByLabel,
	  args = { 'P123456789' },
	  expect = { { label = 'P123456789', value = nil } }
	},
	{ name = 'mw.wikibase.entity.formatPropertyValues no such property', func = integrationTestFormatPropertyValuesNoSuchProperty,
	  args = { 'P342' },
	  expect = { { label = 'LuaTestStringProperty', value = '' } }
	},
	{ name = 'mw.wikibase.entity.formatPropertyValues no such property (by label)', func = integrationTestFormatPropertyValuesNoSuchProperty,
	  args = { 'LuaTestStringProperty' },
	  expect = { { label = 'LuaTestStringProperty', value = '' } }
	},
	{ name = 'mw.wikibase.entity.formatPropertyValues integration property', func = integrationTestFormatPropertyValuesProperty,
	  expect = { { label = 'LuaTestStringProperty', value = 'Lua :)' } }
	},
	{ name = 'mw.wikibase.entity.formatStatements integration 1', func = integrationTestFormatStatements,
	  expect = { { label = 'LuaTestStringProperty', value = '<span><span>Lua :)</span></span>' } }
	},
	{ name = 'mw.wikibase.entity.formatStatements integration 2', func = integrationTestFormatStatements,
	  args = { { mw.wikibase.entity.claimRanks.RANK_PREFERRED, mw.wikibase.entity.claimRanks.RANK_NORMAL } },
	  expect = { { label = 'LuaTestStringProperty', value = '<span><span>Lua :)</span>, <span>Lua is clearly superior to the parser function</span></span>' } }
	},
	{ name = 'mw.wikibase.entity.formatStatements integration 3', func = integrationTestFormatStatements,
	  args = { { mw.wikibase.entity.claimRanks.RANK_TRUTH } },
	  expect = { { label = 'LuaTestStringProperty', value = '' } }
	},
	{ name = 'mw.wikibase.entity.formatStatements integration (by label)', func = integrationTestFormatStatementsByLabel,
	  args = { 'LuaTestStringProperty' },
	  expect = { { label = 'LuaTestStringProperty', value = '<span><span>Lua :)</span></span>' } }
	},
	{ name = 'mw.wikibase.entity.formatStatements tag parsing', func = integrationTestFormatStatementsGlobeCoordinate,
	  expect = { true }
	},
	{ name = 'mw.wikibase.entity.formatStatements by non-existing label', func = integrationTestFormatStatementsByLabel,
	  args = { 'A label that doesn\'t exist' },
	  expect = { { label = 'A label that doesn\'t exist', value = nil } }
	},
	{ name = 'mw.wikibase.entity.formatStatements by non-existing property', func = integrationTestFormatStatementsByLabel,
	  args = { 'P123456789' },
	  expect = { { label = 'P123456789', value = nil } }
	},
	{ name = 'mw.wikibase.entity.formatStatements no such property', func = integrationTestFormatStatementsNoSuchProperty,
	  args = { 'P342' },
	  expect = { { label = 'LuaTestStringProperty', value = '' } }
	},
	{ name = 'mw.wikibase.entity.formatStatements no such property (by label)', func = integrationTestFormatStatementsNoSuchProperty,
	  args = { 'LuaTestStringProperty' },
	  expect = { { label = 'LuaTestStringProperty', value = '' } }
	},
	{ name = 'mw.wikibase.entity.formatStatements integration property', func = integrationTestFormatStatementsProperty,
	  expect = { { label = 'LuaTestStringProperty', value = '<span><span>Lua :)</span></span>' } }
	},
	{ name = 'reassigning entity ID to 0 does not crash', func = testReassignEntityId_number,
	  expect = { 'Lua Test Item' }
	},
	{ name = 'reassigning entity ID to nil does not crash', func = testReassignEntityId_nil,
	  expect = { 'Lua Test Item' }
	},
	{ name = 'reassigning entity ID to string does not crash', func = testReassignEntityId_string,
	  expect = { 'Lua Test Item' }
	},
}

return testframework.getTestProvider( tests )
