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
  private const ERROR_LOG = 'deeplwire-api';

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

      $output['message'] = json_encode([
        'HTTP Code' => $httpResponseCode,
        'API Message' => $apiMsg,
        'Request Endpoint' => $endpoint,
        'Parameters' => $paramString,
        'PW Page ID' => $page->id,
        'PW Page Name' => $page->name
      ]);

      wire('log')->save(self::ERROR_LOG, $message);
    }

    // If we did not get a positive response, do not return the body data directly
    // This prevents having to do more aggressive checking elsewhere.
    // To check for errors use the $response->http_code and get associated messages
    // from the DeepL::getErrorMessage() public method
    // $output['data'] = $httpResponseCode === 200 ? $data : null;
    $output['data'] = $data;
    $output['httpCode'] = $httpResponseCode;


    return (object) $output;
  }

  /**
   * Adds the tag that prevents DeepL from translating words/phrases.
   *
   * @param string $text String to add DeepL ignored tags
   */
  private function addIgnoredTags(string $text, array $ignoredStrings): string {
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
  }

  /**
   * Removes the ignored tag from translated text
   *
   * @param  string  $text            String containing ignore tags to strip
   * @param  array   $ignoredStrings  Strings to not be translated
   * @return string                   String without tags
   */
  private function removeIgnoredTags(string $text, array $ignoredStrings): string {
    return preg_replace('/(<\/?' . self::IGNORED_TAG_NAME . '>)/', '', $text);
  }

  /**
   * This method makes the actual call to DeepL to translate a given string of text.
   * The $texts array can take up to 50 k/v sets to translate in one request
   * See: https://www.deepl.com/docs-api.html?part=translating_text
   *
   * @param  string       $source         The 2 letter source langauge shortcode
   * @param  string|array $content        Either a string or an array of strings
   * @param  string       $target         The 2 letter target langauge shortcode
   * @param  array        $addParams      Additional DeepL parameters
   * @param  array        $ignoredStrings Array of strings to not translate
   * @return object                       Array of translated strings
   */
  public function translate(
    string $sourceLanguage,
    $content,
    string $targetLanguage,
    array $addParams = [],
    array $ignoredStrings = []
  ): object {
    $hasIgnoredStrings = count($ignoredStrings);
    $output = null;

    // Convert the content to an array if it's a string
    $content = gettype($content) === 'array' ? $content : [$content];

    // If there are strings that shouldn't be translated, add no-translate tags
    if ($hasIgnoredStrings) {
      $content = array_map(function($text) use ($ignoredStrings) {
        return $this->addIgnoredTags($text, $ignoredStrings);
      }, $content);
    }

    $request = array_merge($addParams, [
     'source_lang' => $sourceLanguage,
     'target_lang' => $targetLanguage,
     'tag_handling' => 'xml',
     'text' => $content
    ]);

    $output = $this->apiCall('/translate', $request);

    // If there were ignored strings, remove their tags from the returned data
    if ($hasIgnoredStrings && isset($output->data->translations)) {
      $output->data->translations = array_map(function($translation) use ($ignoredStrings) {
        // The array returned by DeepL contains objects with arrays containing the
        // detected langauge and the translated string. We will pull this data,
        // strip the ignore tags, and return it in the original structure.
        $translation->text = $this->removeIgnoredTags(
          $translation->text,
          $ignoredStrings
        );

        return $translation;
      }, $output->data->translations);
    }

    return $output;
  }

  /**
   * Returns a multidimensional array containing all of the languages available
   * for translation by DeepL
   * @param  string $type Get source or target langauges (parameter should be source/target)
   * @return object
   */
  public function getLanguageList(string $type = 'target'): object {
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
}
