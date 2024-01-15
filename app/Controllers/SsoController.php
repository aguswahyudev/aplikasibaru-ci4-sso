<?php

namespace App\Controllers;

use App\Models\User;
use Config\Services;

class SsoController extends BaseController
{
    public function index()
    {
        return view('auth/login');
    }

    public function getLogin()
    {
        session()->set('state', $state = bin2hex(random_bytes(32)));

        $query = http_build_query([
            'client_id' => '9b181730-7c24-4f20-9b92-31d6a3b044d4', //sesuaikan
            'redirect_uri' => 'http://localhost:8080/callback', //sesuaikan
            'response_type' => 'code',
            'scope' => 'view-user',
            'state' => $state,
            // 'prompt' => '', // "none", "consent", or "login"
        ]);

        return redirect()->to('http://sso-bps.test/oauth/authorize?' . $query);
    }

    public function getCallback()
    {

        $http = Services::curlrequest([
            'baseURI' => 'http://sso-bps.test',
        ]);

        $request = Services::request();

        $response = $http->post('/oauth/token', [
            'form_params' => [
                'grant_type' => 'authorization_code',
                'client_id' => '9b181730-7c24-4f20-9b92-31d6a3b044d4', //sesuaikan
                'client_secret' => 'pBXGolIup3sZYgCXGuMgOS13G2Li1x4wNLv3lATW', //sesuaikan
                'redirect_uri' => 'http://localhost:8080/callback',
                'code' => $request->getVar('code')
            ]
        ]);

        $response = json_decode($response->getBody(), true);

        if (isset($response['access_token'])) {
            session()->set('access_token', $response['access_token']);
            return redirect()->to('http://localhost:8080/connect');
        } else {
            return redirect()->to('http://localhost:8080/login');
        }
    }

    public function getConnect()
    {
        $access_token = session()->get('access_token');

        $http = \Config\Services::curlrequest([
            'baseURI' => 'http://sso-bps.test',
        ]);

        $response = $http->setHeader('Authorization', 'Bearer ' . $access_token)
            ->get('/api/user');

        $response = json_decode($response->getBody(), true);

        $userModel = new \App\Models\User();
        $user = $userModel->where('sso_bps_id', $response['nip_bps'])->first();
        if (!$user) {
            $userModel->insert([
                'name' => $response['name'],
                'email' => $response['email'],
                'sso_bps_id' => $response['nip_bps']
            ]);
            $user = $userModel->where('sso_bps_id', $response['nip_bps'])->first();
        } else {
            $userModel->update($user['id'], [
                'name' => $response['name'],
                'email' => $response['email'],
                'sso_bps_id' => $response['nip_bps']
            ]);
            $user = $userModel->where('sso_bps_id', $response['nip_bps'])->first();
        }

        session()->set([
            'name' => $user['name'],
            'email' => $user['email'],
            'isLoggedIn' => true
        ]);

        return redirect()->to('http://localhost:8080/dashboard');
    }

    public function logout()
    {
        session()->destroy();
        session()->set('isLoggedIn', false);
        return redirect()->to('/login');
    }


}
