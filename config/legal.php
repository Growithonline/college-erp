<?php

// Institute admins must accept every document listed here (in this order)
// before they get dashboard access. Bump a document's 'version' whenever
// its content changes materially — that forces every institute to re-accept
// just that document on their next login.
return [
    'documents' => [
        'privacy_policy' => [
            'label'   => 'Privacy Policy',
            'version' => '1.0',
            'view'    => 'legal.privacy-policy',
        ],
        'terms_conditions' => [
            'label'   => 'Terms & Conditions',
            'version' => '1.0',
            'view'    => 'legal.terms-conditions',
        ],
        'disclaimer' => [
            'label'   => 'Disclaimer',
            'version' => '1.0',
            'view'    => 'legal.disclaimer',
        ],
    ],
];
