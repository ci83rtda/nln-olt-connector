<?php

return [
    'tasks' => [
        'toggleCatvStatus' => [
            'action' => 'toggleCatvStatus',
            'port' => 1,
            'onuId' => 1,
            'model' => 'V452',
            'newStatus' => 'enable',
            'request_id' => '123456'
        ],
        'addOnu' => [
            'action' => 'addOnu',
            'port' => 1,
            'onuId' => 2,
            'serialNumber' => 'GPON00112233',
            'profile' => 'default',
            'description' => 'ONU for testing',
            'request_id' => '123457'
        ],
        'changeWifiSettings' => [
            'action' => 'changeWifiSettings',
            'port' => 1,
            'onuId' => 3,
            'wifiSettings' => [
                'ssid_1' => 'TestSSID1',
                'password_1' => 'password1',
                'ssid_5' => 'TestSSID5',
                'password_5' => 'password5'
            ],
            'wifiSwitchSettings' => [
                'switch_2_4' => 'enable',
                'switch_5_0' => 'enable'
            ],
            'model' => 'V452',
            'request_id' => '123458'
        ],
        'getWifiDetails' => [
            'action' => 'getWifiDetails',
            'port' => 1,
            'onuId' => 4,
            'request_id' => '123459'
        ],
        'getOnuStatus' => [
            'action' => 'getOnuStatus',
            'port' => 1,
            'onuId' => 5,
            'request_id' => '123460'
        ]
    ]
];
