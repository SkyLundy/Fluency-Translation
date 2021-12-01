<?php namespace ProcessWire;

// FileCompiler=0

require_once 'classes/FluencyTools.class.php';
require_once 'classes/FluencyLocalization.class.php';
require_once 'engines/DeepL.class.php';

/**
 * Master module class for Fluency
 */
class Fluency extends Process implements Module {

  /**
   * Holds DeepL class instance
   * Public so that the DeepL object can be called directly outside of the module
   * @var DeepL
   */
  public $deepL;

  /**
   * This holds an instance of FluencyLocalization
   * This is the class that handles auto-localization of the module and the UI.
   * @var FluencyLocalization
   */
  public $fluencyLocalization;

  /**
   * Name/slug of the Fluency admin page
   * @var string
   */
  // private $adminPageName = 'fluency';

  /**
   * This is assigned an instance of fluencyTools when module is ready
   * @var FluencyTools
   */
  private $fluencyTools;

  /**
   * Languages that cannot be used for translating page names (URLs)
   * @var array
   */
  private $urlInvalidLanguages = [
    'RU',
    'JA',
    'ZH'
  ];

  /**
   * Executes module when PW is ready
   * @return void
   */
  public function ready() {
    // Work with this?
    $this->config->js('fluencyTranslation', [
      'test' => 'fired'
    ]);

    $this->fluencyTools = new FluencyTools;

    $deepL = new DeepL([
      'apiKey' => $this->deepl_api_key,
      'accountType' => $this->deepl_account_type
    ]);

    $this->deepL = $deepL;
    $this->fluencyLocalization = new FluencyLocalization($deepL);

    if (!$this->moduleShouldInit()) {
      return false;
    }

    // Pass boot data to JS
    $this->config->js('fluencyTranslation', [
      'bootData' => [
        'data' => $this->getClientBootData()
      ]
    ]);

    // CSS/JS assets
    $this->insertAssets();
  }

  /**
   * Checks to see if the module should initialize
   * - API key must be present and valid
   *
   * @return bool
   */
  private function moduleShouldInit(): bool {
    $moduleConfig = $this->modules->getModuleConfigData('Fluency');

    return $this->page->name !== 'login' &&
           $this->deepl_api_key &&
           $this->user->hasPermission('fluency-translate') &&
           (isset($moduleConfig['api_key_valid']) && $moduleConfig['api_key_valid'] === true) &&
           (isset($moduleConfig['deepl_account_type']));
  }

  /**
   * Inserts required assets into admin pages on load.
   *
   * @return void
   */
  private function insertAssets(): void {
    $moduleCssPath = "{$this->urls->$this}src/css/";
    $moduleJsPath = "{$this->urls->$this}src/js/";

    // Include common styles/JS used on every page
    $this->config->styles->add("{$moduleCssPath}fluency.css");
    $this->config->scripts->add("{$moduleJsPath}fluency_tools.js");

    // This switches out scripts that are needed for certain pages and not others
    switch ($this->page->name) {
      case 'fluency':
        // If on the Fluency custom admin page
        $this->config->styles->add("{$moduleCssPath}fluency_admin.css");
        $this->config->scripts->add("{$moduleJsPath}fluency_admin.js");
        break;
      case 'language-translator':
        // On the static text translation pages
        $this->config->scripts->add("{$moduleJsPath}fluency_language_translator_page.js");
        break;
      case 'module':
        // Check URL to see that we are in the Fluency module config
        if ($this->input->get->name === 'Fluency') {
          $this->config->styles->add("{$moduleCssPath}fluency_processwire_module_config.css");
          $this->config->scripts->add("{$moduleJsPath}fluency_tools.js");
          $this->config->scripts->add("{$moduleJsPath}fluency_processwire_module_config.js");
        }
        break;
      default:
        // Everywhere else
        $this->config->scripts->add("{$moduleJsPath}fluency.js");
        break;
    }
  }

