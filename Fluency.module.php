<?php namespace ProcessWire;

// FileCompiler=0

require_once 'classes/FluencyTools.class.php';
require_once 'engines/DeepL.class.php';
/**
 * Master module class for Fluency
 */
class Fluency extends Process implements Module {

  /**
   * Holds DeepL class instance
   * Public so that the DeepL object can be called directly for use via the
   * module
   * @var DeepL
   */
  public $deepL;

  /**
   * Holds data for every language in ProcessWire and their DeepL associations
   * @var array
   */
  private $allLanguageData = [];

  /**
   * Name/slug of the Fluency admin page
   * @var string
   */
  private $adminPageName = 'fluency';

  /**
   * Executes module when PW is ready
   * @return void
   */
  public function ready() {
    $this->fluencyTools = new FluencyTools;

    $this->deepL = new DeepL([
      'apiKey' => $this->deepl_api_key,
      'accountType' => $this->deepl_account_type
    ]);

    if (!$this->moduleShouldInit()) return false;

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
           wire('user')->hasPermission('fluency-translate') &&
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
    $languageData = [
      'source' => [],
      'target' => []
    ];
    $languageTitle = '';

    // Iterate through all languages and package for front end consumption
    foreach ($this->languages as $language) {
      $languageId = $language->id;
      $languageName = $language->name;
      // Get the module configuration association variable and then pull the
      // DeepL code
      $configVar = "pw_language_{$languageId}";
      $languageDeeplCode = $this->$configVar;
      // We only want to return languages that have been configured
      if (!$languageDeeplCode) continue;

      // We want to get the proper name for labeling the input translation trigger
      if ($language->name === "default") {
        // Set source language data
        $userLang = $this->user->language;
        $languageTitle = $language->title;

        // Check that this is a LanguagesPageFieldValue object before calling
        // this method. $language->title returns different objects depending on
        // context.
        if (gettype($languageTitle) !== 'string') {
          $languageTitle = $language->title->getLanguageValue($userLang->name);
        }

        $languageData['source']['id'] = $languageId;
        $languageData['source']['name'] = $languageName;
        $languageData['source']['title'] = $languageTitle;
        $languageData['source']['deeplCode'] = $languageDeeplCode;

        $languageData['source'] = (object) $languageData['source'];
      } else {
        // Set target languages data
        $languageData['target'][] = (object) [
          'id' => $languageId,
          'name' => $languageName,
          'title' => $language->title,
          'deeplCode' => $languageDeeplCode
        ];
      }
    }

    return (object) $languageData;
  }

  /**
   * This returns a data payload needed by the client UI JS to add translation
   * triggers and interactivity to fields in admin pages.
   *
   * @return array Array with all boot data needed by client UI scripts.
   */
  private function getClientBootData(): object {
    return (object) [
      'languages' => $this->getConfiguredLanguageData(),
      'pageName' => $this->page->name
    ];
  }

  /**
   * Handle translation requests
   *
   * @param  string       $sourceLangCode  2 letter language shortcode translating from
   * @param  string|array $content         Can be a string or an array of strings
   * @param  string       $targetLangCode  2 letter language shortcode translating to
   * @param  array        $addParams       Additional DeepL API parameters
   * @param  array        $ignoredStrings  Strings to not translate (Merged with CMS config'd strings)
   * @return object                        Fluency  module response object
   */
  public function translate(
    string $sourceLangCode,
    $content,
    string $targetLangCode,
    array  $addParams = [],
    ?array $ignoredStrings = []
  ): object {
    // Get configured non-translated strings and merge with passed array
    if ($this->non_translated_strings) {
      $configIgnoredStrings = explode(',', $this->non_translated_strings);
      $configIgnoredStrings = array_map('trim', $configIgnoredStrings);

      $ignoredStrings = array_merge($configIgnoredStrings, $ignoredStrings);
    }

    // Configure additional parameters
    $parameters = array_merge([
      'preserve_formatting' => $this->api_param_preserve_formatting
    ], $addParams);

    // Translate and get value
    $result = $this->deepL->translate(
      $sourceLangCode,
      $content,
      $targetLangCode,
      $parameters,
      $ignoredStrings
    );

    return $result;
  }

  /**
   * Gets the current API usage relative to the limits configured in your DeepL
   * developer account
   * @return object
   */
  public function getApiUsage(): object {
    return $this->deepL->getApiUsage();
  }

