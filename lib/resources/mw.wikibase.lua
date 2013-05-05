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
    @author Thomas Pellissier Tanon
]]

local wikibase = {}

function wikibase.setupInterface()
  local php = mw_interface
  mw_interface = nil

  wikibase.getEntity = function( id )
    if id == nil then
      id = php.getEntityId( tostring( mw.title.getCurrentTitle().prefixedText ) )
    elseif php.getGlobalSiteId() ~= nil then -- Hack to disallow clients to get items
      error( "You are not allowed to get currently an other item than the item linked to he current page", 2 )
    end
    if id == nil then
      return nil
    end
    return php.getEntity( id )
  end

  wikibase.label = function( id )
    local code = mw.language.getContentLanguage():getCode()
    if code == nil then
      return nil
    end

    local entity = php.getEntity( id )
    if entity == nil or entity.labels == nil then
      return nil
    end

    local label = entity.labels[code]
    if label == nil then
      return nil
    end

    return label.value
  end

  wikibase.sitelink = function( id )
    local globalSiteId = php.getGlobalSiteId()
    if globalSiteId == nil then
      return nil
    end

    local entity = php.getEntity( id )
    if entity == nil or entity.sitelinks == nil then
      return nil
    end

    local sitelink = entity.sitelinks[globalSiteId]
    if sitelink == nil then
      return nil
    end
    return sitelink.title
  end

  mw = mw or {}
  mw.wikibase = wikibase
  package.loaded['mw.wikibase'] = wikibase
  wikibase.setupInterface = nil
end

return wikibase