<?php
// app/controllers/HomeController.php
// CONTROLLER = the manager. It asks the Model for data, then shows the View.

require_once APP_PATH . '/models/ServiceModel.php';
require_once APP_PATH . '/models/HomeContentModel.php';

class HomeController
{
    public function index()
    {
        // 1. Load both language files
        $allTexts = [
            'bn' => require APP_PATH . '/lang/bn.php',
            'en' => require APP_PATH . '/lang/en.php',
        ];

        // 2. Which language? Read the cookie. Default is Bangla.
        $lang = 'bn';
        if (isset($_COOKIE['lang']) && isset($allTexts[$_COOKIE['lang']])) {
            $lang = $_COOKIE['lang'];
        }

        // 3. Get data from the Model
        $services = ServiceModel::getAll();

        // Coupon picture: the admin can change it (admin dashboard -> Welcome page)
        $promo = HomeContentModel::DEFAULT_WELCOME_BANNER;
        try {
            $promo = HomeContentModel::setting('welcome_banner', $promo);
        } catch (PDOException $ex) {
            app_log('error', 'welcome banner: ' . $ex->getMessage());   // database off: keep the built-in picture
        }

        // 4. Send everything to the View
        view('landing/home', [
            'lang'     => $lang,
            't'        => $allTexts[$lang],   // chosen language: used ONLY for service names + 2 buttons
            'bn'       => $allTexts['bn'],    // Bangla: the rest of the page is always Bangla
            'allTexts' => $allTexts,          // both languages (used by landing.js)
            'services' => $services,
            'promo'    => $promo,
        ]);
    }
}
