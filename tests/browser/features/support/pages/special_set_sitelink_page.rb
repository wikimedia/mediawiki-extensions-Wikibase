# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Katie Filbert < aude.wiki@gmail.com >
# License:: GNU GPL v2+
#
# page object for special set site link page

class SpecialSetSitelinkPage < SpecialModifyTermPage
  include PageObject

  page_url URL.repo_url("Special:SetSiteLink")

  text_field(:site_id_input_field, :id => "wb-setsitelink-site")
  text_field(:page_input_field, :id => "wb-setsitelink-page")

  def navigate_to_page_with_item_id id
    navigate_to URL.repo_url("Special:SetSiteLink/" + id)
  end
end
