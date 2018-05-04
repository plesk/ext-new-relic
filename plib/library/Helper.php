<?php
// Copyright 1999-2018. Plesk International GmbH.

class Modules_NewRelic_Helper
{
    public static function postInstallCheck()
    {
        pm_ApiCli::callSbin('postinstallcheck.sh');
    }

    public static function preUninstallCheck()
    {
        pm_ApiCli::callSbin('preuninstallcheck.sh');
    }

    /**
     * Gets all installed Plesk PHP versions with the help of shell script
     *
     * @return array
     * @throws pm_Exception
     */
    public static function getPleskPhpVersions()
    {
        $php_versions = array();

        $result = pm_ApiCli::callSbin('phpversions.sh', array(), pm_ApiCli::RESULT_FULL);

        if (empty($result['code'] AND !empty($result['stdout']))) {
            $php_versions_object = json_decode($result['stdout']);

            foreach ($php_versions_object as $php_version) {
                if (!array_key_exists($php_version->fullVersion, $php_versions)) {
                    if ($php_version->status == 'enabled') {
                        $php_versions[$php_version->fullVersion] = substr($php_version->clipath, 0, strrpos($php_version->clipath, '/'));
                    }
                }
            }
        }

        return $php_versions;
    }
}