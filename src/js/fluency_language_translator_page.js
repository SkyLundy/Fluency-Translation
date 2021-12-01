
var Fluency = Fluency || {}



/**
 * This module handles client side functionality for page name (url) fields
 *
 * @return {Object}   public interfaces
 */
Fluency.LanguageTranslatorPage = (function() {

  /**
   * Object containing source language data from ajax call
   * @type {Object}
   */
  var sourceLanguage = {}

  /**
   * Array containing objects with data for all target languages
   * @type {Array}
   */
  var targetLanguages = []

  /**
   * The target language DeepL code for this language page
   * @type {string|null}
   */
  var targetLanguage = null

  /**
   * Holds the UI text returned from the module on boot. Used to build the
   * client interface
   * @type {Object}
   */
  var uiText = {}

  /**
   * Initializes module
   * Will not initialize if there's no language ID that can be pulled from the
   * URL under the language_id parameter
   *
   * @param  {Object} bootData Boot data from module
   * @return {void}
   */
  var init = function(bootData) {
    sourceLanguage = bootData.languages.source
    targetLanguages = bootData.languages.target
    uiText = bootData.ui.text
    targetLanguage= _getTargetLanguage()

    var inputfields = document.getElementById('pw-content-body')
                              .querySelectorAll('.Inputfields .Inputfield')

    // If there is a language present and configured then add overlays &
    // triggers.
    // If not, then add a "translation not available for this message" label
    if (targetLanguage) {
      _addActivityOverlays(inputfields)
      _addTranslateTriggers(inputfields)
    } else {
      _addTranslationNotAvailableLabels(inputfields)
    }
  }

  /**
   * Gets the language ID from the current URL
   * @return {int|null} Language ID number, null if not present
   */
  var _getTargetLanguage = function() {
    var urlParams = new URLSearchParams(window.location.search),
        id = parseInt(urlParams.get('language_id'))

    return id ? Fluency.Tools.getTargetLanguageById(id, targetLanguages) : null
  }

  /**
   * Add activity overlays to page name inputs
   *
   * @param {NodeList} inputfields All page name fields
   */
  var _addActivityOverlays = function(inputfields) {
    // Start iterator at 1 to skip over first default language field which
    // does not need an overlay
    for (var i = 1; i < inputfields.length; i++) {
      var thisContainer = inputfields[i]

      thisContainer.classList.add('fluency-overlay-container')

      var activityOverlay = Fluency.Tools.createActivityOverlay('translating')

      thisContainer.appendChild(activityOverlay)
    }
  }

  /**
   * Adds translation trigger to multilanguage listed fields
   * Ex. page names on settings tab
   *
   * @param {NodeList} inputfields All page name fields
   */
  var _addTranslateTriggers = function(inputfields) {
    for (var i = 1; i < inputfields.length; i++) {
      var thisContainer = inputfields[i],
          containerContent = thisContainer.querySelector('.InputfieldContent')

      // Build an element that will contain the trigger or notifier element to
      // be appended to the inputfield
      var triggerContainer = document.createElement('div')
          triggerContainer.setAttribute('class', 'fluency-translate-trigger-container')

      // Build trigger link element
      var triggerEl = document.createElement('a')
          triggerEl.setAttribute('href', '#0')
          triggerEl.setAttribute('class', 'fluency-translate-trigger')
          triggerEl.textContent = uiText.translateTrigger
          // triggerEl.textContent = "Translate from English"

      // Add the child element created to the trigger container
      // Add the trigger container to the inputfield container element
      // Bind it and run
      triggerContainer.appendChild(triggerEl)
      containerContent.appendChild(triggerContainer)
      _bindTranslationTrigger(triggerEl)
    }
  }

  /**
   * Binds the translation behavior to a page name trigger
   * @param  {HTMLElement} triggerEl   Translation trigger
   * @param  {HTMLElement  targetInput Destination field for translated text
   * @return {void}
   */
  var _bindTranslationTrigger = function(triggerEl) {
    triggerEl.addEventListener('click', function(e) {
      var parentEl = triggerEl.closest('.Inputfield'),
          overlay = parentEl.querySelector('.fluency-activity-overlay'),
          content = parentEl.querySelector('.description').textContent,
          targetInput = parentEl.querySelector('input') ?? parentEl.querySelector('textarea')

      Fluency.Tools.showActivityOverlay(overlay)

      // Set ajax call, get value, insert into destination field
      Fluency.Tools.moduleRequest({
        req: 'translate',
        sourceLanguage: sourceLanguage.deeplCode,
        targetLanguage: targetLanguage.deeplCode,
        content: content
      }, function(err, response) {
        var translatedText = response.data.translations[0].text

        targetInput.value = translatedText

        Fluency.Tools.hideActivityOverlay(overlay)
      })
    })
  }

  /**
   * Adds a label located under the field containing the message passed
   *
   * @param {HTMLElement} inputfieldContainer
   */
  var _addTranslationNotAvailableLabels = function(inputfieldContainers) {
    for (var i = 1; i < inputfieldContainers.length; i++) {
      var thisinputfieldContainer = inputfieldContainers[i].querySelector('.InputfieldContent')

      // Build an element that will contain the trigger or notifier element to
      // be appended to the inputfield
      var elContainer = document.createElement('div')
      elContainer.setAttribute('class', 'fluency-translate-trigger-container')

      // Build a notifier element
      var messageEl = document.createElement('span')
      messageEl.setAttribute('class', 'fluency-translate-notifier')
      messageEl.textContent = uiText.translationNotAvailable

      elContainer.appendChild(messageEl)

      // Add the trigger container to the inputfield container element
      thisinputfieldContainer.appendChild(elContainer)
    }
  }

  return {
    init: init
  }
}())





window.addEventListener('load', function(e) {
  Fluency.LanguageTranslatorPage.init(ProcessWire.config.fluencyTranslation.bootData)
})
