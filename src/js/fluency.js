
var Fluency = Fluency || {}



/**
 * This handles any non field/translation UI manipulations
  * @return {object} Public methods
 */
Fluency.AdminUi = (function() {

  /**
   * Initializes module
   * @return {void}
   */
  var init = function() {
    _convertAdminMenuItemToModal()
  }

  /**
   * Finds and converts the Translation admin menu item to open in a modal
   * rather than navigating to the page.
   * @return {void}
   */
  var _convertAdminMenuItemToModal = function() {
    var adminNavItems = document.querySelectorAll('.pw-masthead .pw-primary-nav > li > a'),
        urlParams = new URLSearchParams(window.location.search)

    // We don't want to modify this menu item if we are on the Fluency config page
    // because the modal behavior is not available in the Admin UI
    if (urlParams.get('name') === 'Fluency' ) return false

    adminNavItems.forEach(function(el, i) {
      var hrefSegments = el.href.split('/').filter(Boolean)

      if (hrefSegments[hrefSegments.length - 1].includes('fluency')) {
        el.href = el.href + '?modal=1'
        el.classList.add('pw-modal-large')
        el.classList.add('pw-modal')
      }
    })
  }

  return {
    init: init
  }
}())





/**
 * This module handles client side functionality for multilanguage (tabbed) fields
 *
 * @return {object} Public interfaces
 */
