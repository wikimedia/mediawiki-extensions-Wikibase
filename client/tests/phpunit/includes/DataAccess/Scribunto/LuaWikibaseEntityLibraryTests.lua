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
			value = 'LabelEN'
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

local function testGetLabel( code )
	return getNewTestItem():getLabel( code )
end

local function testGetSitelink( globalSiteId )
	return getNewTestItem():getSitelink( globalSiteId )
end

local function testGetBestStatements( propertyId )
	return getNewTestItem():getBestStatements( propertyId )
end

local function testGetProperties()
	return getNewTestItem():getProperties()
end

local function testFormatPropertyValues( propertyId, acceptableRanks )
	return getNewTestItem():formatPropertyValues( propertyId, acceptableRanks )
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

local function integrationTestFormatPropertyValues( ranks )
	local entity = mw.wikibase.getEntityObject()
	local propertyId = entity:getProperties()[1]

	return entity:formatPropertyValues( propertyId, ranks )
end

local function integrationTestFormatPropertyValuesByLabel( label )
	local entity = mw.wikibase.getEntityObject()

	return entity:formatPropertyValues( label )
end

local function integrationTestFormatPropertyValuesNoSuchProperty( propertyIdOrLabel )
	local entity = mw.wikibase.getEntityObject( 'Q199024' )

	return entity:formatPropertyValues( propertyIdOrLabel )
end

local function integrationTestFormatPropertyValuesProperty()
	local entity = mw.wikibase.getEntityObject( 'P342' )

	return entity:formatPropertyValues( 'P342', mw.wikibase.entity.claimRanks )
end

local tests = {
	-- Unit Tests

	{ name = 'mw.wikibase.entity exists', func = testExists, type='ToString',
	  expect = { 'table' }
	},
	{ name = 'mw.wikibase.entity.create 1', func = testCreate,
	  args = { {} },
	  expect = 'The entity data must be a table obtained via mw.wikibase.getEntityObject'
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
	  expect = 'The entity data must be a table obtained via mw.wikibase.getEntityObject'
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
	{ name = 'mw.wikibase.entity.getLabel 4 (content language)', func = testGetLabel, type='ToString',
	  expect = { 'LabelDE' }
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
	{ name = 'mw.wikibase.entity.getBestStatements 1', func = testGetBestStatements,
	  args = { 'P321' },
	  expect = { {} }
	},
	{ name = 'mw.wikibase.entity.getBestStatements 2', func = testGetBestStatements,
	  args = { 'P123' },
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
	{ name = 'mw.wikibase.entity.claimRanks', func = getClaimRank,
	  expect = { 2 }
	},

	-- Integration tests

	{ name = 'mw.wikibase.entity.getLabel integration 1', func = integrationTestGetLabel, type='ToString',
	  expect = { 'Lua Test Item' }
	},
	{ name = 'mw.wikibase.entity.getLabel integration 2', func = integrationTestGetLabel, type='ToString',
	  args = { 'en' },
	  expect = { 'Test all the code paths' }
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
}

return testframework.getTestProvider( tests )
