<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\CustomSiteId;

use Piwik\Plugins\MobileAppMeasurable\Type as MobileAppType;
use Piwik\Settings\Setting;
use Piwik\Settings\FieldConfig;
use Piwik\Db;
use Piwik\Common;

/**
 * Defines Settings for CustomSiteId.
 *
 * Usage like this:
 * // require Piwik\Plugin\SettingsProvider via Dependency Injection eg in constructor of your class
 * $settings = $settingsProvider->getMeasurableSettings('CustomSiteId', $idSite);
 * $settings->appId->getValue();
 * $settings->contactEmails->getValue();
 */
class MeasurableSettings extends \Piwik\Settings\Measurable\MeasurableSettings
{
    /** @var Setting|null */
    public $customSiteId;

    protected function init()
    {
        $this->customSiteId = $this->makeCustomSiteIdSetting();
    }

    private function makeCustomSiteIdSetting()
    {
        $defaultValue = '';
        $type = FieldConfig::TYPE_STRING;

        return $this->makeSetting('custom_site_id', $defaultValue, $type, function (FieldConfig $field) {
            $field->title = 'Custom Site Id';
            $field->uiControlAttributes = ['size' => 15];
            $field->inlineHelp = 'Enter the custom Id you\'d like';
            $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
            $field->validate = function ($value, $setting) {
                if ($value != '') {
                  if (strlen($value) < 5) {
                    throw new \Exception('Custom Site Id value must be longer than 4 characters');
                  }

                  if (preg_match('/[a-zA-Z]/', $value) === 0) {
                    throw new \Exception('Custom Site Id field must include alpha character.');
                  }

                  $sql = "SELECT idsite from `" . Common::prefixTable('site_setting') . "`
                    where setting_name = ? and setting_value = ?";
                  $siteId = Db::fetchOne($sql, array('custom_site_id', $value));
                  if ($siteId && (stripos($_POST['method'], 'add') !== false || $siteId != $_POST['idSite'])) {
                      throw new \Exception('Duplicate Custom Site Id, each site id must be unique.');
                  }
                }
            };
        });
    }

}
