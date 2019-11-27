<?php

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

function m365_install() {
	message::add('m365', 'Merci pour l\'installation de ce plugin. ATTENTION, la version de M365 requise est la V1.3.8');    
}

function m365_update() {
    message::add('m365', 'Merci pour la mise à jour de ce plugin. Pour rappel, la version de M365 requise est la V1.3.8');
    foreach (eqLogic::byType('m365') as $m365) {
        $m365->save();
    }
}

function m365_remove() {
	log::add('m365', 'warn', 'Suppression du Plugin m365');    
}

?>