Fluency.MultilanguageFields = (function() {

  /**
   * This gets all of the multilangInputfieldContainers
   * @type {NodeList}
   */
  var multilangInputfieldContainers = null

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
   * This is the text that is used for the translation trigger
   * This is delivered in the boot data payload
   * @type {String}
   */
  var translateTriggerText = ''

  // Initializes admin interface
  /**
   * Initializes module
   * Will not initialize if there are no multi-language fields on the page
   * @param  {Object} bootData Boot data from module
   * @return {void}
   */
  var init = function(bootData) {
    multilangInputfieldContainers = document.querySelectorAll('.langTabs .LanguageSupport')

    // Set module-wide language boot data vars
    sourceLanguage = bootData.data.languages.source
    targetLanguages = bootData.data.languages.target
    translateTriggerText = bootData.data.translateTriggerText

    if (!_moduleShouldInit()) return false

    _initMultilanguageFields()
    _bindNewMultilanguageFieldsOnInsertion()
  }

  /**
   * Determines if module should initialize
   * - LanguageSupport elements must be present
   * - Target language(s) must be configured and present
   * @return {bool}
   */
  var _moduleShouldInit = function() {
    return multilangInputfieldContainers.length && targetLanguages.length
  }

  /**
   * Master method to initialize fields with triggers for translation
   * @return {void}
   */
  var _initMultilanguageFields = function() {
    for (var inputfieldContainer of multilangInputfieldContainers) {
      _addElementsToMultilangContainer(inputfieldContainer)
    }
  }

  /**
   * Determines if a specified inputfield container has an input that should
   * have translation available. Also prevents locked fields from initializing
   * @param  {HTMLElement} inputfieldContainer
   * @return {bool}
   */
  var _shouldBeTranslatable = function(inputfieldContainer) {
    var noTriggerPresent = !inputfieldContainer.hasAttribute('data-fluency-initialized'),
        hasInputfields = inputfieldContainer.querySelectorAll('input, iframe, textarea').length

    return noTriggerPresent && hasInputfields
  }

  /**
   * This adds all Fluently elements needed for translation to a given
   * element requiring translation triggers and an activity overlay
   * @param {void} inputfieldContainer [description]
   */
  var _addElementsToMultilangContainer = function(inputfieldContainer) {
    if (_shouldBeTranslatable(inputfieldContainer)) {
      _addTranslateTriggers(inputfieldContainer)
      _addActivityOverlays(inputfieldContainer)

      // Add an initialized data attribute so that they aren't initialized again
      inputfieldContainer.setAttribute('data-fluency-initialized', true)
    }
  }

  /**
   * Add translation trigger/notifiers to multilanguage (language tabbed) inputs
   * @return {void}
   */
  var _addTranslateTriggers = function(inputfieldContainer) {
    var inputfieldLanguage = inputfieldContainer.dataset.language,
        targetLanguage = Fluency.Tools.getTargetLanguageById(
                           inputfieldLanguage,
                           targetLanguages
                         )

      // If the PW language matches the source language, add available message
    // If the PW language matches the target language, add trigger
    // Else, add translation not available label
    if (inputfieldLanguage == sourceLanguage.id) {
      _addTranslationLabelToField(
        inputfieldContainer,
        'Translation Service Available'
      )
    } else if (targetLanguage && inputfieldLanguage == targetLanguage.id) {
      _addTranslationTriggerToField(inputfieldContainer)
    } else {
      _addTranslationLabelToField(
        inputfieldContainer,
        'Translation not available for this language'
      )
    }
  }

  /**
   * Adds a label located under the field containing the message passed
   *
   * @param {HTMLElement} inputfieldContainer
   */
  var _addTranslationLabelToField = function(inputfieldContainer, text) {
    // Build an element that will contain the trigger or notifier element to
    // be appended to the inputfield
    var elContainer = document.createElement('div')
    elContainer.setAttribute('class', 'fluency-translate-trigger-container')

    // Build a notifier element
    var messageEl = document.createElement('span')
    messageEl.setAttribute('class', 'fluency-translate-notifier')
    messageEl.textContent = text

    elContainer.appendChild(messageEl)

    // Add the trigger container to the inputfield container element
    inputfieldContainer.appendChild(elContainer)
  }

  /**
   * Adds a translation link trigger to a language field
   * Adds notifier text on the default language to indicate translation is available
   *
   * @param {HTMLElement} langInput Inputfield element
   * @param {void|false}            Nothing if append complets, false if exiting
   *                                without action
   */
  var _addTranslationTriggerToField = function(inputfieldContainer) {
    var targetLanguage,
        elContainer,
        triggerEl

    targetLanguage = Fluency.Tools.getTargetLanguageById(
                       inputfieldContainer.dataset.language,
                       targetLanguages
                     )

    // Build an element that will contain the trigger or notifier element to
    // be appended to the inputfield
    elContainer = document.createElement('div')
    elContainer.setAttribute('class', 'fluency-translate-trigger-container')

    // Build trigger link element
    triggerEl = document.createElement('a')
    triggerEl.setAttribute('href', '#0')
    triggerEl.setAttribute('class', 'fluency-translate-trigger')
    triggerEl.setAttribute('data-fluency-target-language', targetLanguage.deeplCode)
    triggerEl.textContent = translateTriggerText
    // triggerEl.textContent = "Translate from English"

    // Bind the event listener to trigger translation for this field.
    _bindTranslationTrigger(triggerEl, inputfieldContainer)

    // Add the child element created to the trigger container
    elContainer.appendChild(triggerEl)

    // Add the trigger container to the inputfield container element
    inputfieldContainer.appendChild(elContainer)
  }

  /**
   * Binds the translation behavior to a multi language trigger
   * Gets the default language ID from the default language inputfield container el
   * Sends an ajax
   *
   * @param  {HTMLElement} targetInputTrigger     <a> trigger for the field to be translated
   * @param  {HTMLElement} targetInputContainer   Container with field to be translated
   * @return {void}
   */
  var _bindTranslationTrigger = function(targetInputTrigger, targetInputContainer) {
    var langIdSelector = '[data-language="' + sourceLanguage.id + '"]',
        // Get the associated field
        sourceInputContainer = targetInputContainer.parentElement
                                                   .querySelector(langIdSelector),
        sourceInput = sourceInputContainer.querySelector('input, textarea'),
        targetInput = targetInputContainer.querySelector('input, textarea'),
        sourceCkeditor = null,
        targetCkeditor = null

    // Bind a click event to translate
    targetInputTrigger.addEventListener('click', function(e) {
      e.preventDefault()

      var contentToTranslate = null,
          overlay = targetInputContainer.closest('li')
                                        .querySelector('.fluency-activity-overlay')

      // If CKEDITOR exists and an instance exists for the target input
      // then use CKEDITOR to populate the data
      try {
        if (CKEDITOR != undefined && CKEDITOR.instances[targetInput.id]){
          // Get the source field ID from the target field ID by removing
          // the target input ID from the end
          sourceCkeditor = CKEDITOR.instances[targetInput.id.split('__')[0]]
          targetCkeditor = CKEDITOR.instances[targetInput.id]
          contentToTranslate = sourceCkeditor.document.getBody().getHtml()
        }
      } catch (e){
        console.log('No CKEditor found by Fluency')
      }

      if (contentToTranslate === null){
        contentToTranslate = sourceInput.value
      }

      Fluency.Tools.showActivityOverlay(overlay)

      // Set ajax call, get value, insert into destination field
      Fluency.Tools.moduleRequest({
        req: 'translate',
        sourceLanguage: sourceLanguage.deeplCode,
        targetLanguage: this.dataset.fluencyTargetLanguage,
        content: contentToTranslate
      }, function(err, response) {
        var translatedText = response.data.translations[0].text

        // Target input is updated
        Fluency.Tools.updateInputValue(targetInput, translatedText)

        // CKEditor is updated if present
        if (targetCkeditor) {
          targetCkeditor.setData(translatedText)
        }

        Fluency.Tools.hideActivityOverlay(overlay)
      })
    })
  }

  /**
   * Checks if a container should be initialized
   *
   * @param {HTMLElement} langInput Inputfield element
   * @param {bool}                  Boolean
   */
  var _shouldInitializeContainer = function(inputfieldContainer) {
    var isInitialized = inputfieldContainer.classList.contains('fluency-translate-trigger-container'),
        missingInputs = !inputfieldContainer.querySelector('input, textarea'),
        languagePresent = inputfieldContainer.dataset.language

    return (!isInitialized && !missingInputs) && languagePresent
  }

  /**
   * Adds translation activity overlay
   * @return {void}
   */
  var _addActivityOverlays = function() {
    var allMultilangFields = document.querySelectorAll('li.hasLangTabs')

    // Only add an activity overlay if one doesn't exist.
    // Prevents multiple additions when new elements are added via AJAX and
    // this method is called again
    allMultilangFields.forEach(function(el) {
      if (!el.querySelector('.fluency-activity-overlay')) {
        var overlay = Fluency.Tools.createActivityOverlay('translating')

        el.appendChild(overlay)
      }
    })
  }

  /**
   * This sets a mutation observer on the main content area of the edit page so that
   * new elements that are added to the DOM (fields created or loaded through AJAX)
   * which are multilanguage fields will be initialized with translation triggers
   *
   * @return {void}
   */
  var _bindNewMultilanguageFieldsOnInsertion = function() {
    var watchNode = document.getElementById('pw-content-body')

    var onMutation = function(mutationsList, observer) {
      for (var mutation of mutationsList) {
        var inputfieldContainers = mutation.target
                                           .querySelectorAll('.LanguageSupport')

        // Initialize requirements for containers found
        for (var inputfieldContainer  of inputfieldContainers) {
          _addElementsToMultilangContainer(inputfieldContainer)
        }
      }
    }

    var observer = new MutationObserver(onMutation)

    observer.observe(watchNode, {
      childList: true,
      subtree: true
    })
  }

  return {
    init: init
  }
}())



