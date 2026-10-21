<?php
// app/data/site-defaults.php - built-in site info (contact, about, terms, privacy).
// The admin changes these on Admin -> "সাইট তথ্য" (saved in the settings table as site_<key>); empty there = this text.
// Long texts: an empty line starts a new paragraph, a line starting with "## " is a heading (see rich_text()).
// Read by HomeContentModel::site().
return [
    // ---- Contact ----
    'phone'      => '01700000000',
    'whatsapp'   => '01700000000',
    'email'      => 'support.shomadhan@gmail.com',
    'address_bn' => 'ময়মনসিংহ সদর, ময়মনসিংহ, বাংলাদেশ',
    'address_en' => 'Mymensingh Sadar, Mymensingh, Bangladesh',
    'hours_bn'   => 'প্রতিদিন সকাল ৯টা – রাত ৯টা',
    'hours_en'   => 'Every day 9:00 AM – 9:00 PM',
    // ---- Social links (empty = icon hidden) ----
    'facebook'   => '',
    'youtube'    => '',
    'instagram'  => '',
    'linkedin'   => '',
    // ---- Wallet top-up numbers ----
    'pay_bkash'  => '01700000000',     // wallet top-up: users "Send Money" to these personal numbers, then type the transaction ID
    'pay_nagad'  => '01700000000',
    'pay_rocket' => '',                // empty = this method is not offered

    // ---- About page ----
    'about_bn' => "সমাধান ময়মনসিংহের একটি হোম সার্ভিস প্ল্যাটফর্ম। ঘরের ইলেকট্রিক কাজ, প্লাম্বিং, এসি সার্ভিস, লন্ড্রি, বাসা পরিষ্কারসহ দৈনন্দিন নানা কাজের জন্য আমরা যাচাই করা দক্ষ সেবাদাতাকে আপনার দরজায় পৌঁছে দিই।\n\nআমরা দেখেছি, ভালো মিস্ত্রি বা টেকনিশিয়ান খুঁজে পাওয়া, ন্যায্য দাম জানা আর কাজের পরে ভরসা পাওয়া সবসময় সহজ না। তাই এক জায়গায় সেবা বেছে নেওয়া, দাম দেখা আর সহজে যোগাযোগের ব্যবস্থা করেছি।",
    'about_en' => "Shomadhan is a home service platform in Mymensingh. For electrical work, plumbing, AC service, laundry, home cleaning and other everyday jobs, we bring verified, skilled service providers to your door.\n\nFinding a good technician, knowing a fair price and trusting the work afterwards is not always easy. So we put choosing a service, seeing the price and reaching us in one simple place.",
    'mission_bn' => "ময়মনসিংহের প্রতিটি ঘরে নির্ভরযোগ্য, ন্যায্য দামের আর ঝামেলাহীন হোম সার্ভিস পৌঁছে দেওয়া, আর স্থানীয় দক্ষ কর্মীদের জন্য সম্মানজনক কাজের সুযোগ তৈরি করা।",
    'mission_en' => "To bring reliable, fairly priced and hassle-free home services to every home in Mymensingh, and to create respectful work for skilled local people.",

    // ---- Terms page ----
    'terms_bn' => "## ১. আমাদের সেবা\nসমাধান একটি অনলাইন প্ল্যাটফর্ম, যার মাধ্যমে ইউজার ঘরের বিভিন্ন কাজের জন্য সেবাদাতা খুঁজে নিতে পারেন। কাজটি করেন আমাদের তালিকাভুক্ত সেবাদাতা।\n\n## ২. অ্যাকাউন্ট\nঅ্যাকাউন্ট খুলতে সঠিক নাম, নিজের মোবাইল নম্বর, এলাকা আর লিঙ্গ দিতে হবে। আপনার পাসওয়ার্ড গোপন রাখার দায়িত্ব আপনার। অন্যের তথ্য দিয়ে অ্যাকাউন্ট খোলা যাবে না।\n\n## ৩. দাম ও পেমেন্ট\nসেবার পাশে দেখানো দাম শুরুর দাম। কাজের ধরন আর পরিমাণ দেখে আসল দাম কিছুটা বাড়তে পারে, যা কাজ শুরুর আগে আপনাকে জানানো হবে।\n\n## ৪. বাতিল ও অভিযোগ\nকাজ শুরুর আগে আপনি বুকিং বাতিল করতে পারবেন। কাজ নিয়ে কোনো অভিযোগ থাকলে ৩ দিনের মধ্যে আমাদের জানান, আমরা যাচাই করে ব্যবস্থা নেব।\n\n## ৫. ইউজারের দায়িত্ব\nসেবাদাতার সাথে ভদ্র আচরণ করবেন এবং কাজের জায়গায় নিরাপদ পরিবেশ রাখবেন। মিথ্যা বুকিং বা অপব্যবহার করলে অ্যাকাউন্ট বন্ধ করা হতে পারে।\n\n## ৬. দায়বদ্ধতা\nআমরা সেবাদাতাদের যাচাই করে তালিকাভুক্ত করি, তবুও কাজের সময় ঘরের মূল্যবান জিনিস নিজে দেখে রাখার অনুরোধ রইল।\n\n## ৭. শর্তের পরিবর্তন\nপ্রয়োজনে এই শর্তাবলী বদলানো হতে পারে। বদলালে এই পেজে নতুন শর্ত দেখানো হবে।",
    'terms_en' => "## 1. Our service\nShomadhan is an online platform where users find service providers for jobs at home. The work is done by the service providers listed with us.\n\n## 2. Account\nTo open an account you must give your real name, your own mobile number, area and gender. Keeping your password secret is your responsibility. You may not open an account with someone else's details.\n\n## 3. Price and payment\nThe price shown with a service is the starting price. The final price may be a little higher depending on the job, and you will be told before the work starts.\n\n## 4. Cancelling and complaints\nYou can cancel a booking before the work starts. If you have a complaint about the work, tell us within 3 days and we will check it and act.\n\n## 5. Your duties\nBe polite to the service provider and keep the work place safe. False bookings or misuse can close your account.\n\n## 6. Liability\nWe check service providers before listing them, but please keep an eye on your valuables while work is going on.\n\n## 7. Changes\nThese terms may change when needed. The new terms will always be shown on this page.",

    // ---- Privacy page ----
    'privacy_bn' => "## ১. আমরা কী তথ্য রাখি\nনাম, মোবাইল নম্বর, ইমেইল (দিলে), এলাকা, লিঙ্গ আর প্রোফাইল ছবি (দিলে)। লগইনের সময় আর ভাষার পছন্দও রাখা হয়।\n\n## ২. কেন রাখি\nআপনার অ্যাকাউন্ট চালাতে, আপনার এলাকায় সেবাদাতা পাঠাতে, অফার আর নোটিফিকেশন দেখাতে এবং প্রয়োজনে যোগাযোগ করতে।\n\n## ৩. কার সাথে শেয়ার করি\nশুধু যে সেবাদাতা আপনার কাজটি করবেন, তিনি কাজের জন্য দরকারি তথ্য (নাম, মোবাইল, ঠিকানা) পাবেন। আপনার তথ্য আমরা কখনো বিক্রি করি না।\n\n## ৪. নিরাপত্তা\nপাসওয়ার্ড এনক্রিপ্ট করে রাখা হয়, আমরাও আসল পাসওয়ার্ড দেখতে পারি না। ভুল পাসওয়ার্ড বারবার দিলে কিছুক্ষণের জন্য লগইন আটকে যায়।\n\n## ৫. কুকি\nভাষার পছন্দ আর \"আমাকে মনে রাখুন\" চালু রাখতে কুকি ব্যবহার করা হয়।\n\n## ৬. আপনার অধিকার\nপ্রোফাইল থেকে নিজের তথ্য বদলাতে পারবেন। অ্যাকাউন্ট মুছতে চাইলে আমাদের সাথে যোগাযোগ করুন।",
    'privacy_en' => "## 1. What we keep\nName, mobile number, email (if given), area, gender and profile picture (if given). We also keep your login time and language choice.\n\n## 2. Why\nTo run your account, send a service provider in your area, show offers and notifications, and contact you when needed.\n\n## 3. Who sees it\nOnly the service provider who does your job gets the details needed for it (name, mobile, address). We never sell your information.\n\n## 4. Security\nPasswords are stored encrypted; even we can't see your real password. Too many wrong passwords lock the login for a while.\n\n## 5. Cookies\nCookies keep your language choice and \"Remember me\".\n\n## 6. Your rights\nYou can change your details from your profile. To delete your account, contact us.",
];
