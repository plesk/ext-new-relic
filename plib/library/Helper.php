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
}