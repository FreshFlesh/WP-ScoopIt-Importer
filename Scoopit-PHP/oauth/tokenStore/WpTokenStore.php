<?php

include("TokenStore.php");

// Store authentication tokens in the session
class WpTokenStore implements TokenStore{
	public function __construct() {}
	
	// store
	public function storeRequestToken($value){
		update_option('scoopit_oauth_requestToken',$value);
	}
	public function storeAccessToken($value){
		update_option('scoopit_oauth_accessToken',$value);
	}
	public function storeVerifier($value){
		update_option('scoopit_oauth_verifier',$value);
	}
	public function storeSecret($value){
		update_option('scoopit_oauth_secret',$value);
	}
	
	// get
	public function getRequestToken(){
		return get_option('scoopit_oauth_requestToken');
	}
	public function getAccessToken(){
		return get_option('scoopit_oauth_accessToken');
	}
	public function getVerifier(){
		return get_option('scoopit_oauth_verifier');
	}
	public function getSecret(){
		return get_option('scoopit_oauth_secret');
	}
	
	// flush
	public function flushRequestToken(){
		delete_option('scoopit_oauth_requestToken');
	}
	public function flushAccessToken(){
		delete_option('scoopit_oauth_accessToken');
	}
	public function flushVerifier(){
		delete_option('scoopit_oauth_verifier');
	}
	public function flushSecret(){
		delete_option('scoopit_oauth_secret');
	}
	
	public function flushAll() {
		delete_option('scoopit_oauth_requestToken');
		delete_option('scoopit_oauth_accessToken');
		delete_option('scoopit_oauth_verifier');
		delete_option('scoopit_oauth_verifier');
		delete_option('scoopit_oauth_secret');
	}
}

?>