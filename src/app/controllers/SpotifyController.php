<?php

use Phalcon\Mvc\Controller;
use GuzzleHttp\Client;
class SpotifyController extends Controller
{
    public function apiAction() {
        $url = "https://accounts.spotify.com/authorize?";

        $client_id = 'e9ff6d571b9e46499848f9e5e6e2af37';
        $client_secret = '198f9e76e6754e678e52a40101e46524';
        $headers = [
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'redirect_uri' => 'http://localhost:8080/spotify/api',
        'scope' => 'playlist-modify-public playlist-read-private playlist-modify-private',
        'response_type' =>'code'
        ];

        $OauthUrl = $url.http_build_query($headers);
        $this->view->OauthUrl = $OauthUrl;
        if ($this->request->get('code') !=null){
            $code = $this->request->get('code');
            $data = array(
                'redirect_uri' => 'http://localhost:8080/spotify/api',
                'grant_type'   => 'authorization_code',
                'code'         => $code,
            );

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, 'https://accounts.spotify.com/api/token');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Basic ' . base64_encode($client_id . ':' . $client_secret)));

            $result = json_decode(curl_exec($ch));
            $this->session->token = $result->access_token;
            $this->response->redirect('/spotify/search');
        }


    }
    public function searchAction()
    {
        $this->view->allPlaylists = $this->getPlaylist();
        if ($this->request->isPost()) {
            $toSearch = urlencode($this->request->getPost('search'));
            if ($this->request->has('track') || count($this->request->getPost()) == 1) {
                $this->view->tracks = $this->result($toSearch, 'track');
            } if ($this->request->has('album')) {
                $this->view->album = $this->result($toSearch, 'album');
            } if ($this->request->has('artist')) {
                $this->view->artist = $this->result($toSearch, 'artist');
            } if ($this->request->has('playlist')) {
                $this->view->playlist = $this->result($toSearch, 'playlist');
            } if ($this->request->has('show')) {
                $this->view->shows = $this->result($toSearch, 'show');
            } if ($this->request->has('episode')) {
                $this->view->episode = $this->result($toSearch, 'episode');
            }
        }
    }
    public function addAction() {
        
        if ($this->request->isPost()) {
            $playlist_id = $this->request->getPost('playlist');
            $uri = $this->request->getPost('uri');

            $url = "https://api.spotify.com/";

            $client = new Client([
                'base_uri' => URL
            ]);
            $result = $client->request('POST', "/v1/playlists/$playlist_id/tracks?uris=$uri", [
                'headers' => [
                    'Authorization' => "Bearer " . $this->session->token
                ]
            ]);
        }
        $this->response->redirect('/spotify/search');

    }
    public function result($toSearch, $type) {
        $url = URL . "search?q=$toSearch&type=$type";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        $headers = array('Authorization: Bearer ' . $this->session->token);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);

        $result = curl_exec($ch);
        return json_decode($result, true);
    }
    public function getPlaylist() {
        $url = "https://api.spotify.com/v1/me/playlists";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        $headers = array('Authorization: Bearer ' . $this->session->token);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);

        $result = curl_exec($ch);
        return json_decode($result, true);

    }
     public function removeTrackAction() {
        if ($this->request->isPost()) {
            $playlist_id = $this->request->getPost('playlist_id');
            $track_uri = $this->request->getPost('removeTrack');

            

            $client = new Client([
                'base_uri' => URL
            ]);
            $result = $client->request('DELETE', "v1/playlists/$playlist_id/tracks", [
                'headers' => [
                    'Authorization' => "Bearer " . $this->session->token
                ],
                'body' => json_encode([
                    "uris" => [$track_uri]
                ])
            ]);
        }
        $this->response->redirect('/spotify/viewPlaylists?id='.$playlist_id);
    }
    public function createPlaylistAction() {
        if ($this->request->isPost()) {
            $playlistName = $this->request->getPost('playlist');
            $description = $this->request->getPost('description');
            $user_id = $this->getUserDetails()['id'];

            $client = new Client([
                'base_uri' => URL
            ]);
            $result = $client->request('POST', "https://api.spotify.com/v1/users/$user_id/playlists", [
                'headers' => [
                    'Authorization' => "Bearer " . $this->session->token
                ],
                'body' => json_encode([
                    "name" => $playlistName,
                    "description" => $description,
                    "public" => false
                ])
            ]);
       }
       $this->response->redirect('/spotify/search');
    }
    public function getUserDetails() {
        $url = URL.'me';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        $headers = array('Authorization: Bearer ' . $this->session->token);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);

        $result = curl_exec($ch);
        
        return (json_decode($result, true));
    }
    public function viewPlaylistsAction() {
        if ($this->request->get('id')!=null) {
            $playlist_id = $this->request->get('id');
            $url = URL."/$playlist_id/tracks";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);

            $headers = array('Authorization: Bearer ' . $this->session->token);
            
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            curl_setopt($ch, CURLOPT_TIMEOUT, 2);

            $result = curl_exec($ch);
            $this->view->playlists = json_decode($result, true);
        }
    }
}
