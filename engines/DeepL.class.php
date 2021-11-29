<?php namespace ProcessWire;

// FileCompiler=0

/**
 * Handles all API interaction with the DeepL API
 */
class DeepL {

  /**
   * Name of tag that will be used to prevent translation of selected strings
   * @var string
   */
  private const IGNORED_TAG_NAME = 'fluency-ignore';

  /**
   * Base URL for the pro DeepL API
   * @var string
   */
  private const URL_BASE_PRO = 'https://api.deepl.com/v2';

  /**
   * Base URL for the free DeepL API
   * @var string
   */
  private const URL_BASE_FREE = 'https://api-free.deepl.com/v2';

  /**
   * Name of error log
   * @var string
   */
  private const ERROR_LOG = 'fluency-deepl';

  /**
   * The DeepL API key
   * @var string
   */
  private $apiKey;

  /**
   * The appropriate DeepL API URL for the provided account type
   * @var string
   */
  private $apiUrl;

  /**
   * The DeepL account type. 'free' or 'pro'
   * @var string
   */
  private $accountType;

  public function __construct(array $configs) {
    $this->apiKey = $configs['apiKey'];
    $this->setApiUrl($configs['accountType']);
  }

  ///////////////////////
  // Public Interfaces //
  ///////////////////////

  /**
   * This method makes the actual call to DeepL to translate a given string of text.
   * The $texts array can take up to 50 k/v sets to translate in one request
   * See: https://www.deepl.com/docs-api.html?part=translating_text
   * $opts array options:
   * [
   *   'addParams => [
   *     'deepl-api-parameter' => 'value'
   *   ],
   *   'ignoredStrings' => [
   *     'do not',
   *     'translate',
   *     'these words'
   *   ]
   * ]
   * @param  string       $sourceLanguage The 2 letter source langauge shortcode
   * @param  string|array $content        String or an array of strings to translate
   * @param  string       $targetLanguage The 2 letter target langauge shortcode
   * @param  array        $opts           Array of options, see definition above
   * @return object                       API response
   */
  public function translate(
    string $sourceLanguage,
    $content,
    string $targetLanguage,
    array  $opts = []
  ): object {
    $addParams = $opts['addParams'] ?? [];
    $ignoredStrings = $opts['ignoredStrings'] ?? [];

    // Convert the content to an array if it's a string
    $content = is_array($content) ? $content : [$content];

    // If there are strings that shouldn't be translated, add no-translate tags
    $content = $this->addIgnoredTags($content, $ignoredStrings);

    // Ensure ISO 639-1 source/target language codes are uppercase
    $request = array_merge($addParams, [
     'source_lang' => strtoupper($sourceLanguage),
     'target_lang' => strtoupper($targetLanguage),
     'tag_handling' => 'xml',
     'text' => $content
    ]);

    // Call API
    $response = $this->apiCall('/translate', $request);

    // Remove any ignored strings from translated content
    if (isset($response->data->translations)) {
      $response->data->translations = $this->removeIgnoredTags($response->data->translations);
    }

    return $response;
  }

  /**
   * Returns a multidimensional array containing langauges translatable by DeepL
   * Source: Language translating from, list of language you can translate from
   * Target: Language translating to, list of languages you can translate to
   * @param  string $type Get source or target langauges (parameter should be source/target)
   * @return object
   */
  public function availableLanguages(string $type = 'target'): object {
    return $this->apiCall('/languages', [
      'type' => $type
    ]);
  }

  /**
   * Gets the current API usage including character limit and characters translated
   * @return object
   */
  public function getApiUsage(): object {
    return $this->apiCall('/usage');
  }

  //////////////////////
  // Internal Methods //
  //////////////////////

  /**
   * Sets the appropriate API URL determined by the provided account type
   * @param string $accountType Type of account, either 'free' or 'pro'
   */
  private function setApiUrl(string $accountType): void {
    switch ($accountType) {
      case 'pro':
        $this->apiUrl = self::URL_BASE_PRO;
        break;
      case 'free':
        $this->apiUrl = self::URL_BASE_FREE;
        break;
      default:
        $this->apiUrl = self::URL_BASE_FREE;
        break;
    }
  }

