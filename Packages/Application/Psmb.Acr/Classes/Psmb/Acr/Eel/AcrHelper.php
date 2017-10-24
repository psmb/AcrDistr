<?php
namespace Psmb\Acr\Eel;

use TYPO3\Eel\ProtectedContextAwareInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\Flow\Annotations as Flow;

/**
 * Eel helper as a wrapper around Twitter API
 */
class AcrHelper implements ProtectedContextAwareInterface {

	protected $result;

	protected $domain;

	/**
	 * Inject the settings
	 *
	 * @param array $settings
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}


	/**
	 * @param string $domain
	 * @return array
	 */
	public function scan($domain) {
		$this->domain = $domain;
		$this->result = $this->settings['Sections'];

		foreach($this->result as $sectionUrl => &$section) {
			if (isset($section['children'])) {
				$xpath = $this->getXpath($sectionUrl);
				if ($xpath) {
					foreach($section['children'] as $itemtypeName => &$itemtype) {
						if (isset($itemtype['children'])) {
							foreach($itemtype['children'] as $itempropName => &$itemprop) {
								$itemprop['result'] = $this->getProp($xpath, $itemtypeName, $itempropName);
							}
						}
					}
				} else {
					$section['error'] = true;
				}
			}
		}
		return $this->result;
	}

	protected function getXpath($sectionUrl) {
		$html = @file_get_contents($this->domain . $sectionUrl);
		if ($html === false) {
			return false;
		}
		$dom = new \DOMDocument;
		// Ignore warnings
		libxml_use_internal_errors(true);
		// Fix encoding issues
		$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
		libxml_use_internal_errors(false);
		return new \DomXPath($dom);
	}

	protected function getProp($xpath, $typeName, $propName) {
		$result = [];
		if ($typeName == 'common') {
			$items = $xpath->query("//*[contains(@itemprop,'" . $propName . "')]");
		} else {
			$items = $xpath->query("//*[@itemprop='http://obrnadzor.gov.ru/microformats/" . $typeName . "']//*[contains(@itemprop,'" . $propName . "')]");
		}
		foreach($items as $item) {
			$result[] = strip_tags($this->getInnerHtml($item), '<a><div><p><h1><h2><h3><h4><br><table><tr><td><th><tbody>');
		}
		return $result;
	}

	private function getInnerHtml($node) {
		return $node->ownerDocument->saveXML($node);
	}

	/**
	 * All methods are considered safe
	 *
	 * @param string $methodName
	 * @return boolean
	 */
	public function allowsCallOfMethod($methodName) {
		return true;
	}
}
