# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# module for time page object

module TimePage
  include PageObject
  # time UI elements
  div(:timeCalendarHint, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-expert-timeinput-calendarhint')]")
  span(:timeCalendarHintMessage, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-expert-timeinput-calendarhint')]/span[contains(@class, 'valueview-expert-timeinput-calendarhint-message')]")
  link(:timeCalendarHintSwitch, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-expert-timeinput-calendarhint')]/span[contains(@class, 'valueview-expert-timeinput-calendarhint-switch')]")
  link(:timeInputExtenderAdvanced, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/a[contains(@class, 'valueview-expert-timeinput-advancedtoggler')]")
  div(:timePrecision, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-expert-timeinput-precisioncontainer')]")
  link(:timePrecisionRotatorAuto, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-expert-timeinput-precisioncontainer')]/div[contains(@class, 'valueview-expert-timeinput-precision')]/a[contains(@class, 'ui-listrotator-auto')]")
  link(:timePrecisionRotatorPrev, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-expert-timeinput-precisioncontainer')]/div[contains(@class, 'valueview-expert-timeinput-precision')]/a[contains(@class, 'ui-listrotator-prev')]")
  link(:timePrecisionRotatorNext, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-expert-timeinput-precisioncontainer')]/div[contains(@class, 'valueview-expert-timeinput-precision')]/a[contains(@class, 'ui-listrotator-next')]")
  link(:timePrecisionRotatorSelect, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-expert-timeinput-precisioncontainer')]/div[contains(@class, 'valueview-expert-timeinput-precision')]/a[contains(@class, 'ui-listrotator-curr')]")
  div(:timeCalendar, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-expert-timeinput-calendarcontainer')]")
  link(:timeCalendarRotatorAuto, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-expert-timeinput-calendarcontainer')]/div[contains(@class, 'ui-listrotator')]/a[contains(@class, 'ui-listrotator-auto')]")
  link(:timeCalendarRotatorPrev, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-expert-timeinput-calendarcontainer')]/div[contains(@class, 'ui-listrotator')]/a[contains(@class, 'ui-listrotator-prev')]")
  link(:timeCalendarRotatorNext, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-expert-timeinput-calendarcontainer')]/div[contains(@class, 'ui-listrotator')]/a[contains(@class, 'ui-listrotator-next')]")
  link(:timeCalendarRotatorSelect, :xpath => "//div[contains(@class, 'ui-inputextender-extension')]/div[contains(@class, 'valueview-expert-timeinput-calendarcontainer')]/div[contains(@class, 'ui-listrotator')]/a[contains(@class, 'ui-listrotator-curr')]")
  unordered_list(:timePrecisionMenu, :class => "ui-listrotator-menu", :index => 0)
  unordered_list(:timeCalendarMenu, :class => "ui-listrotator-menu", :index => 1)
  # methods
  def select_time_precision prec
    self.show_advanced_time_settings
    if prec == "auto"
      self.timePrecisionRotatorAuto
      return
    end
    self.timePrecisionRotatorSelect
    self.timePrecisionMenu_element.when_visible
    self.timePrecisionMenu_element.each do |item|
      if item.text == prec
        item.click
        return
      end
    end
  end

  def select_calendar cal
    self.show_advanced_time_settings
    if cal == "auto"
      self.timeCalendarRotatorAuto
      return
    end
    self.timeCalendarRotatorSelect
    self.timeCalendarMenu_element.when_visible
    self.timeCalendarMenu_element.each do |item|
      if item.text == cal
        item.click
        return
      end
    end
  end

  def show_advanced_time_settings
    if !self.timePrecision_element.visible?
      self.timeInputExtenderAdvanced
      self.timePrecision_element.when_visible
    end
  end

  def wait_for_time_request
    wait_until do
      previewSpinner? == false
    end
  end
end