  /**
   * Gets all language data for every language installed in ProcessWire and their
   * DeepL associations. Organized by a source array and a target array
   * Note: This is intended for use as an AJAX return value for UI build use.
   *       It does not return a list of the languages that DeepL allows to translate
   *       from. The source here is the default language which is used to translate
   *       from as configured.
   *
   * @return object Each langauge with associated data
   */
  private function getConfiguredLanguageData(): object {
    $languageData = [];

    // Iterate through all languages and package for front end consumption
    foreach ($this->languages as $language) {
      $pwLanguageId = $language->id;
      $pwLanguageName = $language->name;

      // Pull the translator data configured for this language
      $configVar = "pw_language_{$pwLanguageId}";
      $translatorLangConfigData = $this->$configVar;

      // We only want to return languages that have been configured
      if (!$translatorLangConfigData) continue;

      $translatorLangConfigData = (object) json_decode($translatorLangConfigData);
      $isDefaultLanguage = $language->name === 'default';

      // Set source language data
      if ($isDefaultLanguage) {
        $languageTitle = $language->title;

        // Check if this is a LanguagesPageFieldValue object.
        // $language->title returns different objects depending on context.
        if (!is_string($languageTitle)) {
          $languageTitle = $language->title->getLanguageValue($this->user->language);
        }

        $languageData['source'] = (object) [
          'processWire' => (object) [
            'id' => $pwLanguageId,
            'name' => $pwLanguageName,
            'title' => $languageTitle,
          ],
          'translator' => $translatorLangConfigData
        ];
      }

      // Set target language data
      if (!$isDefaultLanguage) {
        $urlValid = !in_array($translatorLangConfigData->language, $this->urlInvalidLanguages);

        $languageData['target'][] = (object) [
          'processWire' => (object) [
            'id' => $pwLanguageId,
            'name' => $pwLanguageName,
            'title' => $language->title,
            'urlValid' => $urlValid
          ],
          'translator' => $translatorLangConfigData
        ];
      }
    }

    return (object) $languageData;
  }

  /**
   * Gets the text for rendering the UI
   * @return object
   */
  private function getLocalizations(): object {
    $localizationDir = __DIR__ . '/module_localizations/';
    $contentTranslation = require_once "{$localizationDir}/content_translation.php";
    $translationTool = require_once "{$localizationDir}/translation_tool.php";

    return (object) [
      'clientRendered' => $contentTranslation($this),
      'translationTool' => $translationTool()
    ];
  }

  /**
   * Returns an array of PW language IDs and associated ISO codes
   * @param  array $additions This allows for adding custom associations for languages
   *                          not configured in Fluency
   * @return array
   */
  private function getLanguageIdIsoAssociations(): array {
    $configuredLanguages = $this->getConfiguredLanguageData();
    $sourceLanguage = $configuredLanguages->source;
    $targetLanguages = $configuredLanguages->target;
    $isoCodesById = [];

    // Add id/iso code for source language
    $isoCodesById[$sourceLanguage->processWire->id] = $sourceLanguage->translator->language;

    // Add id/iso codes for source languages
    foreach ($configuredLanguages->target as $targetLanguage) {
      $isoCodesById[$targetLanguage->processWire->id] = $targetLanguage->translator->language;
    }

    return $isoCodesById;
  }

  ////////////////////
  // Module Actions //
  ////////////////////

  /**
   * This returns a data payload needed by the client UI JS to add translation
   * triggers and interactivity to fields in admin pages.
   *
   * @return array Array with all boot data needed by client UI scripts.
   */
  public function getClientBootData(): object {
    return (object) [
      'languages' => $this->getConfiguredLanguageData(),
      'ui' => (object) [
        'text' => $this->fluencyLocalization->get('pageEditor')
        // 'text' => $this->getLocalizations()->clientRendered
      ]
    ];
  }

