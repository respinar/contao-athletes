<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @package   atheletes
 * @author    Hamid Abbaszadeh
 * @license   LGPL-3.0+
 * @copyright respinar 2013-2015
 */


/**
 * Namespace
 */
namespace athletes;

/**
 * Class Athlete
 *
 * @copyright  respinar 2013-2015
 * @author     Hamid Abbaszadeh
 * @package    athletes
 */
class Athlete extends \Frontend
{

	/**
	 * Add athletes items to the indexer
	 * @param array
	 * @param integer
	 * @param boolean
	 * @return array
	 */
	public function getSearchablePages($arrPages, $intRoot=0, $blnIsSitemap=false)
	{
		$arrRoot = array();

		if ($intRoot > 0)
		{
			$arrRoot = $this->Database->getChildRecords($intRoot, 'tl_page');
		}

		$time = time();
		$arrProcessed = array();

		// Get all athletes categories
		$objCategory = \AthleteCategoryModel::findByProtected('');

		// Walk through each archive
		if ($objCategory !== null)
		{
			while ($objCategory->next())
			{
				// Skip athletes archives without target page
				if (!$objCategory->jumpTo)
				{
					continue;
				}

				// Skip athletes archives outside the root nodes
				if (!empty($arrRoot) && !in_array($objCategory->jumpTo, $arrRoot))
				{
					continue;
				}

				// Get the URL of the jumpTo page
				if (!isset($arrProcessed[$objCategory->jumpTo]))
				{
					$objParent = \PageModel::findWithDetails($objCategory->jumpTo);

					// The target page does not exist
					if ($objParent === null)
					{
						continue;
					}

					// The target page has not been published (see #5520)
					if (!$objParent->published || ($objParent->start != '' && $objParent->start > $time) || ($objParent->stop != '' && $objParent->stop < $time))
					{
						continue;
					}

					// The target page is exempt from the sitemap (see #6418)
					if ($blnIsSitemap && $objParent->sitemap == 'map_never')
					{
						continue;
					}

					// Set the domain (see #6421)
					$domain = ($objParent->rootUseSSL ? 'https://' : 'http://') . ($objParent->domain ?: \Environment::get('host')) . TL_PATH . '/';

					// Generate the URL
					$arrProcessed[$objCategory->jumpTo] = $domain . $this->generateFrontendUrl($objParent->row(), (($GLOBALS['TL_CONFIG']['useAutoItem'] && !$GLOBALS['TL_CONFIG']['disableAlias']) ?  '/%s' : '/items/%s'), $objParent->language);
				}

				$strUrl = $arrProcessed[$objCategory->jumpTo];

				// Get the items
				$objAthlete = \AthleteModel::findPublishedDefaultByPid($objCategory->id);

				if ($objAthlete !== null)
				{
					while ($objAthlete->next())
					{
						$arrPages[] = $this->getLink($objAthlete, $strUrl);
					}
				}
			}
		}

		return $arrPages;
	}


	/**
	 * Return the link of a atheletes
	 * @param object
	 * @param string
	 * @param string
	 * @return string
	 */
	protected function getLink($objItem, $strUrl, $strBase='')
	{
		switch ($objItem->source)
		{
			// Link to an external page
			case 'external':
				return $objItem->url;
				break;

			// Link to an internal page
			case 'internal':
				if (($objTarget = $objItem->getRelated('jumpTo')) !== null)
				{
					return $strBase . $this->generateFrontendUrl($objTarget->row());
				}
				break;

			// Link to an article
			case 'article':
				if (($objAthlete = \ArticleModel::findByPk($objItem->articleId, array('eager'=>true))) !== null && ($objPid = $objArticle->getRelated('pid')) !== null)
				{
					return $strBase . ampersand($this->generateFrontendUrl($objPid->row(), '/articles/' . ((!$GLOBALS['TL_CONFIG']['disableAlias'] && $objArticle->alias != '') ? $objArticle->alias : $objArticle->id)));
				}
				break;
		}

		// Link to the default page
		return $strBase . sprintf($strUrl, (($objItem->alias != '' && !$GLOBALS['TL_CONFIG']['disableAlias']) ? $objItem->alias : $objItem->id));
	}

}
