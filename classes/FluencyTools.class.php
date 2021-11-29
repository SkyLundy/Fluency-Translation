<?php namespace ProcessWire;

/**
 * A utility class that provides helper methods for common needs
 */
class FluencyTools {

  /**
   * Gets a module template
   *
   * @param  string $filename Filename of template file
   * @return string           Completed markup for output
   */
  public function getTemplate(string $filename): string {
    return file_get_contents(__DIR__ . "/../fluency_templates/{$filename}");

    return $markup;
  }

  /**
   * Fills template with data
   */
  // public function fillTemplate() {

  // }
}
