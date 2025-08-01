<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index(): string
    {
        //return view('welcome_message');

        $data['title'] = ucfirst('La barrigona'); // Capitalize the first letter
        return view('templates/header', $data)
            . view('templates/navigation')
            . view('templates/subheader')
            . view('pages/home')
            . view('templates/sub_footer')
            . view('templates/footer');
    }
}
