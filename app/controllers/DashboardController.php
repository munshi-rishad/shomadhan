<?php
// app/controllers/DashboardController.php
// Employee dashboard (blank for now). Admin: AdminController. User: UserHomeController.

class DashboardController
{
    public function employee() { $this->show('employee', 'dash_employee'); }

    private function show($role, $titleKey)
    {
        $account = require_role($role);      // not logged in -> login page, wrong role -> own dashboard
        list($lang, $t) = lang_texts();
        view('dashboard/blank', [
            'lang'    => $lang,
            't'       => $t,
            'title'   => $t[$titleKey],
            'account' => $account,
            'notice'  => flash_get('notice'),
        ]);
    }
}
