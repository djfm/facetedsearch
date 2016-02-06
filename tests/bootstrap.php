<?php

require implode(DIRECTORY_SEPARATOR, [
    '..', '..', 'config', 'config.inc.php'
]);

// isolate variables in a scope
call_user_func(function () {
    $module = Module::getInstanceByName('facetedsearch');
    if ($module) {
        $module->uninstall();
    }
});
