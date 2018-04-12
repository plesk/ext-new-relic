<?php
// Copyright 1999-2018. Plesk International GmbH.

pm_Context::init('new-relic');

Modules_NewRelic_Helper::postInstallCheck();
