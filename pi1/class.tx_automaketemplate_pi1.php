<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2002-2004 Kasper Skårhøj (kasper@typo3.com)
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is 
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
* 
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
* 
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/** 
 * Plugin 'Template Auto-parser' for the 'automaketemplate' extension.
 *
 * $Id$
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   56: class tx_automaketemplate_pi1 extends tslib_pibase 
 *   74:     function main($content,$conf)	
 *  174:     function recursiveBlockSplitting($content)	
 *  279:     function singleSplitting($content)	
 *
 * TOTAL FUNCTIONS: 3
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


require_once(PATH_tslib.'class.tslib_pibase.php');

/**
 * Plugin class - instantiated from TypoScript. See documentation in doc/manual.sxw
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage tx_automaketemplate
 */
class tx_automaketemplate_pi1 extends tslib_pibase {

		// Default extension plugin variables:
	var $prefixId = 'tx_automaketemplate_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_automaketemplate_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey = 'automaketemplate';	// The extension key.

		// Others:
	var $htmlParse;		// Is set to an object; Instance of t3lib_parsehtml
	

	/**
	 * Main function, called from TypoScript
	 * 
	 * @param	string		Input content. Not used. Ignore.
	 * @param	array		TypoScript configuration of the plugin.
	 * @return	string		HTML output.
	 */
	function main($content,$conf)	{	
		
			// Setting configuration internally:
		$this->conf = $conf;
		
			// Getting content:
		$content = $this->cObj->cObjGetSingle($conf['content'],$conf['content.'],'content');

			// Making cache-hash:
		$hashConf = $conf;
		unset($hashConf['getBodyTag']);
		$hash = md5($content.'|'.serialize($hashConf));
		
			// Looking for a cached version of the parsed template:
		$hashedContent = $GLOBALS['TSFE']->sys_page->getHash($hash);
		if ($hashedContent)	{	// Cached version found; setting values from the cache data:
			$hashedContent = unserialize($hashedContent);
			$this->markersContent = $hashedContent['markersContent'];
			$this->bodyTagFound = $hashedContent['bodyTagFound'];
			$content = $hashedContent['content'];
		} else {	// Cached version NOT found; parsing the template
		
				// Initialize HTML parser object:
			$this->htmlParse = t3lib_div::makeInstance('t3lib_parsehtml');
			
				// Block elements (eg. TABLE, TD, P, DIV)
			$elArr=array();
			if (is_array($this->conf['elements.']))	{
			
					// Finding all elements configured:
				foreach($this->conf['elements.'] as $k => $v)	{
					if (is_array($v))	{
						$elArr[]=substr($k,0,-1);
					} else {
						$elArr[]=$k;
					}
				}
				
					// Splitting/Processing the HTML source by these tags:
				$elArr=array_unique($elArr);
				if (count($elArr))	{
					$this->elementList = implode(',',$elArr);
					$content = $this->recursiveBlockSplitting($content);
				}
			}

				// Single elements (eg. IMG, INPUT)
			$elArr=array();
			if (is_array($this->conf['single.']))	{
			
					// Finding all elements configured:
				foreach($this->conf['single.'] as $k => $v)	{
					if (is_array($v))	{
						$elArr[]=substr($k,0,-1);
					} else {
						$elArr[]=$k;
					}
				}
				
					// Splitting/Processing the HTML source by these tags:
				$elArr=array_unique($elArr);
				if (count($elArr))	{
					$this->elementList = implode(',',$elArr);
					$content = $this->singleSplitting($content);
				}
			}

				// Fixing all relative paths found:
			if ($this->conf['relPathPrefix'])	{
				$content = $this->htmlParse->prefixResourcePath($this->conf['relPathPrefix'],$content,$this->conf['relPathPrefix.']);
			}
			
				// Finding the bodyTag of the HTML source:
			list(,$this->bodyTagFound)=$this->htmlParse->splitTags('body',$content);
			
				// Finally, save the results in the hash table:
			$GLOBALS['TSFE']->sys_page->storeHash($hash,serialize(
				array(
					'content' => $content,
					'markersContent' => $this->markersContent,
					'bodyTagFound' => $this->bodyTagFound
				)
			),'tx_automaketemplate_pi1');
		}	

			// If the property "getBodyTag" was set, return the bodytag. Else return the processed content:
		if ($this->conf['getBodyTag'])	{
			return $this->bodyTagFound?$this->bodyTagFound:'';
		} else {
			return $content;
		}
	}

