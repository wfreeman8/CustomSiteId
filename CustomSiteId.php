<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\CustomSiteId;
use Piwik\Db;
use Piwik\Common;
use Piwik\Plugin\SettingsProvider;

class CustomSiteId extends \Piwik\Plugin
{
    public function registerEvents()
    {
        return [
            'SitesManager.getImageTrackingCode' => 'updateImageUrl',
            'Tracker.getJavascriptCode' => 'updateJavascriptCode',
            'Tracker.Request.getIdSite' => 'convertSiteId',
        ];
    }

    // modify the generated javascript code with the custom site Id
    public function updateJavascriptCode(&$codeImpl, $parameters)
    {
      $settings = new MeasurableSettings($codeImpl['idSite']);
      $customSiteId = $settings->customSiteId->getValue();
      if ($customSiteId) {
        $codeImpl['idSite'] = $customSiteId;
      }
    }

    // modify the image url with the custom site Id
    public function updateImageUrl(&$piwikUrl, &$urlParams)
    {
      $settings = new MeasurableSettings($urlParams['idsite']);
      $customSiteId = $settings->customSiteId->getValue();
      if ($customSiteId) {
        $urlParams['idsite'] = urlencode($customSiteId);
      }
    }

    // convert custom site id back to idSite and if using numeric id
    public function convertSiteId(&$idSite, $params)
    {
      try {
        $sql = "SELECT idsite from `" . Common::prefixTable("site_setting") . "`
               where setting_name = ? and setting_value = ?";
        $siteId = Db::fetchOne($sql, array('custom_site_id', $params['idsite']));
        if (is_numeric($siteId)) {
          $idSite = intval($siteId);
        }
        else if (is_numeric($idSite)) {
          $sql = "SELECT setting_value from `" . Common::prefixTable("site_setting") . "`
               where setting_name = ? and idsite = ?";
          $customSiteIdValue = Db::fetchOne($sql, array('custom_site_id', $params['idsite']));
          if ($customSiteIdValue) {
            throw Exception('Invalid CustomSiteId');
          }
        }
        else {
            throw Exception('Invalid siteId');          
        }
        
      } catch (Exception $e) {
        // ignore column already exists error
        if (!Db::get()->isErrNo($e, '1060')) {
            throw $e;
        }
      }
    }
}
