<?php
// app/controllers/HomeController.php
// CONTROLLER for the welcome page (index.php). It asks the Model for data, then shows the View.

require_once APP_PATH . '/models/HomeContentModel.php';

class HomeController
{
    const SHOW_SERVICES = 5;     // how many service icons the welcome page shows
    // the texts the language buttons change (landing.js): the 2 buttons + the footer
    const SWITCH_KEYS = [
        'btn_register', 'btn_login', 'tagline', 'page_about', 'terms_link1', 'terms_link2',
        'ft_about', 'ft_quick', 'ft_home', 'ft_services', 'ft_contact', 'ft_contact_info',
        'ft_support_head', 'ft_support', 'ft_refund', 'ft_rights', 'ft_b1', 'ft_b2', 'ft_b3', 'ft_b4',
    ];

    // ---- The welcome page ----
    public function index()
    {
        // both languages are needed: the page is Bangla, the language buttons swap a few texts to English
        list($lang, $t) = lang_texts();
        $bn = $lang === 'bn' ? $t : require APP_PATH . '/lang/bn.php';
        $en = $lang === 'en' ? $t : require APP_PATH . '/lang/en.php';

        // Coupon picture + service icons: both come from the admin dashboard
        // (Welcome page and Services). If the database is off, the page still opens with the built-in picture.
        $promo = HomeContentModel::DEFAULT_WELCOME_BANNER;
        $services = [];
        try {
            $promo = HomeContentModel::setting('welcome_banner', $promo);
            $services = array_slice(HomeContentModel::services(true), 0, self::SHOW_SERVICES);
        } catch (PDOException $ex) {
            app_log('error', 'welcome page: ' . $ex->getMessage());
        }

        view('landing/home', [
            'lang'     => $lang,
            't'        => $t,      // chosen language: used ONLY for service names + 2 buttons
            'bn'       => $bn,     // Bangla: the rest of the page is always Bangla
            // only the texts the language buttons change (landing.js)
            'switch'   => [
                'bn' => self::switchTexts($bn),
                'en' => self::switchTexts($en),
            ],
            'services' => $services,
            'promo'    => $promo,
            'site'     => HomeContentModel::site(),   // phone, email, hours, customer care (Admin -> সাইট তথ্য)
        ]);
    }

    // Only the SWITCH_KEYS texts of one language file
    private static function switchTexts(array $texts)
    {
        return array_intersect_key($texts, array_flip(self::SWITCH_KEYS));
    }
}
