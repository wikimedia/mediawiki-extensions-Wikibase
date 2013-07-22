# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for history page

class HistoryPage
  include PageObject

  h1(:mwFirstHeading, :id => "firstHeading")

  link(:historyLink, :xpath => "//li[@id='ca-history']/span/a")
  link(:rollbackLink, :css => "span.mw-rollback-link > a")
  link(:returnToItemLink, :css => "p#mw-returnto > a")

  link(:undo1, :css => "ul#pagehistory > li:nth-child(1) > span.mw-history-undo > a")
  link(:undo2, :css => "ul#pagehistory > li:nth-child(2) > span.mw-history-undo > a")
  link(:undo3, :css => "ul#pagehistory > li:nth-child(3) > span.mw-history-undo > a")
  link(:undo4, :css => "ul#pagehistory > li:nth-child(4) > span.mw-history-undo > a")
  link(:undo5, :css => "ul#pagehistory > li:nth-child(5) > span.mw-history-undo > a")
  link(:undo6, :css => "ul#pagehistory > li:nth-child(6) > span.mw-history-undo > a")
  link(:undo7, :css => "ul#pagehistory > li:nth-child(7) > span.mw-history-undo > a")
  link(:undo8, :css => "ul#pagehistory > li:nth-child(8) > span.mw-history-undo > a")
  link(:undo9, :css => "ul#pagehistory > li:nth-child(9) > span.mw-history-undo > a")

  link(:oldrevision1, :css => "ul#pagehistory > li:nth-child(1) > a.mw-changeslist-date")
  link(:oldrevision2, :css => "ul#pagehistory > li:nth-child(2) > a.mw-changeslist-date")
  link(:oldrevision3, :css => "ul#pagehistory > li:nth-child(3) > a.mw-changeslist-date")

  radio_button(:oldrevision1oldidradio, :xpath => "//ul[@id='pagehistory']/li[1]/input[1]")
  radio_button(:oldrevision2oldidradio, :xpath => "//ul[@id='pagehistory']/li[2]/input[1]")
  radio_button(:oldrevision3oldidradio, :xpath => "//ul[@id='pagehistory']/li[3]/input[1]")
  radio_button(:oldrevision1diffradio, :xpath => "//ul[@id='pagehistory']/li[1]/input[2]")
  radio_button(:oldrevision2diffradio, :xpath => "//ul[@id='pagehistory']/li[2]/input[2]")
  radio_button(:oldrevision3diffradio, :xpath => "//ul[@id='pagehistory']/li[3]/input[2]")

  button(:comparerevisions, :xpath => "//input[contains(@class, 'mw-history-compareselectedversions-button')]")

  span(:autocomment1, :xpath => "//ul[@id='pagehistory']/li[1]/span[contains(@class, 'comment')]/span/span[contains(@class, 'autocomment')]")
  span(:autosummary1, :xpath => "//ul[@id='pagehistory']/li[1]/span[contains(@class, 'comment')]/span")

  link(:curLink4, :css => "ul#pagehistory > li:nth-child(4) > span.mw-history-histlinks > a")
  link(:oldRevision5, :css => "ul#pagehistory > li:nth-child(5) > a")
  link(:restoreLink, :css => "div#mw-diff-otitle1 > strong > a:nth-child(2)")

  unordered_list(:pageHistory, :id => "pagehistory")
  button(:undoSave, :id => "wpSave")
  button(:compareRevisionsButton, :class => "mw-history-compareselectedversions-button")
  element(:undoDel, :del, :class => "diffchange")
  element(:undoIns, :ins, :class => "diffchange")
  element(:undoDelTitle, :td, :class => "diff-lineno", :index => 0)
  element(:undoInsTitle, :td, :class => "diff-lineno", :index => 1)
  def navigate_to_item_history
    historyLink
    sleep 1
  end

  def count_revisions
    count = 0
    pageHistory_element.each do |revision|
      count = count+1
    end
    return count
  end

end
