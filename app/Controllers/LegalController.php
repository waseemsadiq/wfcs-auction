<?php

namespace App\Controllers;

use Core\Controller;

class LegalController extends Controller
{
    public function terms(): void
    {
        global $basePath;
        $user    = getAuthUser();
        $content = $this->renderView('legal/terms', ['user' => $user]);

        $this->view('layouts/public', [
            'pageTitle' => 'Terms of Use',
            'activeNav' => '',
            'user'      => $user,
            'content'   => $content,
            'mainWidth' => 'max-w-4xl',
        ]);
    }

    public function privacy(): void
    {
        global $basePath;
        $user    = getAuthUser();
        $content = $this->renderView('legal/privacy', ['user' => $user]);

        $this->view('layouts/public', [
            'pageTitle' => 'Privacy Policy',
            'activeNav' => '',
            'user'      => $user,
            'content'   => $content,
            'mainWidth' => 'max-w-4xl',
        ]);
    }
}
