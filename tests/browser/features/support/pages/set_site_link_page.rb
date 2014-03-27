# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Katie Filbert < aude.wiki@gmail.com >
# License:: GNU GPL v2+
#
# page object for special set site link page

class SetSiteLinkPage
  include PageObject
  page_url URL.repo_url("Special:SetSiteLink")

  text_field(:set_site_link_entity_id_input_field, :id => "wb-modifyentity-id")
  text_field(:set_site_link_site_id_input_field, :id => "wb-setsitelink-site")
  text_field(:set_site_link_page_input_field, :id => "wb-setsitelink-page")

end
