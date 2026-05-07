<?php
require 'aramex_service.php';

$aramex = new AramexService();

$shipment = [
    'Reference1' => 'ORDER123',

    'Shipper' => [
        'AccountNumber' => 'YOUR_ACCOUNT_NUMBER',
        'PartyAddress' => [
            'Line1' => 'Delhi',
            'City' => 'Delhi',
            'CountryCode' => 'IN'
        ],
        'Contact' => [
            'PersonName' => 'Sender Name',
            'PhoneNumber1' => '9999999999'
        ]
    ],

    'Consignee' => [
        'PartyAddress' => [
            'Line1' => 'Mumbai',
            'City' => 'Mumbai',
            'CountryCode' => 'IN'
        ],
        'Contact' => [
            'PersonName' => 'Receiver Name',
            'PhoneNumber1' => '8888888888'
        ]
    ],

    'ShippingDateTime' => date('c'),

    'Details' => [
        'ActualWeight' => [
            'Value' => 1,
            'Unit' => 'KG'
        ],
        'ProductGroup' => 'DOM',
        'ProductType' => 'OND',
        'PaymentType' => 'P'
    ]
];

$response = $aramex->createShipment($shipment);

print_r($response);