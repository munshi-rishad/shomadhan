<?php
// app/controllers/PageController.php
// CONTROLLER for the open info pages (anyone can open them, logged in or not):
//   about.php    আমাদের সম্পর্কে (our story + mission)
//   terms.php    শর্তাবলী (linked from the register form)
//   privacy.php  প্রাইভেসি পলিসি
// The texts come from Admin -> "সাইট তথ্য" (HomeContentModel::site()).

require_once APP_PATH . '/models/HomeContentModel.php';

class PageController
{
    // ---- One info page ----
    // $page = 'about', 'terms' or 'privacy' (given by the public/*.php file, never by the visitor)
    public function show($page)
    {
        list($lang, $t) = lang_texts();
        $account = current_account();
        $titles = ['about' => 'page_about', 'terms' => 'terms_link1', 'privacy' => 'terms_link2'];   // language key of each title
        view('pages/info', [
            'lang'    => $lang,
            't'       => $t,
            'page'    => $page,
            'title'   => $t[$titles[$page]],
            'site'    => HomeContentModel::site(),
            'home'    => $account ? dashboard_url($account['role']) : 'index.php',   // where the logo / "home" link goes
            'account' => $account,
            'stats'   => $page === 'about' ? HomeContentModel::stats() : [],
        ]);
    }
}
