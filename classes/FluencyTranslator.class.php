<?php namespace ProcessWire;

/**
 * Abstract class that provides commone data for translation engines
 */
abstract class FluencyTranslator {

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
      400 => 'HTTP/1.0 400 Bad request',
      403 => "HTTP/1.0 403 Forbidden.",
      404 => "HTTP/1.0 404 The requested resource could not be found.",
      413 => "HTTP/1.0 413 The amount of content is too large to translate.",
      429 => "HTTP/1.0 429 Too many requests. Please wait and try again in a few moments",
      456 => "HTTP/1.0 456 Translation limit has been reached",
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

}