/**
 * This module handles client side functionality for page name (url) fields
 *
 * @return {Object}   public interfaces
 */
Fluency.PageNameFields = (function() {

  /**
   * Holds the page name fields containing element
   * @type {null}
   */
  var pageNameFieldsContainer = null

  /**
   * Holds all of the page name fields for the module
   * @type {null}
   */
  var pageNameFields = null

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
   * Page name fields are not compatible with Chines, Japanese, or Russian
   * language characters and produce upredictable results. These languages will
   * not receive translation triggers.
   * @type {Array}
   */
  var disabledLanguages = [
    'RU',
    'JA',
    'ZH'
  ]

  /**
   * Initializes module
   * Will not initialize if there are no page name fields present or
   * current page is home page (id=1)
   *
   * @param  {Object} bootData Boot data from module
   * @return {void}
   */
  var init = function(bootData) {
    pageNameFieldsContainer = document.querySelector('.InputfieldPageName')
    // We don't want to insert these on the home page. Those should be unique and
    // descriptive base URLs
    if (Fluency.Tools.pageIdIs(1) || !pageNameFieldsContainer) return false

    pageNameFields = pageNameFieldsContainer.querySelectorAll('.LanguageSupport')

    sourceLanguage = bootData.data.languages.source
    targetLanguages = bootData.data.languages.target

    _addTranslateTriggers(pageNameFields)
    _addActivityOverlays(pageNameFields)
  }

  /**
   * Add activity overlays to page name inputs
   *
   * @param {NodeList} pageNameFields All page name fields
   */
  var _addActivityOverlays = function(pageNameFields) {
    // Start iterator at 1 to skip over first default language field which
    // does not need an overlay
    for (var i = 1; i < pageNameFields.length; i++) {
      var thisContainer = pageNameFields[i]

      thisContainer.classList.add('fluency-overlay-container')

      var activityOverlay = Fluency.Tools.createActivityOverlay('translating')

      thisContainer.appendChild(activityOverlay)
    }
  }

  /**
   * Adds translation trigger to multilanguage listed fields
   * Ex. page names on settings tab
   *
   * @param {NodeList} pageNameFields All page name fields
   */
  var _addTranslateTriggers = function(pageNameFields) {
    // This iterator starts at 1 to skip over the first language field which
    // is the default language since it shouldn't have any message or trigger
    for (var i = 1; i < pageNameFields.length; i++) {
      var thisContainer = pageNameFields[i],
          targetInput = thisContainer.querySelector('input'),
          targetLanguagePwId = null,
          targetLanguage = null

      // Get the PW language ID from the field's name
      targetLanguagePwId = targetInput.name.replace('_pw_page_name', '')

      // Get the DeepL language code from the target languages data
      // If the language is not configured in Fluency, then this will return null
      targetLanguage = Fluency.Tools.getTargetLanguageById(
                         parseInt(targetLanguagePwId),
                         targetLanguages
                       )

      // Set message for incompatible fields
      // Set message for fields that are not configured
      if(targetLanguage && disabledLanguages.includes(targetLanguage.deeplCode)) {
        _addTranslationLabelToField(thisContainer, 'Translation not available for page names in this language')
        continue
      } else if (!targetLanguage) {
        _addTranslationLabelToField(thisContainer, 'Translation not available for this language')
        continue
      }

      // Build an element that will contain the trigger or notifier element to
      // be appended to the inputfield
      var elContainer = document.createElement('div')
          elContainer.setAttribute('class', 'fluency-translate-trigger-container')

      // Build trigger link element
      var triggerEl = document.createElement('a')
          triggerEl.setAttribute('href', '#0')
          triggerEl.setAttribute('class', 'fluency-translate-trigger')
          triggerEl.setAttribute('data-fluency-target-language', targetLanguage.deeplCode)
          triggerEl.textContent = "Translate from English"

      _bindTranslationTrigger(triggerEl, targetInput)

      // Add the child element created to the trigger container
      elContainer.appendChild(triggerEl)

      // Add the trigger container to the inputfield container element
      thisContainer.appendChild(elContainer)
    }
  }

  /**
   * Binds the translation behavior to a page name trigger
   * @param  {HTMLElement} triggerEl   Translation trigger
   * @param  {HTMLElement  targetInput Destination field for translated text
   * @return {void}
   */
  var _bindTranslationTrigger = function(triggerEl, targetInput) {
    triggerEl.addEventListener('click', function(e) {
      var sourceValue = _getSourceTranslationValue(),
          overlay = triggerEl.closest('.LanguageSupport')
                             .querySelector('.fluency-activity-overlay')

      Fluency.Tools.showActivityOverlay(overlay)

      // Set ajax call, get value, insert into destination field
      Fluency.Tools.moduleRequest({
        req: 'translate',
        sourceLanguage: sourceLanguage.deeplCode,
        targetLanguage: triggerEl.getAttribute('data-fluency-target-language'),
        content: sourceValue
      }, function(err, response) {
        var translatedText = response.data.translations[0].text

        Fluency.Tools.updateInputValue(targetInput, translatedText)
        Fluency.Tools.hideActivityOverlay(overlay)
      })
    })
  }

  /**
   * Gets the source page name
   * @return {string} Page name with dashes removed for translation
   */
  var _getSourceTranslationValue = function() {
    // Use querySelector to get first hit
    var sourceField = pageNameFieldsContainer.querySelector('.LanguageSupport input')

    return sourceField.value.replace(/-/g, ' ')
  }

  /**
   * This adds a message that the language is not compatible with URLs
   * @param {HTMLElement} container Container that gets the message
   * @param {string}      Text for label
   */
  var _addTranslationLabelToField = function(container, text) {
    var msgContainer = document.createElement('div')
        msgContainer.setAttribute('class', 'fluency-translate-trigger-container')

    // Build a notifier element
    var childEl = document.createElement('span')
    childEl.setAttribute('class', 'fluency-translate-notifier')
    childEl.textContent = text

    msgContainer.appendChild(childEl)

    // Add the child element created to the parent container
    container.appendChild(msgContainer)
  }

  return {
    init: init
  }
}())

// Initialize all modules when the DOM is ready
window.addEventListener('load', function(e) {
  Fluency.AdminUi.init()

  Fluency.Tools.moduleRequest({req: 'getBootData'}, function(err, bootData) {
    Fluency.MultilanguageFields.init(bootData)
    Fluency.PageNameFields.init(bootData)
  })
})