  /**
   * Handle translation requests
   *
   * @param  string       $sourceLangCode  2 letter language shortcode translating from
   * @param  string|array $content         Can be a string or an array of strings
   * @param  string       $targetLangCode  2 letter language shortcode translating to
   * @param  array        $addApiParams    Additional DeepL API parameters
   * @param  array        $addApiParams    Additional DeepL API parameters
   * @param  array        $ignoredStrings  Strings to not translate (Merged with CMS config'd strings)
   * @return object                        Fluency  module response object
   */
  public function translate(
    string $sourceLangCode,
    $content,
    string $targetLangCode,
    $opts = []
    // array  $addApiParams = [],
    // ?array $ignoredStrings = []
  ): object {

    // Get configured non-translated strings and merge with passed array
    if ($this->non_translated_strings) {
      $ignoredStrings = $opts['ignoredStrings'] ?? [];

      $configIgnoredStrings = explode(',', $this->non_translated_strings);
      $configIgnoredStrings = array_map('trim', $configIgnoredStrings);

      $opts['ignoredStrings'] = array_merge($configIgnoredStrings, $ignoredStrings);
    }

    // Configure additional parameters
    if (!!$this->api_param_preserve_formatting) {
      $addParams = $opts['addParams'] ?? [];

      $opts['addParams'] = array_merge([
        'preserve_formatting' => $this->api_param_preserve_formatting
      ], $addParams);
    }


    // Translate and get value
    $result = $this->deepL->translate(
      $sourceLangCode,
      $content,
      $targetLangCode,
      $opts
    );

    return $result;
  }

  /**
   * Gets the current API usage relative to the limits configured in your DeepL
   * developer account
   * @return object
   */
  public function apiUsage(): object {
    return $this->deepL->getApiUsage();
  }

  /**
   * Gets a list of languages that DeepL can translate from/to
   * @param  bool   $sort Sort languages by name
   * @return object
   */
  public function translatableLanguages(bool $sort = true): object {
    $source = $this->deepL->availableLanguages('source')->data;
    $target = $this->deepL->availableLanguages('target')->data;

    $sortLangs = function(array $langList): array {
      usort($langList, function($a, $b) {
        return strcmp($a->name, $b->name);
      });

      return $langList;
    };

    return (object) [
      'source' => $sort ? $sortLangs($source) : $source,
      'target' => $sort ? $sortLangs($target) : $target
    ];
  }

  /**
   * Gets the current language ISO 639-1 code
   * @return string
   */
  public function currentLanguageIsoCode(): string {
    $currentLanguageId = $this->user->language->id;

    return $this->getLanguageIdIsoAssociations()[$currentLanguageId] ?? null;
  }

  /**
   * Generates alt language meta tags to be rendered in the <head> element of the
   * page for SEO and standards compliance
   * @param  array  $excludeIds ProcessWire IDs of languages to not render alternate link tag for
   * @return string
   */
  public function renderAltLanguageMetaTags(array $excludeIds = []): string {
    $pwLanguages = $this->languages;
    $metaTagTemplate = $this->fluencyTools->getTemplate('alt_language_link_tag.tpl.html');
    $isoCodesById = $this->getLanguageIdIsoAssociations();
    $allTags = [];

    foreach ($this->languages as $language) {
      $languageId = $language->id;

      if (!in_array($languageId, $excludeIds) && isset($isoCodesById[$languageId])) {
        $allTags[] = strtr($metaTagTemplate, [
          '%{HREF}' => $this->page->localHttpUrl($language),
          '%{HREFLANG}' => $isoCodesById[$languageId]
        ]);
      }
    }

    // Add default fallback language tag definition (Google recommended)
    $allTags[] = strtr($metaTagTemplate, [
      '%{HREF}' => $this->page->localHttpUrl($pwLanguages->get('name=default')),
      '%{HREFLANG}' => 'x-default'
    ]);

    return implode('', $allTags);
  }