	/**
	 * Processing HTML content based on element list (block tags!)
	 * 
	 * @param	string		HTML content to split.
	 * @return	string		Processed HTML content
	 * @access private
	 */
	function recursiveBlockSplitting($content)	{
	
			// Split HTML source:
		$parts = $this->htmlParse->splitIntoBlock($this->elementList,$content,0);
		
			// Traverse the parts:
		foreach($parts as $k => $v)	{
			if ($k%2)	{
			
					// Initializing:
				$firstTag = $this->htmlParse->getFirstTag($v);	// The first tag's content
				$firstTagName = $this->htmlParse->getFirstTagName($v);	// The 'name' of the first tag
				$endTag = '</'.strtolower($firstTagName).'>';
				$v = $this->htmlParse->removeFirstAndLastTag($v);	// Finally remove the first tag (unless we do this, the recursivity will be eternal!
				
					// Remove tags from source:
				if ($this->conf['elements.'][$firstTagName.'.']['rmTagSections'])	{
					$elList = t3lib_div::trimExplode(',',$this->conf['elements.'][$firstTagName.'.']['rmTagSections'],1);
					$rmParts = $this->htmlParse->splitIntoBlock(implode(',',$elList),$v,1);
					$outerParts = $this->htmlParse->getAllParts($rmParts,0);
					$v = implode('',$outerParts);
				}
				if ($this->conf['elements.'][$firstTagName.'.']['rmSingleTags'])	{
					$elList = t3lib_div::trimExplode(',',$this->conf['elements.'][$firstTagName.'.']['rmSingleTags'],1);
					$rmParts = $this->htmlParse->splitTags(implode(',',$elList),$v,1);
					$outerParts = $this->htmlParse->getAllParts($rmParts,0);
					$v = implode('',$outerParts);
				}

					// Perform str-replace on the source:
				if (is_array($this->conf['elements.'][$firstTagName.'.']['str_replace.']))	{
					foreach($this->conf['elements.'][$firstTagName.'.']['str_replace.'] as $kk => $vv)	{
						if (is_array($vv) && strcmp($vv['value'],''))	{
							switch((string)$vv['useRPFunc'])	{
								case 'ereg_replace':
									$v = ereg_replace($vv['value'],$vv['replaceWith'],$v);
								break;
								default:
									$v = str_replace($vv['value'],$vv['replaceWith'],$v);
								break;
							}
						}
					}
				}

					// Make the call again - recursively:
				$v = $this->recursiveBlockSplitting($v);
				
					// Check if we are going to do processing:
				$params = $this->htmlParse->get_tag_attributes($firstTag,1);

				
					// Get configuration for this tag:
				$allCheck = $this->conf['elements.'][$firstTagName.'.']['all'];
				$classCheck = $params[0]['class'] && $this->conf['elements.'][$firstTagName.'.']['class.'][$params[0]['class']];
				$idCheck = $params[0]['id'] && $this->conf['elements.'][$firstTagName.'.']['id.'][$params[0]['id']];
				
					// If any configuration was found, do processing:
				if ($classCheck || $idCheck || $allCheck)	{
					if ($allCheck)		$lConf = $this->conf['elements.'][$firstTagName.'.']['all.'];
					if ($classCheck)	$lConf = $this->conf['elements.'][$firstTagName.'.']['class.'][$params[0]['class'].'.'];
					if ($idCheck)		$lConf = $this->conf['elements.'][$firstTagName.'.']['id.'][$params[0]['id'].'.'];

						// Create markers to insert:
					$marker=$lConf['subpartMarker'] ? $lConf['subpartMarker'] : (($idCheck||$allCheck)&&$params[0]['id']?$params[0]['id']:$params[0]['class']);
					$markerArr = array('<!--###'.$marker.'### begin -->','<!--###'.$marker.'### end -->');

						// Wrap markers...:
					if (!trim($marker))	{	// No marker, no wrapping:
						$v=$firstTag.$v.$endTag;
					} elseif ($lConf['doubleWrap'])	{	// Double wrapping, both inside and outside:
						$this->markersContent[$marker][]=$v;
						$v=$firstTag.$markerArr[0].$v.$markerArr[1].$endTag;

						$marker.='_PRE';
						$markerArr = array('<!--###'.$marker.'### begin -->','<!--###'.$marker.'### end -->');
						$this->markersContent[$marker][]=$firstTag.$v.$endTag;
						$v=$markerArr[0].$v.$markerArr[1];
					} elseif ($lConf['includeWrappingTag'])	{	// Wrapping outside the active tag:
						$this->markersContent[$marker][]=$firstTag.$v.$endTag;
						$v=$markerArr[0].$firstTag.$v.$endTag.$markerArr[1];
					} else {	// Default; wrapping inside the active tag:
						$this->markersContent[$marker][]=$v;
						$v=$firstTag.$markerArr[0].$v.$markerArr[1].$endTag;
					}
				} else {	// No config, no wrapping:
					$v=$firstTag.$v.$endTag;
				}
			}
			
				// Override the original value with the processed one:
			$parts[$k]=$v;
		}

			// Implode it all back to a string and return
		return implode('',$parts);
	}

