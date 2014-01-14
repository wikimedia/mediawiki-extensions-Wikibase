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

local function testGetLabel( code )
	return getNewTestItem():getLabel( code )
end


-- Tests
local tests = {
	{ name = 'mw.wikibase.entity exists', func = testExists, type='ToString',
	  expect = { 'table' }
	},
	{ name = 'mw.wikibase.getLabel 1', func = testGetLabel, type='ToString',
	  args = { 'de' },
	  expect = { 'LabelDE' }
	},
	{ name = 'mw.wikibase.getLabel 2', func = testGetLabel, type='ToString',
	  args = { 'oooOOOOooo' },
	  expect = { nil }
	}
}

return testframework.getTestProvider( tests )
