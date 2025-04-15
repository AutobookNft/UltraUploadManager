<?php

return [
    'errors' => [
        'GenericException' => [
            'handlers' => [
                'LogHandler' => [
                    'params' => [
                        'channel' => 'nftflorence',
                        'severity' => 'genric',
                        'message' => 'messaggio di: LogHandler!',
                        'code' => '0',
                    ],
                ],
                'EmailNotificationHandler' => [
                    'params' => [
                        'to' => 'devteam@nftflorence.com',
                        'subject' => 'messaggio di EmailNotificationHandler',
                        'body' => 'Qualcosa da comunicare',
                    ],
                ],
                'UserMessageHandler' => [
                    'params' => [
                        'title' => 'User message',
                        'message' => 'mesage from UserMessageHandler',
                    ],
                ],
                'CorrectiveActionHandler' => [
                    'params' => [
                        'action' => '',
                    ],
                ],
                'RedirectHandler' => [
                    'params' => [
                        'route' => 'back',
                    ],
                ],
            ],
        ],
        'NotAllowedTermException' => [
            'handlers' => [
                'LogHandler' => [
                    'params' => [
                        'channel' => 'nftflorence',
                        'severity' => 'genric',
                        'message' => 'L\'utente ha inserito un termine non consentito',
                        'code' => '0',
                    ],
                ],
                'UserMessageHandler' => [
                    'params' => [
                        'title' => 'User message',
                        'message' => 'mesage from UserMessageHandler',
                    ],
                ],
            ],
        ],
    ],
    'for_futur_use' => [
        // for future use
    ],
];
