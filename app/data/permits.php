<?php
// app/data/permits.php
// Government papers this business needs in Bangladesh, and the admin pages whose work needs them.
// Those pages show one short yellow line on top: "for this work you need: ..." (admin/layout.php).
// Researched October 2026 - NOT legal advice; the full report is the PDF "সরকারি কাগজপত্র ও আইনি ধাপ".
// When a paper is in hand, delete its block here and the line goes away from its pages.
// Each paper: key => [link = where the info came from ("read more" button), pages, name in Bangla, name in English]

return [
    'trade_licence'   => ['link' => 'https://bangladeshtradeportal.gov.bd/index.php?r=searchProcedure/view1&id=65', 'pages' => ['bookings'],          'bn' => 'ট্রেড লাইসেন্স',                                  'en' => 'Trade licence'],
    'etin'            => ['link' => 'https://secure.incometax.gov.bd/', 'pages' => ['bookings'],          'bn' => 'ই-টিআইএন',                                          'en' => 'e-TIN'],
    'commerce_rules'  => ['link' => 'https://www.thedailystar.net/law-our-rights/news/overview-the-digital-commerce-operation-guidelines-2021-2128871', 'pages' => ['messages', 'site'],  'bn' => 'ডিজিটাল কমার্স নির্দেশিকা ২০২১ মানা',              'en' => 'Digital Commerce Guidelines 2021'],
    'data_protection' => ['link' => 'https://www.thedailystar.net/tech-startup/news/bangladeshs-personal-data-protection-ordinance-2025-key-takeaways-4015401', 'pages' => ['users', 'site'],     'bn' => 'ব্যক্তিগত তথ্য সুরক্ষা (প্রাইভেসি নোটিশ ও সম্মতি)', 'en' => 'Personal data protection (privacy notice + consent)'],
    'pest_licence'    => ['link' => 'https://www.tbsnews.net/node/652218', 'pages' => ['providers'],         'bn' => 'পেস্ট কন্ট্রোল লাইসেন্স (পেস্ট কন্ট্রোল সেবার জন্য)', 'en' => 'Pest-control licence (for pest-control service)'],
    'wallet_licence'  => ['link' => 'https://vdb-loi.com/bd_publications/bangladesh-enacts-payment-and-settlement-system-act-2024-strengthening-financial-security-and-consumer-protection/', 'pages' => ['topups', 'site'],    'bn' => 'বাংলাদেশ ব্যাংকের অনুমতি (ওয়ালেট)',                'en' => 'Bangladesh Bank permission (wallet)'],
    'mfs_business'    => ['link' => 'https://www.bkash.com/en/page/personal-retail-account', 'pages' => ['topups', 'site'],    'bn' => 'ব্যবসার bKash/Nagad অ্যাকাউন্ট',                    'en' => 'Business bKash/Nagad account'],
];
