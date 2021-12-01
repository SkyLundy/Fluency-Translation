// This is the admin page module.
// Should only be included/initialized on the Fluency admin page

var Fluency = Fluency || {}

/**
 * Admin module that handles all /{admin slug}/fluency actions
 * @return {object}   Exposes public methods
 */
Fluency.Admin = (function() {

  /**
   * Holds cached HTML elements for the translator tool accessible module-wide
   * @type {Object}
   */
  var translatorEls = {}

  /**
   * Holds cached HTML elements for the API usage table accessible module-wide
   * @type {Object}
   */
  var usageTableEls = {}

  /**
   * Initializes module
   * @return {void}
   */
  var init = function() {
    _cacheEls()
    _initTranslator()
    _addTargetContentClickToCopy()
    _initUsageTable()
  }

  /**
   * Treverses DOM and caches elements for use throughout module.
   * @return void
   */
  var _cacheEls = function() {
    var translatorFieldset = document.querySelector('.fluency-translator-fieldset');

    translatorEls.fieldset = translatorFieldset
    translatorEls.sourceLang = translatorFieldset.querySelector('.fluency-source-lang')
    translatorEls.targetLanguage = translatorFieldset.querySelector('.fluency-target-lang')
    translatorEls.sourceContent = translatorFieldset.querySelector('.fluency-source-content')
    translatorEls.targetContent = translatorFieldset.querySelector('.fluency-target-content')
    translatorEls.translateButton = translatorFieldset.querySelector('.js-fluency-translate')
    translatorEls.overlay = translatorFieldset.querySelector('.fluency-activity-overlay')

    // Usage table
    var usageFieldset = document.querySelector('.fluency-api-usage-fieldset')

    usageTableEls.fieldset = usageFieldset
    usageTableEls.limitCell = usageFieldset.querySelector('.fluency-usage-limit')
    usageTableEls.translatedCell = usageFieldset.querySelector('.fluency-usage-translated')
    usageTableEls.availableCell = usageFieldset.querySelector('.fluency-usage-available')
    usageTableEls.usageCell = usageFieldset.querySelector('.fluency-usage-total')
    usageTableEls.refreshButton = usageFieldset.querySelector('.InputfieldButtonLink')
    usageTableEls.overlay = usageFieldset.querySelector('.fluency-activity-overlay')
  }

  /**
   * Controlling method to initialize the translator
   * @return {void}
   */
  var _initTranslator = function()  {
    _bindButtonTranslate()
    _bindClearFormErrorOnFocus()
  }

  /**
   * Binds the translate action to the translate button
   * @return {void}
   */
  var _bindButtonTranslate = function() {
    translatorEls.translateButton.addEventListener('click', function(e) {
      e.preventDefault()

      if (_formHasErrors()) {
        _updateFormErrors()

        return false
      }

      _translateContent()
    })
  }

  /**
   * Gets the language selections and content from the page
   * @return {Object} Contains the source language and content as well as the
   *                  target language
   */
  var _getTranslatorUserInputData = function() {
    return {
      sourceLanguage: translatorEls.sourceLang.value,
      content: translatorEls.sourceContent.value,
      targetLanguage: translatorEls.targetLanguage.value
    }
  }

  /**
   * Check if the translation form has errors
   * @return {boolean}
   */
  var _formHasErrors = function() {
    var input = _getTranslatorUserInputData()

    return Object.values(input).filter(Boolean).length < Object.keys(input).length
  }

  var _bindClearFormErrorOnFocus = function() {
    var requiredFields = [
      translatorEls.sourceLang,
      translatorEls.sourceContent,
      translatorEls.targetLanguage
    ]

    function clearError(e) {
      e.target.closest('.Inputfield').classList.remove('InputfieldStateError')
    }

    requiredFields.forEach(function(field, i) {
      field.addEventListener('focus', clearError)
      field.addEventListener('change', clearError)
    })
  }

  /**
   * Sets/removes errors on required fields with missing values
   * @return {void}
   */
  var _updateFormErrors = function() {
    var requiredFields = [
      translatorEls.sourceLang,
      translatorEls.sourceContent,
      translatorEls.targetLanguage
    ]

    requiredFields.forEach(function(field, i) {
      var fieldClassList = field.closest('.Inputfield').classList,
          errorClass = 'InputfieldStateError'

      !field.value ? fieldClassList.add(errorClass) : fieldClassList.remove(errorClass)
    })
  }

  /**
   * Executes the translation for the translator feature
   * @return {void}
   */
  var _translateContent = function() {
    var userInputData = _getTranslatorUserInputData()

    Fluency.Tools.showActivityOverlay(translatorEls.overlay)

    Fluency.Tools.moduleRequest({
      req: 'translate',
      sourceLanguage: userInputData.sourceLanguage,
      content: userInputData.content,
      targetLanguage: userInputData.targetLanguage
    }, function(err, response) {
      if (response.httpStatus === 200) {
        _updateTranslator(response.data)
      }
    })
  }

  /**
   * Updates translator with content after translation
   * @param  {object} data
   * @return {void}
   */
  var _updateTranslator = function(data) {
    translatorEls.targetContent.value = data.translations[0].text

    Fluency.Tools.hideActivityOverlay(translatorEls.overlay)
  }

  /**
   * Controlling method to initialize the usage table
   * Note: This data is not loaded with the page to reduce API calls and speed
   *       rendering/JS times.
   * @return {void}
   */
  var _initUsageTable = function() {
   _bindButtonUsageRefresh()
  }

  /**
   * Binds the click action for the translation usage
   * @return {void}
   */
  var _bindButtonUsageRefresh = function() {
    usageTableEls.refreshButton.addEventListener('click', function(e) {
      e.preventDefault()

      Fluency.Tools.showActivityOverlay(usageTableEls.overlay)

      Fluency.Tools.moduleRequest({req: 'usage'}, function(err, response) {
        if (response.httpStatus === 200) {
          _updateUsageTable(response.data)
        }
      })
    })
  }

  /**
   * Updates the API usage table
   * @param  {object} data API return data with DeepL usage/limit data
   * @return {void}
   */
  var _updateUsageTable = function(data) {
    var limit = data.character_limit,
        count = data.character_count

    // Insert formatted data into cells
    usageTableEls.limitCell.innerHTML = limit.toLocaleString()
    usageTableEls.translatedCell.innerHTML = count.toLocaleString()
    usageTableEls.availableCell.innerHTML = (limit - count).toLocaleString()
    usageTableEls.usageCell.innerHTML = Math.round((count / limit) * 100) + '%'

    Fluency.Tools.hideActivityOverlay(usageTableEls.overlay)
  }

  /**
   * Adds a click to copy behavior when hovering over the target content field
   * @return {void}
   */
  var _addTargetContentClickToCopy = function() {
    var inputfieldParent = translatorEls.targetContent.closest('.Inputfield')

    inputfieldParent.classList.add('fluency-copy-trigger-container')

    inputfieldParent.addEventListener('click', function(e) {
      e.preventDefault()

      translatorEls.targetContent.select()
      document.execCommand('copy')
      translatorEls.targetContent.blur()
      this.classList.add('copied')

      setTimeout(function() {
        inputfieldParent.classList.remove('copied')
      }, 2000)
    })
  }

  return {
    init:init
  }
}())





window.addEventListener('load', Fluency.Admin.init)