	/**
	 * Processing HTML content based on element list (single tags!)
	 * 
	 * @param	string		HTML content to split.
	 * @return	string		Processed HTML content
	 * @access private
	 */
	function singleSplitting($content)	{
	
			// Split HTML source:
		$parts = $this->htmlParse->splitTags($this->elementList,$content);
		
			// Traverse the parts:
		foreach($parts as $k => $v)	{
			if ($k%2)	{
			
					// Initializing:
				$firstTag = $v;	// The first tag's content
				$firstTagName = $this->htmlParse->getFirstTagName($v);	// The 'name' of the first tag

					// Check if we are going to do processing:
				$params = $this->htmlParse->get_tag_attributes($firstTag,1);

// ******** THIS IS similar to the code in recursiveBlockSplitting(), but 'elements.' substituted with 'single.': (begin)
				$allCheck = $this->conf['elements.'][$firstTagName.'.']['all'];
				$classCheck = $params[0]['class'] && $this->conf['elements.'][$firstTagName.'.']['class.'][$params[0]['class']];
				$idCheck = $params[0]['id'] && $this->conf['elements.'][$firstTagName.'.']['id.'][$params[0]['id']];
				
					// If any configuration was found, do processing:
				if ($classCheck || $idCheck || $allCheck)	{
					if ($allCheck)		$lConf = $this->conf['elements.'][$firstTagName.'.']['all.'];
					if ($classCheck)	$lConf = $this->conf['elements.'][$firstTagName.'.']['class.'][$params[0]['class'].'.'];
					if ($idCheck)		$lConf = $this->conf['elements.'][$firstTagName.'.']['id.'][$params[0]['id'].'.'];

						// Create markers to insert:
					$marker=$lConf['subpartMarker'] ? $lConf['subpartMarker'] : (($idCheck||$allCheck)&&$params[0]['id']?$params[0]['id']:$params[0]['class']);
					$markerArr = array('<!--###'.$marker.'### begin -->','<!--###'.$marker.'### end -->');
// ******** THIS IS similar to the code in recursiveBlockSplitting() (end)

						// If a marker was defined, wrap the tag:
					if (trim($marker))	{
						$this->markersContent[$marker][]=$v;
						$v=$markerArr[0].$v.$markerArr[1];
					}
				}
			}
			
				// Override the original value with the processed one:
			$parts[$k]=$v;
		}

			// Implode it all back to a string and return
		return implode('',$parts);
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/automaketemplate/pi1/class.tx_automaketemplate_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/automaketemplate/pi1/class.tx_automaketemplate_pi1.php']);
}
?>