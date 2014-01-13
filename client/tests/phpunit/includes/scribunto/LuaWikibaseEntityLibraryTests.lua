--[[
	Unit tests for the mw.wikibase.entity module

	@license GNU GPL v2+
	@author Marius Hoch < hoo@online.de >
]]

local testframework = require 'Module:TestFramework'
local render = mw.wikibase.entity

-- A test item (the structure isn't complete... but good enough for tests)
local testItem = {
	id = "Q123",
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

local getNewTestItem = function()
	return mw.wikibase.entity.create( testItem )
end

-- Tests

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

local function testGetProperties()
	return getNewTestItem():getProperties()
end

-- Tests
local tests = {
	{ name = 'mw.wikibase.entity exists', func = testExists, type='ToString',
	  expect = { 'table' }
	},
	{ name = 'mw.wikibase.entity.create 1', func = testCreate, type='ToString',
	  args = { {} },
	  expect = { {} }
	},
	{ name = 'mw.wikibase.entity.create 2', func = testCreate, type='ToString',
	  args = { testItem },
	  expect = { testItem }
	},
	{ name = 'mw.wikibase.entity.create 2', func = testCreate, type='ToString',
	  args = { nil },
	  expect = 'The entity data must be a table'
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
	  expect = 'langCode must be either of type string, number or nil'
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
	  expect = 'globalSiteId must be either of type string, number or nil'
	},
	{ name = 'mw.wikibase.entity.getProperties', func = testGetProperties,
	  expect = { { 'P4321', 'P321' } }
	}
}

return testframework.getTestProvider( tests )
