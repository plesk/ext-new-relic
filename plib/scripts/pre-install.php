<?php
// Copyright 1999-2016. Parallels IP Holdings GmbH.

$memoryLimit = ini_get('memory_limit');

switch (true) {
    case false !== strpos($memoryLimit, 'K'):
        $memoryLimit = (int)$memoryLimit * 1024;
        break;
    case false !== strpos($memoryLimit, 'M'):
        $memoryLimit = (int)$memoryLimit * 1024 * 1024;
        break;
    default:
        $memoryLimit = (int)$memoryLimit;
}

if ($memoryLimit < 32 * 1024 * 1024) {
    echo "$memoryLimit is too small\n";
    exit(1);
}

exit(0);