  /**
   * A list of error codes and corresponding messages. These are user friendly.
   * https://www.deepl.com/docs-api/accessing-the-api/error-handling/
   * These need to be moved to the localization class for self-translation
   * @var    int    HTTP code
   * @return string HTTP message
   */
  public function getHttpMessage(int $code): string {
    $message = null;

    $messages = [
      200 => 'HTTP/1.0 200 OK',
      400 => 'HTTP/1.0 400 Bad request. Please check error message and your parameters.',
      403 => "HTTP/1.0 403 The DeepL API key in use is invalid or the associated account is not current.",
      404 => "HTTP/1.0 404 The requested resource could not be found.",
      413 => "HTTP/1.0 413 The amount of content is too large to translate.",
      429 => "HTTP/1.0 429 Too many requests. Please wait and try again in a few moments.",
      456 => "HTTP/1.0 456 The DeepL translation character limit for this billing period has been reached. Please review your DeepL account for more information.",
      503 => "HTTP/1.0 503 There was an error communicating with the translation service, please try again later.",
      500 => "HTTP/1.0 500 The translation service may be experiencing errors or is undergoing maintenance. Please try again later"
    ];

    if (isset($messages[$code])) {
      $message = $messages[$code];
    } else {
      $message = 'An unknown error has occurred';
    }

    return $message;
  }

  /**
   * Maces a call to the DeepL API
   * @param  string $endpoint Fullendpoint URL starting with a slash
   * @param  array  $params   k/v array with parameter data for API call
   * @return string           JSON API return value
   */
  private function apiCall(string $endpoint, array $params = []) {
    $reqUrl = "{$this->apiUrl}{$endpoint}?auth_key={$this->apiKey}";
    $paramString = '';
    $output = [];

    // If array of text is present, format it and remove the key so it is not
    // added to the parameter string
    if (isset($params['text'])) {

      foreach ($params['text'] as $text) {
        $paramString .= "&text=" . urlencode($text);
      }

      // Set the ignored_tags parameter for translation
      $paramString .= "&ignore_tags=" . self::IGNORED_TAG_NAME;

      // Remove text entry from parameters
      unset($params['text']);
    }

    // Add params to URL
    foreach ($params as $param => $val) $paramString .= "&{$param}={$val}";

    // Get cURL resource
    $ch = curl_init();

    // Set some options
    curl_setopt_array($ch, [
      CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_POST => true,
      CURLOPT_POSTFIELDS => $paramString,
      CURLOPT_URL => $reqUrl,
      CURLOPT_USERAGENT => 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)'
    ]);

    // Send the request & save response to $output
    $data = json_decode(curl_exec($ch));
    $response = (object) curl_getinfo($ch);

    // Close request1
    curl_close($ch);

    $httpResponseCode = $response->http_code;

    // Error handling. If something didn't go right, then log the error, message,
    // page ID
    if ($httpResponseCode !== 200) {
      $apiMsg = isset($data->message) ? $data->message : 'No message returned';
      $page = wire('page');

      $message = json_encode([
        'HTTP Code' => $httpResponseCode,
        'API Message' => $apiMsg,
        'Request Endpoint' => $endpoint,
        'Parameters' => $paramString,
        'PW Page ID' => $page->id,
        'PW Page Name' => $page->name
      ]);

      $output['message'] = $message;

      wire('log')->save(self::ERROR_LOG, $message);
    }

    // If we did not get a positive response, do not return the body data directly
    // This prevents having to do more aggressive checking elsewhere.
    // To check for errors use the $response->http_code and get associated messages
    // from the DeepL::getErrorMessage() public method
    // $output['data'] = $httpResponseCode === 200 ? $data : null;
    $output['data'] = $data;
    $output['httpStatus'] = $httpResponseCode;

    return (object) $output;
  }

  /**
   * Adds the tag that prevents DeepL from translating words/phrases.
   * @param array  $content        Array of strings to add ignored tags to
   * @param array  $ignoredStrings String to add DeepL ignored tags
   */
  private function addIgnoredTags(array $content, array $ignoredStrings): array {
    // Map content array, return modiied data
    $output = array_map(function($text) use ($ignoredStrings) {
      $instancesFound = [];

      // Get all instances of ignored strings
      foreach ($ignoredStrings as $str) {
        preg_match_all('/' . preg_quote($str) . '/i', $text, $matches);

        $instancesFound = array_merge($instancesFound, $matches[0]);
      }

      // Replace ignored string matches with tagged versions
      foreach ($instancesFound as $instance) {
        $ignoredTagName = self::IGNORED_TAG_NAME;

        $taggedInstance = "<{$ignoredTagName}>{$instance}</{$ignoredTagName}>";

        $text = str_replace($instance, $taggedInstance, $text);
      }

      return $text;
    }, $content);

    return $output;
  }

  /**
   * Removes the ignored tags from array of translated texts from DeepL
   * @param  array $translations
   * @return array
   */
  private function removeIgnoredTags(array $translations): array {
    $output = array_map(function($translation) {
      // The array returned by DeepL contains objects with arrays containing the
      // detected langauge and the translated string. We will pull this data,
      // strip the ignore tags, and return it in the original structure.
      $tagPattern = '/(<\/?' . self::IGNORED_TAG_NAME . '>)/';

      $translation->text = preg_replace($tagPattern, '', $translation->text);

      return $translation;
    }, $translations);

    return $output;
  }
}
