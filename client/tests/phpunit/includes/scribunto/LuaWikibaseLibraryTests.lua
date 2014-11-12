--[[
	Integration tests for the mw.wikibase module

	@license GNU GPL v2+
	@author Marius Hoch < hoo@online.de >
]]

local testframework = require 'Module:TestFramework'

-- Integration tests

local function testGetEntityType()
	return type( mw.wikibase.getEntity() )
end

local function testGetEntitySchemaVersion()
	return mw.wikibase.getEntity().schemaVersion
end

local function testGetEntityObjectType()
	return type( mw.wikibase.getEntityObject() )
end

local function testGetEntityObjectSchemaVersion()
	return mw.wikibase.getEntityObject().schemaVersion
end

local function testGetEntityObjectForeignLabel()
	return mw.wikibase.getEntityObject( 'Q199024' ):getLabel( 'de' )
end

local tests = {
	-- Integration tests

	{ name = 'mw.wikibase.getEntity (type)', func = testGetEntityType, type='ToString',
	  expect = { 'table' }
	},
	{ name = 'mw.wikibase.getEntity (schema version)', func = testGetEntitySchemaVersion,
	  expect = { 1 }
	},
	{ name = 'mw.wikibase.getEntityObject (type)', func = testGetEntityObjectType, type='ToString',
	  expect = { 'table' }
	},
	{ name = 'mw.wikibase.getEntityObject (schema version)', func = testGetEntityObjectSchemaVersion,
	  expect = { 2 }
	},
	{ name = "mw.wikibase.getEntityObject (foreign access - doesn't exist)", func = mw.wikibase.getEntityObject,
	  args = { 'Q1223214234' },
	  expect = { nil }
	},
	{ name = "mw.wikibase.getEntityObject (foreign access)", func = testGetEntityObjectForeignLabel,
	  expect = { 'Arbitrary access \\o/' }
	},
	{ name = 'mw.wikibase.getEntityObject (id must be string)', func = mw.wikibase.getEntityObject,
	  args = { 123 },
	  expect = 'id must be either of type string or nil, number given'
	},
	{ name = 'mw.wikibase.label', func = mw.wikibase.label, type='ToString',
	  args = { 'Q32487' },
	  expect = { 'Lua Test Item' }
	},
	{ name = 'mw.wikibase.sitelink', func = mw.wikibase.sitelink, type='ToString',
	  args = { 'Q32487' },
	  expect = { 'WikibaseClientLuaTest' }
	}
}

return testframework.getTestProvider( tests )