  /**
   * Gets a list of languages that DeepL can translate from/to
   * @return object
   */
  public function getLanguageList(): object {
    return $this->deepL->getLanguageList();
  }

  /**
   * Handles AJAX requests to /{admin slug}/fluency/data
   * The req GET parameter value determines what data will be returned and
   * is required for all AJAX requests to the module
   *
   * @return string|null
   */
  public function ___executeData(): ?string {
    if (!$this->config->ajax) return null;

    $returnData = [];

    switch ($this->input->post->req) {
      case 'getBootData':
        $returnData = (object) [
          'data' => $this->getClientBootData(),
          'httpCode' => 200
        ];
        break;
      case 'translate':
        $returnData = $this->translate(
          urldecode($this->input->post->sourceLanguage),
          urldecode($this->input->post->content),
          urldecode($this->input->post->targetLanguage),
          [],
          $this->input->ignoredStrings ?? []
        );
        break;
      case 'usage':
        $returnData = $this->getApiUsage();
        break;
      case 'languageList':
        $returnData = $this->getLanguageList();
        break;
      default:
        $returnData = (object) [
          'data' => null,
          'httpCode' => 400,
          'message' => 'Fluency Module Error: No request parameter found or incorrect parameter received'
        ];
        break;
    }

    header('Content-Type: application/json');
    header($this->deepL->getHttpMessage($returnData->httpCode));
    return json_encode($returnData);
  }

  /**
   * Handles admin page execution
   *
   * @return string
   */
  public function ___execute(): string {
    $moduleConfig = $this->modules->getModuleConfigData('Fluency');

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

      $content = '';

      $content = '<h1>' . __("Sorry, translation is not ready yet.") . '</h1>';

      $content .= '<p>' . __("This module needs to be configured before use. Please add a valid DeepL API key in Fluency's module configuration.") . '</p>';

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
    $fieldset->label = __('Fluency Translation Tool');
    $fieldset->description = __('Translate your text to any language');
    $fieldset->addClass('fluency-translator-tool');
    $fieldset->collapsed = Inputfield::collapsedNever;
    $fieldset->icon = 'language';
    $fieldset->addClass('fluency-translator-fieldset');
    $fieldset->addClass('fluency-overlay-container');
    $fieldset->appendMarkup = $this->fluencyTools->getMarkup('overlay_translating.tpl.html');

    // ===== Create source language select
    $deeplSourceLanguages = $this->deepL->getLanguageList('source')->data;

    $sourceLangSelect = $this->modules->get('InputfieldSelect');
    $sourceLangSelect->name = "fluency_translate_source_lang";
    $sourceLangSelect->label = __('Translate from...');
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
    $deeplTargetLanguages = $this->deepL->getLanguageList('target')->data;

    $targetLangSelect = $this->modules->get('InputfieldSelect');
    $targetLangSelect->name = "fluency_translate_target_lang";
    $targetLangSelect->label = __('Translate to...');
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
    $sourceLangContent->label = __('Your Text:');
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
    $targetLangContent->label = __('Translated Text:');
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
    $translateButton->text = 'Translate';
    $translateButton->attr('icon','chevron-circle-right');
    $fieldset->append($translateButton);

    // Add fieldset to form
    $form->append($fieldset);

    ///////////////
    // API usage //
    ///////////////
    $fieldset = $this->modules->get('InputfieldFieldset');
    $fieldset->name = 'fieldset_fluency_api_usage';
    $fieldset->label = __('Translation Service Usage Information');
    $fieldset->description = __('Click the Refresh button to get translation service usage information');
    $fieldset->addClass('fluency-api-usage-fieldset');
    $fieldset->addClass('fluency-overlay-container');
    $fieldset->collapsed = true;
    $fieldset->appendMarkup = $this->fluencyTools->getMarkup('overlay_update_usage_table.tpl.html');

    // NOTE: This only creates a table that can be filled in on demand
    // Create markup
    $usageTable = $this->modules->get('MarkupAdminDataTable');
    $usageTable->sortable = false;
    $usageTable->action(['Refresh' => '#']);
    $usageTable->headerRow([
      __('Character Limit'),
      __('Characters Translated'),
      __('Characters Available'),
      __('Total Usage')
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
    // $field->label = __('Usage Details');
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

