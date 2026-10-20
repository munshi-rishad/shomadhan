<?php
// app/controllers/HomeController.php
// CONTROLLER for the welcome page (index.php). It asks the Model for data, then shows the View.

require_once APP_PATH . '/models/HomeContentModel.php';

class HomeController
{
    const SHOW_SERVICES = 5;     // how many service icons the welcome page shows

    public function index()
    {
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
                'bn' => ['btn_register' => $bn['btn_register'], 'btn_login' => $bn['btn_login']],
                'en' => ['btn_register' => $en['btn_register'], 'btn_login' => $en['btn_login']],
            ],
            'services' => $services,
            'promo'    => $promo,
        ]);
    }
}
