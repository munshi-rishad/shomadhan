<?php
// app/controllers/DashboardController.php
// 3 dashboards: admin, employee, user. Blank for now, they only show which dashboard you are on.

class DashboardController
{
    public function admin()    { $this->show('admin', 'dash_admin'); }
    public function employee() { $this->show('employee', 'dash_employee'); }
    public function user()     { $this->show('user', 'dash_user'); }

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
