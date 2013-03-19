--[[
    Registers and defines functions to access Wikibase through the Scribunto extension
    Provides Lua setupInterface

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License along
    with this program; if not, write to the Free Software Foundation, Inc.,
    51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
    http://www.gnu.org/copyleft/gpl.html

    @since 0.4

    @licence GNU GPL v2+
    @author Jens Ohlig < jens.ohlig@wikimedia.de >
]]

local wikibase = {}

function wikibase.setupInterface()
  local title  = require('mw.title')
  local site = require('mw.site')
  local php = mw_interface
  mw_interface = nil
  wikibase.getEntity = function()
    id = php.getEntityId(tostring(title.getCurrentTitle().prefixedText))
    if (id == nil) then return nil end
    entity = php.getEntity(id)
    return entity
  end
  mw = mw or {}
  mw.wikibase = wikibase
  package.loaded['mw.wikibase'] = wikibase
  wikibase.setupInterface = nil
end

return wikibase
