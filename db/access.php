<?php
//creating capability to student and manager.
$capabilities = array(
    'local/deptrpts:managerreport' => array(
        'captype'=> 'write',
        'contextlevel'=>CONTEXT_SYSTEM,
        'archetypes'=>array(
          'manager'=>CAP_ALLOW,
        )
    ),
        'local/deptrpts:userreport' => array(
        'captype'=> 'write',
        'contextlevel'=>CONTEXT_SYSTEM,
        'archetypes'=>array(
          'student'=>CAP_ALLOW,
        )
    ),
);