  /**
   * Renders an accessible language select element with options for each language. Options array
   * allows additional configuration. Optional inline JS that navigates to page in language on
   * select can be added..
   * Available options:
   *
   * $opts = [
   *   'addJs' => false,                         // bool, default: false
   *   'id' => 'your-specified-id',              // string
   *   'classes' => 'additional classes-to-add', // string
   *   'excludeIds' => []                        // array, ProcessWire  IDs of langauges to exclude
   * ]
   *
   * @param  array $opts  Additional options for rendering select element
   * @return string
   */
  public function renderLanguageSelectElement(array $opts = []): string {
    $currentLanguage = $this->user->language;
    $selectElTemplate = $this->fluencyTools->getTemplate('language_select_tag.tpl.html');
    $optionElTemplate = $this->fluencyTools->getTemplate('language_select_option_tag.tpl.html');
    $optionElJs = $this->fluencyTools->getTemplate('language_select_inline_js.tpl.html');
    $excludeIds = $opts['excludeIds'] ?? [];
    $optionEls = [];

    // Create option elements markup, add each to array
    foreach (wire('languages') as $language) {
      if (in_array($language->id, $excludeIds)) {
        continue;
      }

      $optionEls[] = strtr($optionElTemplate, [
        '%{URL}' => $this->page->localUrl($language),
        '%{SELECTED}' => $currentLanguage->id === $language->id ? ' selected' : '',
        '%{LANGUAGE_NAME}' => $language->title
      ]);
    }

    // Add data to select element, output is completed markup
    $output = strtr($selectElTemplate, [
      '%{ID}' => $opts['id'] ?? '',
      '%{CLASSES}' => !empty($opts['classes']) ? " {$opts['classes']}" : '',
      '%{INLINE_JS}' => !empty($opts['addJs']) && $opts['addJs'] ? " {$optionElJs}" : '',
      '%{ARIA_SELECTED_LANGUAGE_LABEL}' => $currentLanguage->title,
      '%{CURRENT_LANGUAGE}' => $currentLanguage->title,
      '%{OPTION_ELS}' => implode('', $optionEls)
    ]);

    return $output;
  }

  ////////////////////////
  // AJAX API Endpoints //
  ////////////////////////

  // This is the interface that makes the module action methods available via AJAX

  /**
   * Handles AJAX requests to /{admin slug}/fluency/data
   * The req GET parameter value determines what data will be returned and
   * is required for all AJAX requests to the module
   *
   * @return string    // JSON
   */
  public function ___executeData(): ?string {
    if (!$this->config->ajax) return null;

    $postData = $this->input->post;
    $httpStatus = "200 OK";
    $returnData = [];

    switch ($postData->req) {
      case 'getBootData':
        $returnData = (object) [
          'data' => $this->getClientBootData()
        ];
        break;
      case 'translate':
        $returnData = $this->translate(
          urldecode($postData->sourceLanguage),
          urldecode($postData->content),
          urldecode($postData->targetLanguage)
        );
        break;
      case 'usage':
        $returnData = $this->apiUsage();
        break;
      case 'translatableLanguages':
        $returnData = $this->translatableLanguages();
        break;
      case 'currentLanguageIsoCode':
        $returnData = $this->currentLanguageIsoCode();
        break;
      case 'altLanguageMetaTags':
        $returnData = $this->altLanguageMetaTags();
        break;
      case 'localizeModule':
        return [1020, 1100];
        // Localize the module
        break;
      case 'clearLocalizationCache':
        $returnData = $this->fluencyLocalization->clearLocalizations();
        $httpStatus = "204 No Content";
        return true;
        // clear localizations
        break;
      case 'getAllModuleLocalizations':
        return [1020, 1100];
        break;
      default:
        $httpStatus = "400 Bad Request";
        $returnData = (object) [
          'data' => null,
          'httpStatus' => 400,
          'message' => 'Fluency Module Error: No request parameter found or incorrect parameter received'
        ];
        break;
    }

    header('Content-Type: application/json');
    http_response_code($httpStatus);
    header($this->deepL->getHttpMessage($returnData->httpStatus));

    return json_encode($returnData);
  }

  ////////////////
  // Admin Page //
  ////////////////

  /**
   * Admin translation tool page
   *
   * @return string
   */
  public function ___execute(): string {
    $moduleConfig = $this->modules->getModuleConfigData('Fluency');
    $translationToolUiText = $this->fluencyLocalization->get('translationTool');
    // $translationToolUiText = $this->getLocalizations()->translationTool;

    //////////////////////
    // Create page form //
    //////////////////////
    $form = $this->modules->get('InputfieldForm');
    $form->addClass('fluency-admin-view');

    /////////////////////////////////
    // Unconfigured module message //
    /////////////////////////////////
    // Detects if the module has not been configured and displays a corresponding
    // message to the user when attempting to visit the admin page
    if (!$this->deepl_api_key ||
        (isset($moduleConfig['api_key_valid']) && !$moduleConfig['api_key_valid'])) {
      // Create markup for field

      $content = "<h1>{$translationToolUiText->unconfiguredErrorTitle}</h1>";
      $content .= "<p>{$translationToolUiText->unconfiguredErrorBody}</p>";

      // Create field for markup
      $field = $this->modules->get('InputfieldMarkup');
      $field->label = __('Notice');
      $field->collapsed = Inputfield::collapsedNever;
      $field->skipLabel = Inputfield::skipLabelHeader;
      $field->value = $content;
      $form->add($field);

      // If there's an API key that's invalid, don't reder the translator tool
      // Render and return
      return $form->render();
    }

    /////////////////////
    // Translator tool //
    /////////////////////
    $fieldset = $this->modules->get('InputfieldFieldset');
    $fieldset->name = 'fieldset_fluency_translator_tool';
    $fieldset->label = $translationToolUiText->title;
    $fieldset->description = $translationToolUiText->description;
    $fieldset->addClass('fluency-translator-tool');
    $fieldset->collapsed = Inputfield::collapsedNever;
    $fieldset->icon = 'language';
    $fieldset->addClass('fluency-translator-fieldset');
    $fieldset->addClass('fluency-overlay-container');
    $fieldset->appendMarkup = $this->fluencyTools->getTemplate('overlay_translating.tpl.html');

    // ===== Create source language select
    $deeplSourceLanguages = $this->deepL->availableLanguages('source')->data;

    $sourceLangSelect = $this->modules->get('InputfieldSelect');
    $sourceLangSelect->name = "fluency_translate_source_lang";
    $sourceLangSelect->label = $translationToolUiText->fieldLabelFrom;
    $sourceLangSelect->required = true;
    $sourceLangSelect->columnWidth = 50;
    $sourceLangSelect->collapsed = Inputfield::collapsedNever;
    $sourceLangSelect->themeBorder = 'hide';
    $sourceLangSelect->addClass('fluency-source-lang');

    // Get the default language DeepL code and use it to set the default
    // language selected in the translator
    $pwSourceLanguage = false;
    $configuredSourceLanguage = $this->getConfiguredLanguageData()->source;

    if (isset($configuredSourceLanguage->deeplCode)) {
      $pwSourceLanguage = $configuredSourceLanguage->deeplCode;
    };

    // Add each DeepL source language to the select field
    foreach ($deeplSourceLanguages as $lang) {
      if ($lang->language === $pwSourceLanguage) {
        $sourceLangSelect->addOption(
          $lang->language,
          $lang->name,
          ['selected'=> 'selected']
        );
      } else {
        $sourceLangSelect->addOption($lang->language, $lang->name);
      }
    }
    // Add source language select to fieldset
    $fieldset->append($sourceLangSelect);

    // ===== Create destination language select
    $deeplTargetLanguages = $this->deepL->availableLanguages('target')->data;

    $targetLangSelect = $this->modules->get('InputfieldSelect');
    $targetLangSelect->name = "fluency_translate_target_lang";
    $targetLangSelect->label = $translationToolUiText->fieldLabelTo;
    $targetLangSelect->required = true;
    $targetLangSelect->columnWidth = 50;
    $targetLangSelect->collapsed = Inputfield::collapsedNever;
    $targetLangSelect->themeBorder = 'hide';
    $targetLangSelect->addClass('fluency-target-lang');

    foreach ($deeplTargetLanguages as $lang) {
      $targetLangSelect->addOption($lang->language, $lang->name);
    }

    $fieldset->append($targetLangSelect);

    // ===== Create source content textarea
    $sourceLangContent = $this->modules->get('InputfieldTextarea');
    $sourceLangContent->name = "fluency_translate_source_content";
    $sourceLangContent->label = $translationToolUiText->fieldLabelYourText;
    $sourceLangContent->columnWidth = 50;
    $sourceLangContent->collapsed = Inputfield::collapsedNever;
    $sourceLangContent->themeInputSize = 's';
    $sourceLangContent->themeBorder = 'hide';
    $sourceLangContent->rows = 10;
    $sourceLangContent->addClass('fluency-source-content');
    $fieldset->append($sourceLangContent);

    // ===== Create target content textarea
    $targetLangContent = $this->modules->get('InputfieldTextarea');
    $targetLangContent->name = "fluency_translate_target_content";
    $targetLangContent->label = $translationToolUiText->fieldLabelTranslatedText;
    $targetLangContent->columnWidth = 50;
    $targetLangContent->collapsed = Inputfield::collapsedNever;
    $targetLangContent->themeInputSize = 's';
    $targetLangContent->themeBorder = 'hide';
    $targetLangContent->rows = 10;
    $targetLangContent->addClass('fluency-target-content');
    $fieldset->append($targetLangContent);

    // ===== Create translate button
    $translateButton = $this->modules->get('InputfieldSubmit');
    // $translateButton->label = 'wat';
    $translateButton->addClass('fluency-translate-button');
    $translateButton->addClass('js-fluency-translate');
    $translateButton->text = $translationToolUiText->buttonTranslate;
    $translateButton->attr('icon','chevron-circle-right');
    $fieldset->append($translateButton);

    // Add fieldset to form
    $form->append($fieldset);

    ///////////////
    // API usage //
    ///////////////
    $fieldset = $this->modules->get('InputfieldFieldset');
    $fieldset->name = 'fieldset_fluency_api_usage';
    $fieldset->label = $translationToolUiText->serviceUsageTitle;
    $fieldset->description = $translationToolUiText->serviceUsageDescription;
    $fieldset->addClass('fluency-api-usage-fieldset');
    $fieldset->addClass('fluency-overlay-container');
    $fieldset->collapsed = true;
    $fieldset->appendMarkup = $this->fluencyTools->getTemplate('overlay_update_usage_table.tpl.html');

    // NOTE: This only creates a table that can be filled in on demand
    // Create markup
    $usageTable = $this->modules->get('MarkupAdminDataTable');
    $usageTable->sortable = false;
    $usageTable->action(['Refresh' => '#']);
    $usageTable->headerRow([
      $translationToolUiText->usageTableCharacterTitle,
      $translationToolUiText->usageTableTranslatedTitle,
      $translationToolUiText->usageTableAvailableTitle,
      $translationToolUiText->usageTableTotalTitle
    ]);

    // Initialize this table with zeroes
    // It will be updated via ajax by user interaction
    $usageTable->row([
      ['*', 'fluency-usage-limit'],
      ['*', 'fluency-usage-translated'],
      ['*', 'fluency-usage-available'],
      ['*', 'fluency-usage-total']
    ]);

    // Create field for markup
    $field = $this->modules->get('InputfieldMarkup');
    $field->collapsed = Inputfield::collapsedNever;
    $field->skipLable = Inputfield::skipLabelHeader;
    $field->value = $usageTable->render();
    $fieldset->append($field);

    // Add fieldset to form
    $form->append($fieldset);

    return $form->render();
  }

  /**
   * Creates a new message in a dedicated Fluency log
   * @param  string $msg Message to include in log
   * @return void
   */
  private function logMsg($msg): void {
    $this->log->save('fluency-translation', $msg);
  }
}

